<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

/**
 * Restructure the category tree to be friendlier for customers.
 *
 * Key changes:
 * - "Consumables & Disposables" → "Everyday Supplies"
 * - "Personal Protective Equipment" → "Protective Gear (PPE)"
 * - "Hospital Furniture" and "Patient Care > Mobility/Rehab" merge → "Mobility & Furniture"
 * - "First Aid" + "Patient Care > Wound Care" + "Consumables > Disinfectants" merge → "Wound Care & First Aid"
 * - "Surgical Instruments" → "Surgical & Dental"
 * - Child count drops from 34 → 28
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename parents
        $this->renameCategory('personal-protective-equipment', 'Protective Gear (PPE)', 'Masks, gloves, gowns, and eyewear for healthcare safety');
        $this->renameCategory('consumables-disposables', 'Everyday Supplies', 'Syringes, IV sets, and other single-use medical supplies');
        $this->renameCategory('surgical-instruments', 'Surgical & Dental', 'Precision tools for surgical, orthopedic, and dental procedures');
        $this->renameCategory('hospital-furniture', 'Mobility & Furniture', 'Wheelchairs, hospital beds, braces, and medical carts');
        $this->renameCategory('first-aid', 'Wound Care & First Aid', 'Bandages, first aid kits, emergency supplies, and antiseptics');
        $this->renameCategory('laboratory-supplies', 'Lab & Testing', 'Test kits, specimen collection, and lab equipment');
        $this->renameCategory('patient-care', null, null); // Will be deleted after moving children
        $this->renameCategory('medical-equipment', 'Medical Devices', 'Diagnostic tools, monitors, respiratory equipment, and sterilizers');

        // 2. Get parent references
        $devices = Category::where('slug', 'medical-equipment')->first();
        $surgDental = Category::where('slug', 'surgical-instruments')->first();
        $ppe = Category::where('slug', 'personal-protective-equipment')->first();
        $woundCare = Category::where('slug', 'first-aid')->first();
        $everyday = Category::where('slug', 'consumables-disposables')->first();
        $lab = Category::where('slug', 'laboratory-supplies')->first();
        $mobilityFurn = Category::where('slug', 'hospital-furniture')->first();
        $patientCare = Category::where('slug', 'patient-care')->first();
        $medicine = Category::where('slug', 'medicine')->first();

        // 3. Move children between parents

        // -- Move "Wound Care" from Patient Care → Wound Care & First Aid
        if ($patientCare && $woundCare) {
            $wc = Category::where('slug', 'wound-care')->where('parent_id', $patientCare->id)->first();
            if ($wc) {
                $wc->update(['parent_id' => $woundCare->id, 'name' => 'Bandages & Dressings', 'description' => 'Bandages, gauze, wound dressings, and tapes']);
                $wc->update(['slug' => Str::slug('Bandages & Dressings')]);
            }
        }

        // -- Move "Disinfectants & Sanitizers" from Everyday Supplies → Wound Care & First Aid
        if ($everyday && $woundCare) {
            $dis = Category::where('slug', 'disinfectants-sanitizers')->where('parent_id', $everyday->id)->first();
            if ($dis) {
                $dis->update(['parent_id' => $woundCare->id, 'name' => 'Antiseptics & Disinfectants', 'description' => 'Alcohol, povidone-iodine, hand sanitizers, and wound cleansers']);
                $dis->update(['slug' => Str::slug('Antiseptics & Disinfectants')]);
            }
        }

        // -- Move "Mobility Aids" and "Rehabilitation" from Patient Care → Mobility & Furniture
        if ($patientCare && $mobilityFurn) {
            $mob = Category::where('slug', 'mobility-aids')->where('parent_id', $patientCare->id)->first();
            if ($mob) {
                $mob->update(['parent_id' => $mobilityFurn->id, 'name' => 'Wheelchairs & Walkers', 'description' => 'Wheelchairs, walkers, crutches, and canes']);
                $mob->update(['slug' => Str::slug('Wheelchairs & Walkers')]);
            }
            $rehab = Category::where('slug', 'rehabilitation')->where('parent_id', $patientCare->id)->first();
            if ($rehab) {
                $rehab->update(['parent_id' => $mobilityFurn->id, 'name' => 'Braces & Supports', 'description' => 'Therapy bands, braces, and exercise equipment']);
                $rehab->update(['slug' => Str::slug('Braces & Supports')]);
            }
        }

        // -- Move "Incontinence Care" from Patient Care → Everyday Supplies
        if ($patientCare && $everyday) {
            $inc = Category::where('slug', 'incontinence-care')->where('parent_id', $patientCare->id)->first();
            if ($inc) {
                $inc->update(['parent_id' => $everyday->id]);
            }
        }

        // -- Rename existing children for friendlier names
        $this->renameCategory('face-masks', 'Face Masks & Shields', 'Surgical masks, N95 respirators, and face shields');
        $this->renameCategory('protective-gowns', 'Gowns & Coveralls', 'Isolation gowns, surgical gowns, and coveralls');
        $this->renameCategory('eye-protection', 'Eyewear', 'Safety goggles, face shields, and protective eyewear');
        $this->renameCategory('diagnostic-equipment', 'Diagnostics', 'BP monitors, thermometers, stethoscopes, and otoscopes');
        $this->renameCategory('respiratory-equipment', 'Respiratory', 'Nebulizers, oxygen concentrators, and CPAP machines');
        $this->renameCategory('sterilization-equipment', 'Sterilizers & Autoclaves', 'Autoclaves, UV sterilizers, and disinfection systems');
        $this->renameCategory('iv-supplies', 'IV Sets & Catheters', 'IV sets, catheters, cannulas, and infusion pumps');
        $this->renameCategory('lab-instruments', 'Lab Equipment', 'Microscopes, centrifuges, and analyzers');
        $this->renameCategory('suturing-supplies', 'Sutures & Wound Closure', 'Surgical sutures, needles, and staples');
        $this->renameCategory('exam-treatment-tables', 'Medical Carts & Tables', null);
        $this->renameCategory('emergency-supplies', 'Emergency & Trauma', 'Splints, tourniquets, and burn care products');
        $this->renameCategory('aed-cpr', null, null); // Keep as-is or remove if empty

        // -- Merge "Medical Carts & Storage" and "Exam & Treatment Tables" into one
        if ($mobilityFurn) {
            $carts = Category::where('slug', 'medical-carts-storage')->where('parent_id', $mobilityFurn->id)->first();
            $tables = Category::where('slug', 'exam-treatment-tables')->where('parent_id', $mobilityFurn->id)->first();
            if ($carts && $tables) {
                // Move products from tables to carts, then delete tables
                \DB::table('products')->where('category_id', $tables->id)->update(['category_id' => $carts->id]);
                $carts->update(['name' => 'Medical Carts & Tables', 'description' => 'Crash carts, trolleys, exam tables, and procedure chairs']);
                $carts->update(['slug' => Str::slug('Medical Carts & Tables')]);
                $tables->delete();
            }
        }

        // -- Remove "Tubing & Connectors" (too granular — merge into IV Sets & Catheters)
        if ($everyday) {
            $tubing = Category::where('slug', 'tubing-connectors')->where('parent_id', $everyday->id)->first();
            $iv = Category::where('slug', 'iv-supplies')->first()
                ?? Category::where('slug', Str::slug('IV Sets & Catheters'))->first();
            if ($tubing && $iv) {
                \DB::table('products')->where('category_id', $tubing->id)->update(['category_id' => $iv->id]);
                $tubing->delete();
            }
        }

        // -- Remove "Imaging Equipment" (too specialized for this catalog)
        if ($devices) {
            $imaging = Category::where('slug', 'imaging-equipment')->where('parent_id', $devices->id)->first();
            if ($imaging) {
                $diag = Category::where('slug', 'diagnostic-equipment')->first()
                    ?? Category::where('slug', Str::slug('Diagnostics'))->first();
                if ($diag) {
                    \DB::table('products')->where('category_id', $imaging->id)->update(['category_id' => $diag->id]);
                }
                $imaging->delete();
            }
        }

        // -- Remove "Orthopedic Instruments" (merge into General Surgery)
        if ($surgDental) {
            $ortho = Category::where('slug', 'orthopedic-instruments')->where('parent_id', $surgDental->id)->first();
            $genSurg = Category::where('slug', 'general-surgery')->where('parent_id', $surgDental->id)->first();
            if ($ortho && $genSurg) {
                \DB::table('products')->where('category_id', $ortho->id)->update(['category_id' => $genSurg->id]);
                $ortho->delete();
            }
        }

        // -- Delete the now-empty Patient Care parent (all children moved elsewhere)
        if ($patientCare) {
            $remaining = Category::where('parent_id', $patientCare->id)->count();
            if ($remaining === 0) {
                \DB::table('products')->where('category_id', $patientCare->id)->update(['category_id' => $everyday?->id ?? $patientCare->id]);
                $patientCare->delete();
            }
        }

        // -- Rename AED & CPR to just "AED & CPR Supplies" and keep under Wound Care & First Aid
        if ($woundCare) {
            $aed = Category::where('slug', 'aed-cpr')->first();
            if ($aed) {
                $aed->update(['parent_id' => $woundCare->id]);
            }
        }

        // -- Add "Cotton, Gauze & Tape" under Everyday Supplies if it doesn't exist
        if ($everyday) {
            Category::firstOrCreate(
                ['slug' => Str::slug('Cotton, Gauze & Tape')],
                ['name' => 'Cotton, Gauze & Tape', 'description' => 'Cotton balls, gauze pads, medical tape, and related supplies', 'parent_id' => $everyday->id]
            );
        }
    }

    public function down(): void
    {
        // This migration is complex to fully reverse.
        // Re-running the original seed migration + seeder would restore the old tree.
    }

    private function renameCategory(string $slug, ?string $newName, ?string $newDesc): void
    {
        $cat = Category::where('slug', $slug)->first();
        if (! $cat) {
            return;
        }

        $updates = [];
        if ($newName !== null) {
            $updates['name'] = $newName;
        }
        if ($newDesc !== null) {
            $updates['description'] = $newDesc;
        }
        if (! empty($updates)) {
            $cat->update($updates);
        }
    }
};
