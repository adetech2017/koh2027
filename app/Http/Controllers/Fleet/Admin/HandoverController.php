<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Assignment;
use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\Handover;
use App\Models\Fleet\HandoverPhoto;
use App\Models\Fleet\Vehicle;
use App\Services\Fleet\FleetFiles;
use App\Services\Fleet\HandoverService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HandoverController extends Controller
{
    public function __construct(private HandoverService $handovers, private FleetFiles $files) {}

    /**
     * Hand a vehicle to a beneficiary. This starts the assignment.
     */
    public function store(Request $request): JsonResponse
    {
        $this->decodeChecklist($request);
        $data = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:fleet_vehicles,id'],
            'beneficiary_id' => ['required', 'integer', 'exists:fleet_beneficiaries,id'],
        ] + $this->formRules(), $this->formMessages());

        $handover = $this->handovers->handOver(
            Vehicle::findOrFail($data['vehicle_id']),
            Beneficiary::findOrFail($data['beneficiary_id']),
            $data,
            auth('fleet_admin')->user(),
        );

        return response()->json(['handover' => $handover->toApi()], 201);
    }

    /**
     * Take a vehicle back. This ends the assignment.
     */
    public function storeReturn(Request $request, Assignment $assignment): JsonResponse
    {
        $this->decodeChecklist($request);
        $data = $request->validate([
            'vehicle_status' => ['required', Rule::in(['available', 'maintenance'])],
        ] + $this->formRules(), $this->formMessages());

        $handover = $this->handovers->receiveReturn($assignment, $data, auth('fleet_admin')->user(), $data['vehicle_status']);

        return response()->json(['handover' => $handover->toApi()], 201);
    }

    public function show(Handover $handover): JsonResponse
    {
        $handover->load('photos', 'officer', 'assignment.vehicle', 'assignment.beneficiary');

        return response()->json([
            'handover' => $handover->toApi() + [
                'vehicle' => $handover->assignment->vehicle->toApi(),
                'beneficiary' => $handover->assignment->beneficiary->toAdminApi(),
            ],
        ]);
    }

    public function receipt(Handover $handover): Response
    {
        $handover->load('photos', 'officer', 'assignment.vehicle', 'assignment.beneficiary');

        $dataUri = fn (string $path, string $mime) => "data:{$mime};base64,".base64_encode($this->files->disk()->get($path));

        return Pdf::loadView('fleet.pdf.handover', [
            'handover' => $handover,
            'vehicle' => $handover->assignment->vehicle,
            'beneficiary' => $handover->assignment->beneficiary,
            'officerSignature' => $dataUri($handover->officer_signature_path, 'image/png'),
            'signerSignature' => $dataUri($handover->signer_signature_path, 'image/png'),
            'photos' => $handover->photos->take(6)->map(fn (HandoverPhoto $photo) => $dataUri($photo->path, 'image/jpeg')),
        ])->setPaper('a4')->stream($handover->reference().'.pdf');
    }

    public function photo(Handover $handover, HandoverPhoto $photo): StreamedResponse
    {
        abort_unless($photo->handover_id === $handover->id, 404);

        return $this->files->disk()->response($photo->path, headers: ['Cache-Control' => 'private, max-age=86400']);
    }

    public function signature(Handover $handover, string $party): StreamedResponse
    {
        $path = $party === 'officer' ? $handover->officer_signature_path : $handover->signer_signature_path;

        return $this->files->disk()->response($path, headers: ['Cache-Control' => 'private, max-age=86400']);
    }

    private function formRules(): array
    {
        $rules = [
            'odometer_km' => ['required', 'integer', 'min:0', 'max:2000000'],
            'fuel_level' => ['required', Rule::in(array_keys(config('fleet.fuel_levels')))],
            'checklist' => ['required', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'signed_by_name' => ['required', 'string', 'max:255'],
            'officer_signature' => ['required', $this->signatureRule()],
            'signer_signature' => ['required', $this->signatureRule()],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'location_accuracy_m' => ['nullable', 'numeric', 'min:0'],
            // The admin app shrinks photos before upload; this is a ceiling, not the usual size
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['image', 'mimes:jpeg,png,webp', 'max:8192'],
        ];

        foreach (array_keys(config('fleet.handover_checklist')) as $item) {
            $rules["checklist.{$item}"] = ['required', 'array'];
            $rules["checklist.{$item}.status"] = ['required', Rule::in(['ok', 'issue', 'missing'])];
            $rules["checklist.{$item}.note"] = ['nullable', 'string', 'max:500', "required_unless:checklist.{$item}.status,ok"];
        }

        return $rules;
    }

    private function formMessages(): array
    {
        $messages = [
            'officer_signature.required' => 'The officer needs to sign.',
            'signer_signature.required' => 'The beneficiary (or their representative) needs to sign.',
        ];

        foreach (config('fleet.handover_checklist') as $item => $label) {
            $messages["checklist.{$item}.status.required"] = "Choose a condition for “{$label}”.";
            $messages["checklist.{$item}.note.required_unless"] = "Describe the problem with “{$label}”.";
        }

        return $messages;
    }

    private function signatureRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if (!FleetFiles::isPngDataUri($value)) {
                $fail('The signature could not be read. Clear it and sign again.');
            }
        };
    }

    /**
     * The form is multipart (it carries photos), so the checklist arrives as a JSON string.
     */
    private function decodeChecklist(Request $request): void
    {
        if (is_string($request->input('checklist'))) {
            $request->merge(['checklist' => json_decode($request->input('checklist'), true)]);
        }

        // Only the configured items are kept
        if (is_array($request->input('checklist'))) {
            $request->merge(['checklist' => array_intersect_key($request->input('checklist'), config('fleet.handover_checklist'))]);
        }
    }
}
