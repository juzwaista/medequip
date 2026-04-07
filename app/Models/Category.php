<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
    ];

    /**
     * Get the parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get subcategories
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get products in this category
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * IDs for the Medicine parent and its subcategories (slug: medicine).
     */
    public static function medicineTreeIds(): array
    {
        $parent = static::where('slug', 'medicine')->first();
        if (! $parent) {
            return [];
        }

        $childIds = static::where('parent_id', $parent->id)->pluck('id')->all();

        return array_values(array_unique(array_merge([$parent->id], $childIds)));
    }

    /**
     * Only medicine-category products may require a prescription flag.
     */
    public static function normalizeRequiresPrescription(int $categoryId, bool $checked): bool
    {
        $ids = static::medicineTreeIds();

        if ($ids === [] || ! in_array($categoryId, $ids, true)) {
            return false;
        }

        return $checked;
    }

    /**
     * This category ID plus every descendant (any depth), for listing products in a subtree.
     */
    public static function descendantIdsIncludingSelf(int $categoryId): array
    {
        $ids = [];
        $queue = [$categoryId];

        while ($queue !== []) {
            $id = (int) array_shift($queue);
            if (in_array($id, $ids, true)) {
                continue;
            }
            $ids[] = $id;
            $children = static::query()->where('parent_id', $id)->pluck('id')->all();
            foreach ($children as $childId) {
                $queue[] = (int) $childId;
            }
        }

        return $ids;
    }
}
