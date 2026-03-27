<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        $parent = Category::firstOrCreate(
            ['slug' => 'medicine'],
            [
                'name' => 'Medicine',
                'description' => 'Pharmaceuticals, OTC and prescription medicines',
            ]
        );

        $children = [
            ['name' => 'Over-the-Counter (OTC)', 'description' => 'Medicines sold without prescription where regulations allow'],
            ['name' => 'Prescription Medicines', 'description' => 'Medicines that require a valid prescription'],
            ['name' => 'Vitamins & Supplements', 'description' => 'Nutritional supplements'],
        ];

        foreach ($children as $row) {
            Category::firstOrCreate(
                ['slug' => Str::slug($row['name'])],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'parent_id' => $parent->id,
                ]
            );
        }
    }

    public function down(): void
    {
        $parent = Category::where('slug', 'medicine')->first();
        if ($parent) {
            Category::where('parent_id', $parent->id)->delete();
            $parent->delete();
        }
    }
};
