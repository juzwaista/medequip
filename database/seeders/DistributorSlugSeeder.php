<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Distributor;
use Illuminate\Support\Str;

class DistributorSlugSeeder extends Seeder
{
    public function run()
    {
        $distributors = Distributor::whereNull('slug')->orWhere('slug', '')->get();
        
        foreach ($distributors as $distributor) {
            $baseSlug = Str::slug($distributor->company_name);
            $slug = $baseSlug . '-' . $distributor->id;
            
            $distributor->update(['slug' => $slug]);
            $this->command->info("Generated slug for {$distributor->company_name}: {$slug}");
        }
        
        $this->command->info("Processed {$distributors->count()} distributors");
    }
}
