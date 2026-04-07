<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Medicines' => [
                'desc' => 'Pharmaceuticals, OTC and prescription medicines',
                'children' => [
                    'Over-the-Counter (OTC)' => 'Medicines sold without prescription where regulations allow',
                    'Prescription Medicines' => 'Medicines that require a valid prescription',
                    'Vitamins & Supplements' => 'Nutritional supplements',
                ],
            ],
            'Medical Devices' => [
                'desc' => 'Diagnostic tools, monitors, respiratory equipment, and sterilizers',
                'children' => [
                    'Diagnostics' => 'BP monitors, thermometers, stethoscopes, and otoscopes',
                    'Patient Monitors' => 'Pulse oximeters, ECG machines, vital sign monitors',
                    'Respiratory' => 'Nebulizers, oxygen concentrators, and CPAP machines',
                    'Sterilizers & Autoclaves' => 'Autoclaves, UV sterilizers, and disinfection systems',
                ],
            ],
            'Surgical & Dental' => [
                'desc' => 'Precision tools for surgical, orthopedic, and dental procedures',
                'children' => [
                    'General Surgery' => 'Scalpels, scissors, forceps, retractors',
                    'Dental Instruments' => 'Extraction forceps, mirrors, scalers',
                    'Sutures & Wound Closure' => 'Surgical sutures, needles, staples',
                ],
            ],
            'Protective Gear (PPE)' => [
                'desc' => 'Masks, gloves, gowns, and eyewear for healthcare safety',
                'children' => [
                    'Face Masks & Shields' => 'Surgical masks, N95 respirators, and face shields',
                    'Gloves' => 'Latex, nitrile, and vinyl examination gloves',
                    'Gowns & Coveralls' => 'Isolation gowns, surgical gowns, and coveralls',
                    'Eyewear' => 'Safety goggles, face shields, and protective eyewear',
                ],
            ],
            'Wound Care & First Aid' => [
                'desc' => 'Bandages, first aid kits, emergency supplies, and antiseptics',
                'children' => [
                    'Bandages & Dressings' => 'Bandages, gauze, wound dressings, and tapes',
                    'First Aid Kits' => 'Pre-assembled kits for home, office, or field use',
                    'Emergency & Trauma' => 'Splints, tourniquets, and burn care products',
                    'Antiseptics & Disinfectants' => 'Alcohol, povidone-iodine, hand sanitizers, and wound cleansers',
                ],
            ],
            'Everyday Supplies' => [
                'desc' => 'Syringes, IV sets, and other single-use medical supplies',
                'children' => [
                    'Syringes & Needles' => 'Disposable syringes, hypodermic needles, safety needles',
                    'IV Sets & Catheters' => 'IV sets, catheters, cannulas, and infusion pumps',
                    'Cotton, Gauze & Tape' => 'Cotton balls, gauze pads, medical tape, and related supplies',
                    'Incontinence Care' => 'Adult diapers, underpads, catheter supplies',
                ],
            ],
            'Lab & Testing' => [
                'desc' => 'Test kits, specimen collection, and lab equipment',
                'children' => [
                    'Test Kits' => 'Rapid test kits, reagent strips, diagnostic panels',
                    'Specimen Collection' => 'Blood collection tubes, swabs, sample containers',
                    'Lab Equipment' => 'Microscopes, centrifuges, analyzers',
                    'Lab Consumables' => 'Slides, petri dishes, pipette tips',
                ],
            ],
            'Mobility & Furniture' => [
                'desc' => 'Wheelchairs, hospital beds, braces, and medical carts',
                'children' => [
                    'Wheelchairs & Walkers' => 'Wheelchairs, walkers, crutches, and canes',
                    'Braces & Supports' => 'Therapy bands, braces, and exercise equipment',
                    'Hospital Beds' => 'Manual, semi-electric, and fully electric hospital beds',
                    'Medical Carts & Tables' => 'Crash carts, trolleys, exam tables, and procedure chairs',
                ],
            ],
        ];

        foreach ($tree as $parentName => $data) {
            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($parentName)],
                ['name' => $parentName, 'description' => $data['desc']]
            );

            foreach ($data['children'] as $childName => $childDesc) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($childName)],
                    ['name' => $childName, 'description' => $childDesc, 'parent_id' => $parent->id]
                );
            }
        }
    }
}
