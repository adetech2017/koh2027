@php
    $isReturn = $handover->type === 'return';
    $statusLabels = ['ok' => 'OK', 'issue' => 'Issue', 'missing' => 'Missing'];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $handover->reference() }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1e293b; }
        h1 { font-size: 17px; margin: 0; color: #003D82; }
        h2 { font-size: 12px; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #cbd5e1; color: #003D82; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 4px 6px; vertical-align: top; text-align: left; }
        .grid td { width: 25%; }
        .label { color: #64748b; font-size: 9px; text-transform: uppercase; letter-spacing: .03em; }
        .checklist th { background: #f1f5f9; font-size: 9.5px; }
        .checklist td, .checklist th { border: 1px solid #e2e8f0; }
        .issue { color: #b45309; font-weight: bold; }
        .missing { color: #b91c1c; font-weight: bold; }
        .sig { height: 70px; }
        .sig-box { border-bottom: 1px solid #94a3b8; height: 74px; }
        .photos td { width: 33%; padding: 3px; }
        .photos img { width: 100%; height: 130px; object-fit: cover; }
        .header td { padding: 0; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>Vehicle {{ $isReturn ? 'Return' : 'Handover' }} Receipt</h1>
                <div class="muted">KOH 2027 Campaign · Fleet Office</div>
            </td>
            <td style="text-align: right;">
                <div style="font-size: 14px; font-weight: bold;">{{ $handover->reference() }}</div>
                <div class="muted">{{ $handover->occurred_at->timezone('Africa/Lagos')->format('j M Y, g:i a') }}</div>
            </td>
        </tr>
    </table>

    <h2>Vehicle</h2>
    <table class="grid">
        <tr>
            <td><div class="label">Plate number</div>{{ $vehicle->displayPlate() }}</td>
            <td><div class="label">Vehicle</div>{{ $vehicle->make }} {{ $vehicle->model }}{{ $vehicle->year ? ' ('.$vehicle->year.')' : '' }}</td>
            <td><div class="label">Colour</div>{{ $vehicle->colour ?: '—' }}</td>
            <td><div class="label">Chassis number</div>{{ $vehicle->chassis_number ?: '—' }}</td>
        </tr>
        <tr>
            <td><div class="label">Odometer</div>{{ number_format($handover->odometer_km) }} km</td>
            <td><div class="label">Fuel</div>{{ config('fleet.fuel_levels')[$handover->fuel_level] ?? $handover->fuel_level }}</td>
            <td colspan="2"><div class="label">Branding</div>{{ $vehicle->branding ?: '—' }}</td>
        </tr>
    </table>

    <h2>Beneficiary</h2>
    <table class="grid">
        <tr>
            <td><div class="label">Name</div>{{ $beneficiary->full_name }}</td>
            <td><div class="label">Phone</div>{{ $beneficiary->phone }}</td>
            <td><div class="label">LGA / Ward</div>{{ $beneficiary->lga ?: '—' }}{{ $beneficiary->ward ? ' / '.$beneficiary->ward : '' }}</td>
            <td><div class="label">ID</div>{{ $beneficiary->id_number ? (config('fleet.id_types')[$beneficiary->id_type] ?? $beneficiary->id_type).' '.$beneficiary->id_number : '—' }}</td>
        </tr>
    </table>

    <h2>Condition</h2>
    <table class="checklist">
        <tr><th style="width: 38%;">Item</th><th style="width: 12%;">Condition</th><th>Notes</th></tr>
        @foreach (config('fleet.handover_checklist') as $key => $label)
            @php $item = $handover->checklist[$key] ?? ['status' => null, 'note' => null]; @endphp
            <tr>
                <td>{{ $label }}</td>
                <td class="{{ $item['status'] }}">{{ $statusLabels[$item['status']] ?? '—' }}</td>
                <td>{{ $item['note'] ?? '' }}</td>
            </tr>
        @endforeach
    </table>
    @if ($handover->notes)
        <p><span class="label">Other notes</span><br>{{ $handover->notes }}</p>
    @endif

    @if ($handover->latitude !== null)
        <p class="muted">
            Recorded at {{ number_format($handover->latitude, 5) }}, {{ number_format($handover->longitude, 5) }}
            @if ($handover->location_accuracy_m) (±{{ $handover->location_accuracy_m }} m) @endif
        </p>
    @endif

    <h2>Signatures</h2>
    <p>
        {{ $isReturn
            ? 'The vehicle above was returned to the KOH 2027 Fleet Office in the condition recorded on this receipt.'
            : 'The vehicle above was received in the condition recorded on this receipt. It remains campaign property, is to be used for campaign purposes, kept in good condition, evaluated by video every two weeks, and returned when the campaign committee asks.' }}
    </p>
    <table>
        <tr>
            <td style="width: 50%; padding-right: 20px;">
                <div class="sig-box"><img class="sig" src="{{ $signerSignature }}"></div>
                <div>{{ $handover->signed_by_name }}</div>
                <div class="label">{{ $isReturn ? 'Returned by' : 'Received by' }}{{ $handover->signed_by_name !== $beneficiary->full_name ? ' (for '.$beneficiary->full_name.')' : '' }}</div>
            </td>
            <td style="width: 50%; padding-left: 20px;">
                <div class="sig-box"><img class="sig" src="{{ $officerSignature }}"></div>
                <div>{{ $handover->officer?->name ?? 'Fleet officer' }}</div>
                <div class="label">Fleet officer</div>
            </td>
        </tr>
    </table>

    @if ($photos->isNotEmpty())
        <h2>Photos</h2>
        <table class="photos">
            @foreach ($photos->chunk(3) as $row)
                <tr>
                    @foreach ($row as $photo)
                        <td><img src="{{ $photo }}"></td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
