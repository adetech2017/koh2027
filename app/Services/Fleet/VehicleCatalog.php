<?php

namespace App\Services\Fleet;

use App\Models\Fleet\Vehicle;
use App\Models\Fleet\VehicleMake;
use App\Models\Fleet\VehicleModel;

/**
 * The makes and models offered in the vehicle form. Super admins maintain the list in fleet admin;
 * this is the starting list, common in Nigeria, loaded once by the migration that creates the tables.
 */
class VehicleCatalog
{
    /** make => [vehicle type => [models]] */
    public const DEFAULTS = [
        'Bajaj' => ['motorcycle' => ['Boxer', 'Discover', 'Pulsar'], 'tricycle' => ['RE']],
        'BMW' => ['car' => ['3 Series', '5 Series'], 'suv' => ['X3', 'X5']],
        'Chevrolet' => ['suv' => ['Equinox', 'Tahoe'], 'pickup' => ['Silverado']],
        'Ford' => ['car' => ['Focus', 'Fusion'], 'suv' => ['Edge', 'Escape', 'Explorer', 'Expedition'], 'bus' => ['Transit'], 'pickup' => ['F-150', 'Ranger']],
        'GAC' => ['suv' => ['GS4', 'GS8']],
        'Honda' => ['car' => ['Accord', 'City', 'Civic'], 'suv' => ['CR-V', 'HR-V', 'Pilot'], 'motorcycle' => ['Ace', 'CG125']],
        'Hyundai' => ['car' => ['Accent', 'Elantra', 'Sonata'], 'suv' => ['Creta', 'Palisade', 'Santa Fe', 'Tucson'], 'bus' => ['County', 'H-1']],
        'Innoson (IVM)' => ['car' => ['Fox'], 'suv' => ['G5', 'Umu']],
        'Isuzu' => ['pickup' => ['D-Max']],
        'JAC' => ['pickup' => ['T6']],
        'Jeep' => ['suv' => ['Grand Cherokee', 'Wrangler']],
        'Kia' => ['car' => ['Cerato', 'Optima', 'Rio'], 'suv' => ['Sorento', 'Sportage']],
        'Land Rover' => ['suv' => ['Defender', 'Discovery', 'Range Rover', 'Range Rover Sport']],
        'Lexus' => ['car' => ['ES', 'IS'], 'suv' => ['GX', 'LX', 'NX', 'RX']],
        'Mahindra' => ['tricycle' => ['Alfa']],
        'Mazda' => ['car' => ['Mazda3', 'Mazda6'], 'suv' => ['CX-5', 'CX-9'], 'pickup' => ['BT-50']],
        'Mercedes-Benz' => ['car' => ['C-Class', 'E-Class', 'S-Class'], 'suv' => ['G-Class', 'GLC', 'GLE', 'GLK', 'ML'], 'bus' => ['Sprinter']],
        'Mitsubishi' => ['suv' => ['Outlander', 'Pajero'], 'pickup' => ['L200']],
        'Nissan' => ['car' => ['Almera', 'Altima', 'Sentra', 'Sunny'], 'suv' => ['Pathfinder', 'Patrol', 'Rogue', 'X-Trail'], 'bus' => ['Civilian', 'Urvan'], 'pickup' => ['Frontier', 'Hardbody', 'Navara']],
        'Peugeot' => ['car' => ['301', '406', '508'], 'suv' => ['3008', '5008'], 'pickup' => ['Landtrek']],
        'Piaggio' => ['tricycle' => ['Ape']],
        'Suzuki' => ['suv' => ['Grand Vitara'], 'motorcycle' => ['AX100', 'GN125']],
        'Toyota' => [
            'car' => ['Avalon', 'Avensis', 'Camry', 'Corolla', 'Yaris'],
            'suv' => ['4Runner', 'Fortuner', 'Highlander', 'Land Cruiser', 'Land Cruiser Prado', 'RAV4', 'Sequoia', 'Venza'],
            'bus' => ['Coaster', 'Hiace'],
            'pickup' => ['Hilux', 'Tacoma', 'Tundra'],
        ],
        'TVS' => ['motorcycle' => ['Apache', 'Star HLX'], 'tricycle' => ['King']],
        'Volkswagen' => ['car' => ['Golf', 'Jetta', 'Passat'], 'suv' => ['Tiguan', 'Touareg'], 'bus' => ['Crafter', 'Transporter']],
        'Yamaha' => ['motorcycle' => ['Crux', 'YBR125']],
    ];

    public static function seedDefaults(): void
    {
        foreach (self::DEFAULTS as $makeName => $types) {
            $make = VehicleMake::firstOrCreate(['name' => $makeName]);
            foreach ($types as $type => $models) {
                foreach ($models as $model) {
                    $make->models()->firstOrCreate(['name' => $model], ['vehicle_type' => $type]);
                }
            }
        }
    }

    /**
     * Adds every make and model already recorded on a vehicle, so existing vehicles stay valid when edited.
     * Spellings that differ only in case ("toyota", "COROLLA") are moved onto the listed name.
     */
    public static function absorbExistingVehicles(): void
    {
        $makes = VehicleMake::with('models')->get()->keyBy(fn ($make) => mb_strtolower($make->name));

        $combinations = Vehicle::withTrashed()->select('make', 'model', 'vehicle_type')->distinct()->get();

        foreach ($combinations as $row) {
            $makeName = trim((string) $row->make);
            $modelName = trim((string) $row->model);
            if ($makeName === '' || $modelName === '') {
                continue;
            }

            $make = $makes[mb_strtolower($makeName)] ??= VehicleMake::create(['name' => $makeName])->setRelation('models', collect());

            $model = $make->models->first(fn (VehicleModel $m) => mb_strtolower($m->name) === mb_strtolower($modelName));
            if (!$model) {
                $model = $make->models()->create(['name' => $modelName, 'vehicle_type' => $row->vehicle_type]);
                $make->models->push($model);
            }

            if ($row->make !== $make->name || $row->model !== $model->name) {
                Vehicle::withTrashed()->where('make', $row->make)->where('model', $row->model)
                    ->update(['make' => $make->name, 'model' => $model->name]);
            }
        }
    }
}
