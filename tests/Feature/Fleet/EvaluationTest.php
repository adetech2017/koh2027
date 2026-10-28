<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\Assignment;
use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\Evaluation;
use App\Models\Fleet\EvaluationPeriod;
use App\Models\Fleet\Vehicle;
use App\Services\Fleet\EvaluationSchedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

class EvaluationTest extends FleetTestCase
{
    private Beneficiary $beneficiary;

    private Assignment $assignment;

    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Process::run(['ffprobe', '-version'])->successful()) {
            $this->markTestSkipped('ffmpeg/ffprobe are not installed.');
        }

        Storage::fake('fleet');
        // Small pieces, so a test video spans several of them
        config(['fleet.evaluations.chunk_bytes' => 16 * 1024]);
        $this->workDir = sys_get_temp_dir().'/fleet-test-'.uniqid();
        File::ensureDirectoryExists($this->workDir);

        $this->beneficiary = Beneficiary::factory()->create();
        $this->assignment = Assignment::create([
            'vehicle_id' => Vehicle::factory()->create(['status' => 'assigned'])->id,
            'beneficiary_id' => $this->beneficiary->id,
            'status' => 'active',
            'handed_over_at' => now()->subDays(3),
        ]);
        $this->assignment->handovers()->create([
            'type' => 'handover', 'odometer_km' => 10000, 'fuel_level' => 'half', 'checklist' => [],
            'officer_signature_path' => 'x.png', 'signed_by_name' => 'X', 'signer_signature_path' => 'y.png', 'occurred_at' => now()->subDays(3),
        ]);
        $this->actingAs($this->beneficiary, 'fleet_beneficiary');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workDir);
        File::deleteDirectory(storage_path('app/private/fleet-uploads'));
        parent::tearDown();
    }

    /**
     * A tiny real video. $metadata: creation_time and/or location tags, as a phone writes them.
     */
    private function makeVideo(int $seconds = 21, array $metadata = [], string $name = 'clip.mp4'): string
    {
        $path = "{$this->workDir}/{$name}";
        $tags = collect($metadata)->flatMap(fn ($value, $key) => ['-metadata', "{$key}={$value}"])->all();
        Process::run(['ffmpeg', '-y', '-loglevel', 'error', '-f', 'lavfi', '-i', "testsrc=size=160x120:rate=5:duration={$seconds}",
            '-c:v', 'libx264', '-preset', 'ultrafast', ...$tags, $path])->throw();

        return $path;
    }

    private function upload(string $path): string
    {
        $start = $this->fleetApi('POST', 'evaluations/uploads', ['filename' => basename($path), 'mime' => 'video/mp4', 'size' => filesize($path)])
            ->assertCreated();

        $bytes = file_get_contents($path);
        foreach (str_split($bytes, $start->json('chunk_size')) as $index => $chunk) {
            $this->call('PUT', $this->fleetUrl("api/v1/evaluations/uploads/{$start->json('upload_id')}/chunks/{$index}"), [], [], [], ['HTTP_ACCEPT' => 'application/json'], $chunk)
                ->assertOk();
        }

        return $start->json('upload_id');
    }

    private function submit(string $uploadId, array $overrides = []): TestResponse
    {
        return $this->fleetApi('POST', 'evaluations', array_merge([
            'upload_id' => $uploadId,
            'source' => 'gallery',
            'latitude' => 6.6018, 'longitude' => 3.3515, 'accuracy_m' => 15,
            'mileage_km' => 10450, 'fuel_level' => 'half',
            'has_damage' => false, 'branding_intact' => true,
        ], $overrides));
    }

    public function test_windows_open_every_two_weeks_and_unanswered_ones_are_missed(): void
    {
        $this->fleetApi('GET', 'evaluations')->assertOk()
            ->assertJsonPath('current.sequence', 1)
            ->assertJsonPath('current.accepts_submissions', true)
            ->assertJsonPath('last_mileage_km', 10000);

        $this->travel(12)->days();
        $this->fleetApi('GET', 'evaluations')->assertJsonPath('current.sequence', 2)->assertJsonPath('periods.1.status', 'missed');

        // Returning the vehicle closes the open window
        $this->assignment->update(['status' => 'returned', 'returned_at' => now()]);
        app(EvaluationSchedule::class)->sync($this->assignment);
        $this->assertSame(['missed', 'closed'], $this->assignment->evaluationPeriods()->orderBy('sequence')->pluck('status')->all());
    }

    public function test_a_live_recording_with_the_on_screen_code_and_gps_is_verified(): void
    {
        $challenge = $this->fleetApi('POST', 'evaluations/challenge')->assertCreated();
        $uploadId = $this->upload($this->makeVideo());

        $this->submit($uploadId, ['source' => 'live', 'challenge_id' => $challenge->json('id')])->assertCreated();

        $evaluation = Evaluation::sole();
        $this->assertSame('verified_live', $evaluation->trust_level);
        $this->assertSame($challenge->json('code'), $evaluation->challenge_code);
        $this->assertSame('submitted', $evaluation->period->status);
        // The queued job (run straight away in tests) converted it and removed the upload
        $this->assertSame('ready', $evaluation->processing_status);
        Storage::disk('fleet')->assertExists([$evaluation->video_path, $evaluation->thumbnail_path]);
        $this->assertDirectoryDoesNotExist(storage_path("app/private/fleet-uploads/{$uploadId}"));

        // One video per window
        $this->fleetApi('POST', 'evaluations/uploads', ['filename' => 'b.mp4', 'mime' => 'video/mp4', 'size' => 1000])->assertJsonValidationErrors('period');
    }

    public function test_a_live_recording_with_a_used_or_foreign_code_is_unverified(): void
    {
        $other = Beneficiary::factory()->create();
        $otherPeriod = EvaluationPeriod::create(['assignment_id' => Assignment::create([
            'vehicle_id' => Vehicle::factory()->create()->id, 'beneficiary_id' => $other->id, 'status' => 'active', 'handed_over_at' => now(),
        ])->id, 'sequence' => 1, 'starts_at' => now(), 'ends_at' => now()->addDays(14)]);
        $foreign = \App\Models\Fleet\EvaluationChallenge::create(['beneficiary_id' => $other->id, 'period_id' => $otherPeriod->id, 'code' => '1234', 'expires_at' => now()->addHour()]);

        $this->submit($this->upload($this->makeVideo()), ['source' => 'live', 'challenge_id' => $foreign->id])->assertCreated();

        $this->assertSame('unverified', Evaluation::sole()->trust_level);
        $this->assertContains('no_valid_code', Evaluation::sole()->trust_flags);
    }

    public function test_a_gallery_video_recorded_in_this_window_with_a_location_is_accepted(): void
    {
        $video = $this->makeVideo(metadata: ['creation_time' => now()->subDay()->toIso8601ZuluString(), 'location' => '+06.6020+003.3510/']);

        $this->submit($this->upload($video))->assertCreated();

        $evaluation = Evaluation::sole();
        $this->assertSame('gallery_ok', $evaluation->trust_level);
        $this->assertEqualsWithDelta(6.602, $evaluation->metadata_latitude, 0.0001);
    }

    public function test_a_gallery_video_recorded_before_the_window_is_refused(): void
    {
        $video = $this->makeVideo(metadata: ['creation_time' => now()->subDays(20)->toIso8601ZuluString()]);

        $this->submit($this->upload($video))
            ->assertJsonValidationErrors('upload_id')
            ->assertJsonFragment(['message' => 'This video was recorded on '.now()->subDays(20)->timezone('Africa/Lagos')->format('j M Y').', before this evaluation window opened on '.now()->subDays(3)->timezone('Africa/Lagos')->format('j M Y').'. Please record a new video.']);

        $this->assertDatabaseCount('fleet_evaluations', 0);
        $this->assertSame('open', EvaluationPeriod::sole()->status);
    }

    public function test_a_gallery_video_without_metadata_is_accepted_but_flagged(): void
    {
        // e.g. a video forwarded through WhatsApp, which strips the recording date
        $this->submit($this->upload($this->makeVideo()), ['latitude' => null, 'longitude' => null, 'mileage_km' => 9000])->assertCreated();

        $evaluation = Evaluation::sole();
        $this->assertSame('unverified', $evaluation->trust_level);
        $this->assertEqualsCanonicalizing(['no_recording_date', 'no_location', 'mileage_decreased'], $evaluation->trust_flags);
    }

    public function test_the_same_file_cannot_be_submitted_twice(): void
    {
        $video = $this->makeVideo();
        $this->submit($this->upload($video))->assertCreated();

        // Next window, same file
        $this->travel(12)->days();
        $this->fleetApi('GET', 'evaluations');
        $this->submit($this->upload($video))->assertJsonValidationErrors(['upload_id' => 'This exact video has already been submitted. Please record a new one.']);
    }

    public function test_too_short_videos_and_non_videos_are_refused(): void
    {
        $this->submit($this->upload($this->makeVideo(5)))->assertJsonValidationErrors(['upload_id' => 'The video is too short. Please record at least 20 seconds.']);

        $text = "{$this->workDir}/notes.mp4";
        file_put_contents($text, str_repeat('not a video ', 100));
        $this->submit($this->upload($text))->assertJsonValidationErrors('upload_id');
    }

    public function test_the_checklist_needs_notes_for_damage_and_branding_problems(): void
    {
        $this->submit($this->upload($this->makeVideo()), ['has_damage' => true, 'branding_intact' => false, 'mileage_km' => null])
            ->assertJsonValidationErrors(['damage_notes', 'branding_notes', 'mileage_km']);
    }

    public function test_upload_pieces_are_checked_and_private(): void
    {
        $start = $this->fleetApi('POST', 'evaluations/uploads', ['filename' => 'a.mp4', 'mime' => 'video/mp4', 'size' => 40000])->assertCreated();
        $id = $start->json('upload_id');

        // Wrong size for piece 0
        $this->call('PUT', $this->fleetUrl("api/v1/evaluations/uploads/{$id}/chunks/0"), [], [], [], ['HTTP_ACCEPT' => 'application/json'], 'short')
            ->assertJsonValidationErrors('chunk');
        // Submitting before every piece has arrived
        $this->submit($id)->assertJsonValidationErrors(['upload_id' => "The video hasn't finished uploading yet."]);

        // Someone else can't see or add to it
        $this->actingAs(Beneficiary::factory()->create(), 'fleet_beneficiary');
        $this->fleetApi('GET', "evaluations/uploads/{$id}")->assertNotFound();
    }

    public function test_admins_can_list_and_watch_evaluations(): void
    {
        $this->submit($this->upload($this->makeVideo()))->assertCreated();
        $evaluation = Evaluation::sole();

        $this->actingAsFleetAdmin('reviewer');
        $this->fleetApi('GET', 'admin/evaluations?trust_level=unverified')->assertOk()->assertJsonPath('data.0.id', $evaluation->id);
        $this->fleetApi('GET', "admin/evaluations/{$evaluation->id}")->assertOk()->assertJsonPath('evaluation.vehicle.id', $this->assignment->vehicle_id);
        $this->get($this->fleetUrl("api/v1/admin/evaluations/{$evaluation->id}/video"))->assertOk()->assertHeader('Content-Type', 'video/mp4');

        // Beneficiaries can't reach the admin video URL
        $this->actingAs($this->beneficiary, 'fleet_beneficiary');
        auth('fleet_admin')->logout();
        $this->fleetApi('GET', "admin/evaluations/{$evaluation->id}/video")->assertUnauthorized();
    }
}
