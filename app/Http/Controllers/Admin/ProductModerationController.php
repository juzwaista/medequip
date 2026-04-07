<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductModerationController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $filter = $request->query('filter', 'all');

        $query = Product::query()
            ->withTrashed()
            ->with(['distributor:id,company_name,slug', 'category:id,name']);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like, $search) {
                $q->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like);
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if ($filter === 'active') {
            $query->where('is_active', true)->whereNull('deleted_at');
        } elseif ($filter === 'inactive') {
            $query->where('is_active', false)->whereNull('deleted_at');
        } elseif ($filter === 'deleted') {
            $query->onlyTrashed();
        }

        $products = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
            ],
            'filterOptions' => [
                ['value' => 'all', 'label' => 'All'],
                ['value' => 'active', 'label' => 'Active in catalog'],
                ['value' => 'inactive', 'label' => 'Inactive (hidden)'],
                ['value' => 'deleted', 'label' => 'Soft-deleted'],
            ],
        ]);
    }

    public function deactivate(Product $product)
    {
        if ($product->trashed()) {
            return back()->with('error', 'This listing is already removed.');
        }

        $product->update(['is_active' => false]);

        return back()->with('success', 'Listing hidden from the public catalog. The seller can still see it in their inventory.');
    }

    public function softDelete(Product $product)
    {
        if ($product->trashed()) {
            return back()->with('error', 'This listing is already removed.');
        }

        $product->delete();

        return back()->with('success', 'Product removed from the catalog (soft delete).');
    }
}
