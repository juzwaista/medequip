<template>
    <AdminLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div>
                <Link href="/admin/orders" class="text-sm font-semibold text-brand hover:text-brand-dark">&larr; All orders</Link>
                <h1 class="text-2xl font-bold text-ink mt-2">
                    Order <span class="font-mono text-ink-soft">#{{ order.order_number }}</span>
                </h1>
                <p class="text-sm text-ink-soft mt-1">
                    Placed {{ formatDate(order.created_at) }}
                    &middot;
                    <span :class="statusClasses(order.status)" class="px-2 py-0.5 rounded-full text-xs font-bold ">{{ order.status }}</span>
                </p>
            </div>

            <!-- Customer & Shop -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-card shadow-sm border border-line p-5">
                    <h2 class="text-xs font-bold text-ink-soft  tracking-wide mb-3">Customer</h2>
                    <p class="font-semibold text-ink">{{ order.customer?.name ?? '—' }}</p>
                    <p class="text-sm text-ink-soft">{{ order.customer?.email ?? '' }}</p>
                </div>
                <div class="bg-white rounded-card shadow-sm border border-line p-5">
                    <h2 class="text-xs font-bold text-ink-soft  tracking-wide mb-3">Shop</h2>
                    <p class="font-semibold text-ink">{{ order.distributor?.company_name ?? '—' }}</p>
                    <p class="text-sm text-ink-soft">{{ order.distributor?.contact_number ?? '' }}</p>
                    <p class="text-sm text-ink-faint">{{ order.distributor?.address ?? '' }}</p>
                </div>
            </div>

            <!-- Order Items -->
            <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                <div class="px-6 py-4 border-b border-line">
                    <h2 class="text-sm font-bold text-ink">Items</h2>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-mist text-xs text-ink-soft  ">
                        <tr>
                            <th class="px-6 py-3">Product</th>
                            <th class="px-6 py-3 text-right">Qty</th>
                            <th class="px-6 py-3 text-right">Unit Price</th>
                            <th class="px-6 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="item in order.items" :key="item.id">
                            <td class="px-6 py-3 text-ink">
                                {{ item.product?.name ?? 'Deleted product' }}
                                <span v-if="item.variation_label" class="text-xs text-ink-faint ml-1">({{ item.variation_label }})</span>
                            </td>
                            <td class="px-6 py-3 text-right text-ink">{{ item.quantity }}</td>
                            <td class="px-6 py-3 text-right text-ink">{{ currency(item.unit_price) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-ink">{{ currency(item.total_price) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t border-line bg-mist">
                        <tr>
                            <td class="px-6 py-3 font-bold text-ink" colspan="3">Total</td>
                            <td class="px-6 py-3 text-right font-bold text-ink">{{ currency(order.total_amount) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Payments -->
            <div v-if="order.invoice?.payments?.length" class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                <div class="px-6 py-4 border-b border-line">
                    <h2 class="text-sm font-bold text-ink">Payments</h2>
                </div>
                <div class="divide-y divide-line">
                    <div v-for="p in order.invoice.payments" :key="p.id" class="px-6 py-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-ink">{{ currency(p.amount) }}</p>
                            <p class="text-xs text-ink-soft">{{ p.payment_method }} &middot; {{ p.status }}</p>
                        </div>
                        <div class="text-right">
                            <p v-if="p.platform_fee_amount" class="text-xs text-ink-faint">Fee: {{ currency(p.platform_fee_amount) }}</p>
                            <p v-if="p.net_seller_amount" class="text-xs text-brand font-medium">Seller: {{ currency(p.net_seller_amount) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery -->
            <div v-if="order.delivery" class="bg-white rounded-card shadow-sm border border-line p-5">
                <h2 class="text-xs font-bold text-ink-soft  tracking-wide mb-3">Delivery</h2>
                <p class="text-sm text-ink">
                    Status: <span class="font-semibold capitalize">{{ order.delivery.status }}</span>
                </p>
                <p v-if="order.delivery.courier?.user?.name" class="text-sm text-ink-soft mt-1">
                    Courier: {{ order.delivery.courier.user.name }}
                </p>
                <div v-if="order.delivery.proof_of_delivery_path" class="pt-3 mt-3 border-t border-line">
                    <p class="text-xs font-bold text-ink-soft  tracking-wide mb-2">Proof of Delivery</p>
                    <div class="bg-mist border border-line rounded-control p-2 inline-block relative">
                        <img 
                            :src="`/storage/${order.delivery.proof_of_delivery_path}`" 
                            alt="Proof of Delivery Photo" 
                            class="max-w-[200px] rounded shadow-sm"
                        />
                        <div v-if="order.delivery.is_location_flagged" class="absolute top-4 left-4 right-4 bg-red-100/90 text-red-700 text-xs font-bold px-2 py-1 rounded shadow border border-red-200 text-center ">
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
    'bg-brand-tint text-brand-dark': status === 'accepted' || status === 'packed',
    'bg-brand-tint text-brand-dark': status === 'shipped',
    'bg-brand-tint text-brand-dark': status === 'delivered' || status === 'completed',
    'bg-red-100 text-red-800': status === 'cancelled' || status === 'rejected',
});
</script>
