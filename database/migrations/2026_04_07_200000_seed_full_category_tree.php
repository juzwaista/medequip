<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $tree = [
            'Medical Equipment' => [
                'desc' => 'Devices and machines used for diagnosis, treatment, and monitoring',
                'children' => [
                    'Diagnostic Equipment' => 'Stethoscopes, otoscopes, blood pressure monitors',
                    'Patient Monitors' => 'Pulse oximeters, ECG machines, vital sign monitors',
                    'Imaging Equipment' => 'Ultrasound, X-ray, and portable imaging devices',
                    'Respiratory Equipment' => 'Nebulizers, oxygen concentrators, CPAP machines',
                    'Sterilization Equipment' => 'Autoclaves, UV sterilizers, disinfection systems',
                ],
            ],
            'Surgical Instruments' => [
                'desc' => 'Precision tools for surgical and clinical procedures',
                'children' => [
                    'General Surgery' => 'Scalpels, scissors, forceps, retractors',
                    'Orthopedic Instruments' => 'Bone saws, drills, fixation devices',
                    'Dental Instruments' => 'Extraction forceps, mirrors, scalers',
                    'Suturing Supplies' => 'Surgical sutures, needles, staples',
                ],
            ],
            'Personal Protective Equipment' => [
                'desc' => 'Safety gear for healthcare workers and patients',
                'children' => [
                    'Face Masks' => 'Surgical masks, N95 respirators, face shields',
                    'Gloves' => 'Latex, nitrile, and vinyl examination gloves',
                    'Protective Gowns' => 'Isolation gowns, surgical gowns, coveralls',
                    'Eye Protection' => 'Safety goggles, face shields, protective eyewear',
                ],
            ],
            'Laboratory Supplies' => [
                'desc' => 'Equipment and consumables for medical laboratories',
                'children' => [
                    'Test Kits' => 'Rapid test kits, reagent strips, diagnostic panels',
                    'Specimen Collection' => 'Blood collection tubes, swabs, sample containers',
                    'Lab Instruments' => 'Microscopes, centrifuges, analyzers',
                    'Lab Consumables' => 'Slides, petri dishes, pipette tips',
                ],
            ],
            'Patient Care' => [
                'desc' => 'Supplies for daily patient care and recovery',
                'children' => [
                    'Wound Care' => 'Bandages, gauze, wound dressings, antiseptics',
                    'Mobility Aids' => 'Wheelchairs, walkers, crutches, canes',
                    'Rehabilitation' => 'Therapy bands, exercise equipment, braces',
                    'Incontinence Care' => 'Adult diapers, underpads, catheter supplies',
                ],
            ],
            'Consumables & Disposables' => [
                'desc' => 'Single-use medical supplies',
                'children' => [
                    'Syringes & Needles' => 'Disposable syringes, hypodermic needles, safety needles',
                    'IV Supplies' => 'IV sets, catheters, cannulas, infusion pumps',
                    'Tubing & Connectors' => 'Medical tubing, adapters, stopcocks',
                    'Disinfectants & Sanitizers' => 'Alcohol, povidone-iodine, hand sanitizers',
                ],
            ],
            'Hospital Furniture' => [
                'desc' => 'Furniture and fixtures for clinics and hospitals',
                'children' => [
                    'Hospital Beds' => 'Manual, semi-electric, and fully electric hospital beds',
                    'Exam & Treatment Tables' => 'Examination tables, procedure chairs',
                    'Medical Carts & Storage' => 'Crash carts, instrument trolleys, storage cabinets',
                ],
            ],
            'First Aid' => [
                'desc' => 'Emergency and first response supplies',
                'children' => [
                    'First Aid Kits' => 'Pre-assembled kits for home, office, or field use',
                    'Emergency Supplies' => 'Splints, tourniquets, burn care products',
                    'AED & CPR' => 'Automated external defibrillators, CPR masks, training aids',
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

    public function down(): void
    {
        // Only delete categories created by this migration (not Medicine, which has its own migration)
        $parentSlugs = [
            'medical-equipment', 'surgical-instruments', 'personal-protective-equipment',
            'laboratory-supplies', 'patient-care', 'consumables-disposables',
            'hospital-furniture', 'first-aid',
        ];

        foreach ($parentSlugs as $slug) {
            $parent = Category::where('slug', $slug)->first();
            if ($parent) {
                Category::where('parent_id', $parent->id)->delete();
                $parent->delete();
            }
        }
    }
};
