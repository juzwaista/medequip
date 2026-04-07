<template>
    <AdminLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div>
                <Link href="/admin/orders" class="text-sm font-semibold text-blue-600 hover:text-blue-800">&larr; All orders</Link>
                <h1 class="text-2xl font-bold text-gray-900 mt-2">
                    Order <span class="font-mono text-gray-500">#{{ order.order_number }}</span>
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Placed {{ formatDate(order.created_at) }}
                    &middot;
                    <span :class="statusClasses(order.status)" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">{{ order.status }}</span>
                </p>
            </div>

            <!-- Customer & Shop -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Customer</h2>
                    <p class="font-semibold text-gray-900">{{ order.customer?.name ?? '—' }}</p>
                    <p class="text-sm text-gray-500">{{ order.customer?.email ?? '' }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Shop</h2>
                    <p class="font-semibold text-gray-900">{{ order.distributor?.company_name ?? '—' }}</p>
                    <p class="text-sm text-gray-500">{{ order.distributor?.contact_number ?? '' }}</p>
                    <p class="text-sm text-gray-400">{{ order.distributor?.address ?? '' }}</p>
                </div>
            </div>

            <!-- Order Items -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Items</h2>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Product</th>
                            <th class="px-6 py-3 text-right">Qty</th>
                            <th class="px-6 py-3 text-right">Unit Price</th>
                            <th class="px-6 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="item in order.items" :key="item.id">
                            <td class="px-6 py-3 text-gray-700">
                                {{ item.product?.name ?? 'Deleted product' }}
                                <span v-if="item.variation_label" class="text-xs text-gray-400 ml-1">({{ item.variation_label }})</span>
                            </td>
                            <td class="px-6 py-3 text-right text-gray-700">{{ item.quantity }}</td>
                            <td class="px-6 py-3 text-right text-gray-700">{{ currency(item.unit_price) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900">{{ currency(item.total_price) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50">
                        <tr>
                            <td class="px-6 py-3 font-bold text-gray-900" colspan="3">Total</td>
                            <td class="px-6 py-3 text-right font-bold text-gray-900">{{ currency(order.total_amount) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Payments -->
            <div v-if="order.invoice?.payments?.length" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Payments</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="p in order.invoice.payments" :key="p.id" class="px-6 py-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ currency(p.amount) }}</p>
                            <p class="text-xs text-gray-500">{{ p.payment_method }} &middot; {{ p.status }}</p>
                        </div>
                        <div class="text-right">
                            <p v-if="p.platform_fee_amount" class="text-xs text-gray-400">Fee: {{ currency(p.platform_fee_amount) }}</p>
                            <p v-if="p.net_seller_amount" class="text-xs text-emerald-600 font-medium">Seller: {{ currency(p.net_seller_amount) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery -->
            <div v-if="order.delivery" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Delivery</h2>
                <p class="text-sm text-gray-700">
                    Status: <span class="font-semibold capitalize">{{ order.delivery.status }}</span>
                </p>
                <p v-if="order.delivery.courier?.user?.name" class="text-sm text-gray-500 mt-1">
                    Courier: {{ order.delivery.courier.user.name }}
                </p>
                <div v-if="order.delivery.proof_of_delivery_path" class="pt-3 mt-3 border-t border-gray-100">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Proof of Delivery</p>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-2 inline-block relative">
                        <img 
                            :src="`/storage/${order.delivery.proof_of_delivery_path}`" 
                            alt="Proof of Delivery Photo" 
                            class="max-w-[200px] rounded shadow-sm"
                        />
                        <div v-if="order.delivery.is_location_flagged" class="absolute top-4 left-4 right-4 bg-red-100/90 text-red-700 text-[10px] font-bold px-2 py-1 rounded shadow border border-red-200 text-center backdrop-blur-sm">
                            Location mismatch detected
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

const props = defineProps({ order: Object });

const currency = (val) => '₱' + Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatDate = (d) => new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });

const statusClasses = (status) => ({
    'bg-yellow-100 text-yellow-800': status === 'pending',
    'bg-blue-100 text-blue-800': status === 'accepted' || status === 'packed',
    'bg-indigo-100 text-indigo-800': status === 'shipped',
    'bg-emerald-100 text-emerald-800': status === 'delivered' || status === 'completed',
    'bg-red-100 text-red-800': status === 'cancelled' || status === 'rejected',
});
</script>
