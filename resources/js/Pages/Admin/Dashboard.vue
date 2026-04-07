<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                <p class="text-gray-600 mt-2">Overview of platform activity and key metrics.</p>
            </div>

            <!-- Platform Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Users</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.totalUsers }}</p>
                    <p v-if="financial.newUsersThisMonth" class="text-xs text-emerald-600 font-medium mt-1">+{{ financial.newUsersThisMonth }} this month</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Active Shops</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.totalDistributors }}</p>
                </div>
                <Link href="/admin/users?shop_status=pending" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:border-amber-200 hover:shadow transition group">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide group-hover:text-amber-600">Pending Verification</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.pendingVerifications }}</p>
                    <p class="text-xs text-blue-600 font-medium mt-1">View &rarr;</p>
                </Link>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Products</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ stats.totalProducts }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Orders</p>
                    <p class="text-2xl font-bold text-purple-600 mt-1">{{ stats.totalOrders }}</p>
                    <p v-if="financial.ordersThisMonth" class="text-xs text-emerald-600 font-medium mt-1">{{ financial.ordersThisMonth }} this month</p>
                </div>
            </div>

            <!-- Financial + Order Trend Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Financial Summary -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-bold text-gray-900 mb-4">Financial Summary</h2>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Total Revenue</dt>
                            <dd class="text-sm font-bold text-gray-900">{{ currency(financial.totalRevenue) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Platform Fees Earned</dt>
                            <dd class="text-sm font-bold text-emerald-600">{{ currency(financial.platformFees) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Payments held</dt>
                            <dd class="text-sm font-bold text-amber-600">{{ currency(financial.escrowHeld) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Order Trend (7 days) -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-bold text-gray-900 mb-4">Orders — Last 7 Days</h2>
                    <div v-if="orderTrend.length" class="flex items-end gap-2 h-32">
                        <div
                            v-for="day in orderTrend"
                            :key="day.label"
                            class="flex-1 flex flex-col items-center gap-1"
                        >
                            <span class="text-[10px] font-bold text-gray-700">{{ day.count }}</span>
                            <div
                                class="w-full rounded-t bg-blue-500 transition-all"
                                :style="{ height: barHeight(day.count) + '%', minHeight: '4px' }"
                            ></div>
                            <span class="text-[10px] text-gray-400 font-medium">{{ day.label.split(' ')[1] }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 text-center py-8">No data yet.</p>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Link href="/admin/users" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:border-blue-200 hover:shadow transition flex items-center gap-3">
                    <div class="bg-blue-50 text-blue-600 p-2 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-700">Users & Shops</span>
                </Link>
                <Link href="/admin/orders" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:border-purple-200 hover:shadow transition flex items-center gap-3">
                    <div class="bg-purple-50 text-purple-600 p-2 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-700">All Orders</span>
                </Link>
                <Link href="/admin/reports" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:border-rose-200 hover:shadow transition flex items-center gap-3">
                    <div class="bg-rose-50 text-rose-600 p-2 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-700">Reports Hub</span>
                </Link>
                <Link href="/admin/couriers" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:border-teal-200 hover:shadow transition flex items-center gap-3">
                    <div class="bg-teal-50 text-teal-600 p-2 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-700">Courier Fleet</span>
                </Link>
            </div>

            <!-- Recent Orders -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-900">Recent Orders</h2>
                    <Link href="/admin/orders" class="text-xs font-semibold text-blue-600 hover:text-blue-800">View all &rarr;</Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3">Order #</th>
                                <th class="px-6 py-3">Customer</th>
                                <th class="px-6 py-3">Shop</th>
                                <th class="px-6 py-3">Total</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="o in recentOrders" :key="o.id" class="hover:bg-gray-50/50">
                                <td class="px-6 py-3 font-mono text-xs font-semibold text-gray-700">{{ o.order_number }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ o.customer }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ o.shop }}</td>
                                <td class="px-6 py-3 font-semibold text-gray-900">{{ currency(o.total) }}</td>
                                <td class="px-6 py-3">
                                    <span :class="statusClasses(o.status)" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">{{ o.status }}</span>
                                </td>
                                <td class="px-6 py-3 text-gray-400 text-xs">{{ o.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- At-Risk Distributors -->
            <div v-if="atRiskDistributors.length" class="bg-white rounded-xl shadow-sm border-l-4 border-rose-500 border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-rose-50/30 flex items-center gap-3">
                    <svg class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <h2 class="text-sm font-bold text-gray-900">At-Risk Distributors</h2>
                    <span class="text-xs text-gray-500">({{ atRiskDistributors.length }})</span>
                </div>
                <div class="divide-y">
                    <div v-for="d in atRiskDistributors" :key="d.id" class="px-6 py-4 hover:bg-rose-50/20 transition">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-gray-900">{{ d.company_name }}</span>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                        :class="{
                                            'bg-rose-100 text-rose-800': d.risk_level === 'Critical',
                                            'bg-orange-100 text-orange-800': d.risk_level === 'High',
                                            'bg-yellow-100 text-yellow-800': d.risk_level === 'Medium',
                                        }"
                                    >{{ d.risk_level }}</span>
                                    <span v-if="d.is_suspended" class="px-2 py-0.5 rounded-full bg-gray-800 text-white text-[10px] font-bold uppercase">Suspended</span>
                                </div>
                                <ul class="mt-1 text-xs text-gray-500 list-disc list-inside">
                                    <li v-for="r in d.reasons" :key="r">{{ r }}</li>
                                </ul>
                            </div>
                            <Link :href="`/admin/users?search=${encodeURIComponent(d.company_name)}&shop_status=all`" class="text-xs font-semibold text-blue-600 hover:text-blue-800 whitespace-nowrap">
                                Manage &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Distributor Activity -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-900">Recent Shop Registrations</h2>
                    <Link href="/admin/users" class="text-xs font-semibold text-blue-600 hover:text-blue-800">View all &rarr;</Link>
                </div>
                <div class="divide-y">
                    <div v-for="a in recentActivity" :key="a.id" class="px-6 py-3 flex items-center justify-between hover:bg-gray-50/50">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ a.company_name }}</p>
                            <p class="text-xs text-gray-500">{{ a.user_name }}</p>
                        </div>
                        <div class="text-right">
                            <span :class="statusClasses(a.status)" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase capitalize">{{ a.status }}</span>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ a.created_at }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: Object,
    financial: Object,
    orderTrend: Array,
    recentOrders: Array,
    recentActivity: Array,
    atRiskDistributors: Array,
});

const maxTrend = Math.max(...(props.orderTrend?.map(d => d.count) || [1]), 1);
const barHeight = (count) => Math.round((count / maxTrend) * 100);

const currency = (val) => {
    const n = Number(val || 0);
    return '₱' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const statusClasses = (status) => ({
    'bg-yellow-100 text-yellow-800': status === 'pending',
    'bg-blue-100 text-blue-800': status === 'accepted' || status === 'packed',
    'bg-indigo-100 text-indigo-800': status === 'shipped',
    'bg-emerald-100 text-emerald-800': status === 'delivered' || status === 'completed' || status === 'approved',
    'bg-red-100 text-red-800': status === 'cancelled' || status === 'rejected' || status === 'banned',
});
</script>
