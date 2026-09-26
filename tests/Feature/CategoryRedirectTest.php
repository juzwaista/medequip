<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /category/{slug} used to render an Inertia page ("Products/Category") that was never built.
 * It now sends visitors to the catalog, which already filters by category and its subcategories.
 */
class CategoryRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_slug_redirects_to_the_catalog_filter(): void
    {
        $category = Category::create(['name' => 'Redirect Test', 'slug' => 'redirect-test']);

        $this->get('/category/redirect-test')
            ->assertRedirect(route('products.index', ['category' => $category->id]));
    }

    public function test_search_and_sort_are_kept_when_redirecting(): void
    {
        $category = Category::create(['name' => 'Redirect Test', 'slug' => 'redirect-test']);

        $this->get('/category/redirect-test?search=glove&sort=price_low')
            ->assertRedirect(route('products.index', ['category' => $category->id, 'search' => 'glove', 'sort' => 'price_low']));
    }

    public function test_unknown_category_slug_is_a_404(): void
    {
        $this->get('/category/does-not-exist')->assertNotFound();
    }
}
