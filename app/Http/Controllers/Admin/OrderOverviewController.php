<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderOverviewController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $status = $request->query('status', 'all');

        $query = Order::with([
            'customer:id,name,email',
            'distributor:id,company_name',
        ])
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('order_number', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($q3) => $q3->where('name', 'like', "%{$search}%"))
                ->orWhereHas('distributor', fn ($q3) => $q3->where('company_name', 'like', "%{$search}%"))
            ))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at');

        $orders = $query->paginate(20)->withQueryString();

        $statusCounts = [
            'all' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'accepted' => Order::where('status', 'accepted')->count(),
            'packed' => Order::where('status', 'packed')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function show(Order $order)
    {
        $order->load([
            'customer:id,name,email,phone_number',
            'distributor:id,user_id,company_name,contact_number,address',
            'distributor.user:id,phone_number',
            'items.product:id,name',
            'items.productVariation',
            'invoice.payments',
            'delivery.courier.user:id,name,phone_number',
        ]);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
        ]);
    }
}
