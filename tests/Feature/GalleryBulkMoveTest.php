<?php

namespace Tests\Feature;

use App\Models\GalleryImage;
use App\Models\ImageCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryBulkMoveTest extends TestCase
{
    use RefreshDatabase;

    private function photo(?int $albumId): GalleryImage
    {
        return GalleryImage::create(['alt_text' => '', 'image_path' => 'gallery/x.jpg', 'category_id' => $albumId]);
    }

    public function test_editor_moves_selected_photos_into_an_album(): void
    {
        $events = ImageCategory::create(['name' => 'Events']);
        $unveiling = ImageCategory::create(['name' => 'Manifesto Unveiling']);
        $a = $this->photo($events->id);
        $b = $this->photo(null);
        $untouched = $this->photo($events->id);

        $this->actingAs(User::factory()->create(['role' => 'editor']))
            ->from('/admin/gallery?category='.$events->id)
            ->post('/admin/gallery/bulk-move', ['ids' => [$a->id, $b->id], 'category_id' => $unveiling->id])
            ->assertRedirect('/admin/gallery?category='.$events->id)
            ->assertSessionHas('success', 'Moved 2 photos to "Manifesto Unveiling".');

        $this->assertSame($unveiling->id, $a->fresh()->category_id);
        $this->assertSame($unveiling->id, $b->fresh()->category_id);
        $this->assertSame($events->id, $untouched->fresh()->category_id);
        $this->assertSame('manifesto-unveiling', $unveiling->slug);
    }

    public function test_moderators_cannot_move_photos(): void
    {
        $album = ImageCategory::create(['name' => 'Events']);
        $photo = $this->photo(null);

        $this->actingAs(User::factory()->create(['role' => 'moderator']))
            ->post('/admin/gallery/bulk-move', ['ids' => [$photo->id], 'category_id' => $album->id])
            ->assertForbidden();

        $this->assertNull($photo->fresh()->category_id);
    }

    public function test_requires_photos_and_an_album(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/gallery/bulk-move', ['ids' => [], 'category_id' => 999])
            ->assertSessionHasErrors(['ids', 'category_id']);
    }
}
