<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Distributor Dashboard</h1>
                <p class="text-gray-600 mt-2">Welcome back, {{ distributor.company_name }}</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Orders -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Total Orders</p>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.totalOrders }}</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Pending Orders -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Pending Orders</p>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.pendingOrders }}</p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-lg">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div v-if="$page.props.auth.user.role !== 'staff'" class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Total Revenue</p>
                            <p class="text-3xl font-bold text-gray-900">₱{{ Number(stats.totalRevenue).toLocaleString() }}</p>
                            <p class="text-xs text-gray-500 mt-1">This month: ₱{{ Number(stats.monthlyRevenue).toLocaleString() }}</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-lg">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Products & Inventory -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Products</p>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.totalProducts }}</p>
                            <p v-if="stats.lowStockCount > 0" class="text-xs text-orange-600 mt-1 font-semibold">
                                {{ stats.lowStockCount }} low stock items
                            </p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DSS Alerts Widget -->
            <div v-if="dss_alerts && (dss_alerts.expiry_alerts.length > 0 || dss_alerts.low_stock_alerts.length > 0)" class="bg-gradient-to-r from-orange-50 to-red-50 border-2 border-orange-200 rounded-xl shadow-md p-6 mb-8">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <h2 class="text-xl font-bold text-gray-900">⚠️ Decision Support Alerts</h2>
                    </div>
                    <span class="bg-orange-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                        {{ dss_alerts.expiry_alerts.length + dss_alerts.low_stock_alerts.length }} Alerts
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Expiry Alerts -->
                    <div v-if="dss_alerts.expiry_alerts.length > 0" class="bg-white rounded-lg p-4 shadow">
                        <h3 class="font-bold text-red-700 mb-3 flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Expiring Products ({{ dss_alerts.expiry_alerts.length }})
                        </h3>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            <div v-for="alert in dss_alerts.expiry_alerts.slice(0, 5)" :key="alert.id" 
                                 :class="['p-2 rounded border-l-4', alert.severity === 'critical' ? 'bg-red-50 border-red-500' : 'bg-yellow-50 border-yellow-500']">
                                <p class="font-semibold text-sm text-gray-900">{{ alert.product_name }}</p>
                                <p class="text-xs text-gray-600">Batch: {{ alert.batch_number || 'N/A' }} | Qty: {{ alert.quantity }}</p>
                                <p :class="['text-xs font-bold', alert.severity === 'critical' ? 'text-red-600' : 'text-yellow-600']">
                                    Expires in {{ alert.days_until_expiry }} days ({{ alert.expiry_date }})
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Alerts -->
                    <div v-if="dss_alerts.low_stock_alerts.length > 0" class="bg-white rounded-lg p-4 shadow">
                        <h3 class="font-bold text-orange-700 mb-3 flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Low Stock Products ({{ dss_alerts.low_stock_alerts.length }})
                        </h3>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            <div v-for="alert in dss_alerts.low_stock_alerts.slice(0, 5)" :key="alert.id" class="p-2 rounded bg-orange-50 border-l-4 border-orange-500">
                                <p class="font-semibold text-sm text-gray-900">{{ alert.product_name }}</p>
                                <p class="text-xs text-gray-600">Batch: {{ alert.batch_number || 'N/A' }}</p>
                                <p class="text-xs font-bold text-orange-600">
                                    Current: {{ alert.current_quantity }} | Reorder at: {{ alert.reorder_level }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <Link href="/owner/inventory" class="text-blue-600 hover:text-blue-700 font-semibold text-sm">
                        → View All Inventory
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Recent Orders -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md">
                        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-xl font-bold text-gray-900">Recent Orders</h2>
                            <Link href="/owner/orders" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                View All
                            </Link>
                        </div>
                        
                        <div v-if="recentOrders.length > 0" class="divide-y">
                            <Link 
                                v-for="order in recentOrders" 
                                :key="order.id"
                                :href="`/owner/orders/${order.id}`"
                                class="block p-6 hover:bg-blue-50 transition cursor-pointer"
                            >
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ order.order_number }}</p>
                                        <p class="text-sm text-gray-600">{{ order.customer?.name || 'Customer' }}</p>
                                    </div>
                                    <span 
                                        :class="{
                                            'bg-yellow-100 text-yellow-800': order.status === 'pending',
                                            'bg-blue-100 text-blue-800': order.status === 'approved',
                                            'bg-purple-100 text-purple-800': order.status === 'packed',
                                            'bg-indigo-100 text-indigo-800': order.status === 'shipped',
                                            'bg-green-100 text-green-800': order.status === 'delivered',
                                        }"
                                        class="px-3 py-1 rounded-full text-xs font-semibold capitalize"
                                    >
                                        {{ order.status }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center mt-3">
                                    <p class="text-sm text-gray-600">{{ order.items?.length || 0 }} items</p>
                                    <p class="font-bold text-gray-900">₱{{ Number(order.total_amount).toLocaleString() }}</p>
                                </div>
                            </Link>
                        </div>
                        
                        <div v-else class="p-12 text-center text-gray-500">
                            <svg class="h-16 w-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p>No orders yet</p>
                        </div>
                    </div>
                </div>

                <!-- DSS Alerts -->
                <div>
                    <div class="bg-white rounded-xl shadow-md">
                        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-xl font-bold text-gray-900">Alerts</h2>
                            <Link href="/owner/alerts" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                View All
                            </Link>
                        </div>
                        
                        <div v-if="unreadAlerts.length > 0" class="divide-y max-h-96 overflow-y-auto">
                            <div 
                                v-for="alert in unreadAlerts" 
                                :key="alert.id"
                                class="p-4 hover:bg-gray-50 transition"
                            >
                                <div class="flex items-start">
                                    <div 
                                        :class="{
                                            'bg-yellow-100': alert.severity === 'medium',
                                            'bg-red-100': alert.severity === 'high',
                                            'bg-orange-100': alert.severity === 'critical',
                                        }"
                                        class="p-2 rounded-lg mr-3 flex-shrink-0"
                                    >
                                        <svg class="h-4 w-4" :class="{
                                            'text-yellow-600': alert.severity === 'medium',
                                            'text-red-600': alert.severity === 'high',
                                            'text-orange-600': alert.severity === 'critical',
                                        }" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-gray-900 capitalize">{{ alert.type.replace('_', ' ') }}</p>
                                        <p class="text-xs text-gray-600 mt-1">{{ alert.message }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ formatDate(alert.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="p-12 text-center text-gray-500">
                            <svg class="h-16 w-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p>No alerts at the moment</p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-6 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl shadow-md p-6 text-white">
                        <h3 class="font-bold mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <Link href="/owner/products/create" class="block bg-white/20 hover:bg-white/30 transition px-4 py-3 rounded-lg font-medium">
                                + Add New Product
                            </Link>
                            <Link href="/owner/orders?status=pending" class="block bg-white/20 hover:bg-white/30 transition px-4 py-3 rounded-lg font-medium">
                                View Pending Orders
                            </Link>
                            <Link :href="`/owner/distributor/${distributor.id}/branches`" class="block bg-white/20 hover:bg-white/30 transition px-4 py-3 rounded-lg font-medium">
                                Manage Branches
                            </Link>
                            <Link href="/owner/inventory" class="block bg-white/20 hover:bg-white/30 transition px-4 py-3 rounded-lg font-medium">
                                Manage Inventory
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    distributor: Object,
    stats: Object,
    recentOrders: Array,
    dss_alerts: Object,
    unreadAlerts: Array,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
