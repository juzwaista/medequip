<template>
    <MainLayout>
        <div class="max-w-3xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 pb-24 md:pb-14">
            <!-- Confirmation -->
            <div class="mb-8 text-center">
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-brand-tint">
                    <svg class="h-8 w-8 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-ink">Your order has been placed</h1>
                <p class="mt-2 text-ink-soft">
                    Checkout created {{ shopsCount }} order{{ shopsCount > 1 ? 's' : '' }} from {{ shopsCount }} shop{{ shopsCount > 1 ? 's' : '' }}.
                </p>
            </div>

            <!-- Payment notices -->
            <div class="mb-6 space-y-3">
                <AlertBanner v-if="allOrdersCod" variant="warning">
                    <strong class="font-semibold">Cash on delivery:</strong> Pay <strong class="font-semibold">₱{{ Number(grandTotal).toLocaleString() }}</strong> to your courier when your order arrives. No online payment was taken for these orders.
                </AlertBanner>
                <AlertBanner v-else-if="mixedPaymentTypes" variant="info">
                    <p v-if="onlineOrdersTotal > 0">
                        For orders paid online, <strong class="font-semibold">₱{{ Number(onlineOrdersTotal).toLocaleString() }}</strong> is held by the platform per order until you confirm delivery.
                    </p>
                    <p v-if="codOrdersTotal > 0" :class="onlineOrdersTotal > 0 ? 'mt-1' : ''">
                        For cash on delivery orders, pay <strong class="font-semibold">₱{{ Number(codOrdersTotal).toLocaleString() }}</strong> to your courier when those orders arrive.
                    </p>
                </AlertBanner>
                <AlertBanner v-else-if="hasPurchaseOrders && !hasOnlinePaidOrders && !hasUnpaidOnlineOrders" variant="info">
                    <strong class="font-semibold">Purchase order submitted:</strong> the seller will verify your PO document and credit terms before accepting the order. No online payment is needed now. Payment is due on the agreed Net-30 terms.
                </AlertBanner>
                <AlertBanner v-else-if="hasOnlinePaidOrders" variant="success">
                    Your online payments totaling <strong class="font-semibold">₱{{ Number(grandTotal).toLocaleString() }}</strong> were received and are held by the platform per order until you confirm delivery.
                </AlertBanner>
                <AlertBanner v-else-if="hasUnpaidOnlineOrders" variant="warning">
                    <strong class="font-semibold">Payment pending:</strong> Your online payment for <strong class="font-semibold">₱{{ Number(onlineOrdersTotal).toLocaleString() }}</strong> is still pending. You can complete it from the My orders page.
                </AlertBanner>
            </div>

            <!-- Summary -->
            <section class="mb-6 rounded-card border border-line bg-white p-5 sm:p-6">
                <h2 class="mb-4 font-semibold text-ink">Summary</h2>
                <dl class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div>
                        <dt class="mb-0.5 text-sm text-ink-soft">Shops</dt>
                        <dd class="font-semibold tabular-nums text-ink">{{ shopsCount }}</dd>
                    </div>
                    <div>
                        <dt class="mb-0.5 text-sm text-ink-soft">Items</dt>
                        <dd class="font-semibold tabular-nums text-ink">{{ itemsCount }}</dd>
                    </div>
                    <div>
                        <dt class="mb-0.5 text-sm text-ink-soft">Shipping</dt>
                        <dd class="font-semibold tabular-nums text-ink">₱{{ Number(shippingTotal).toLocaleString() }}</dd>
                    </div>
                    <div>
                        <dt class="mb-0.5 text-sm text-ink-soft">Total</dt>
                        <dd class="text-lg font-semibold tabular-nums text-ink">₱{{ Number(grandTotal).toLocaleString() }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Orders -->
            <div class="mb-8 space-y-4">
                <article
                    v-for="entry in normalizedOrders"
                    :key="entry.id"
                    class="rounded-card border border-line bg-white p-5 sm:p-6"
                >
                    <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm text-ink-soft">Order number</p>
                            <Link :href="`/orders/${entry.order_number}`" class="font-semibold text-brand hover:text-brand-dark hover:underline underline-offset-2">{{ entry.order_number }}</Link>
                            <p class="mt-1 text-ink-soft">{{ entry.distributor?.company_name || 'Distributor' }}</p>
                        </div>
                        <StatusBadge :status="String(entry.status)" type="order" />
                    </div>

                    <dl class="mb-4 grid grid-cols-2 gap-3 border-b border-line pb-4 text-sm md:grid-cols-4">
                        <div>
                            <dt class="text-ink-soft">Subtotal</dt>
                            <dd class="font-medium tabular-nums text-ink">₱{{ Number(entry.subtotal || 0).toLocaleString() }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-soft">Shipping</dt>
                            <dd class="font-medium tabular-nums text-ink">₱{{ Number(entry.shipping_fee || 0).toLocaleString() }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-soft">Order total</dt>
                            <dd class="font-medium tabular-nums text-ink">₱{{ Number(entry.total_amount || 0).toLocaleString() }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-soft">Payment</dt>
                            <dd class="font-medium text-ink">{{ formatPaymentMethod(entry.payment_method) }}</dd>
                        </div>
                    </dl>

                    <ul class="divide-y divide-line">
                        <li v-for="item in (entry.items || [])" :key="item.id" class="py-2 text-sm first:pt-0 last:pb-0">
                            <div class="flex justify-between gap-3">
                                <span class="font-medium text-ink">{{ item.product?.name }} <span class="font-normal text-ink-soft">×{{ item.quantity }}</span></span>
                                <span class="font-semibold tabular-nums text-ink">₱{{ Number(item.total_price || 0).toLocaleString() }}</span>
                            </div>
                            <p v-if="item.product_variation" class="mt-0.5 text-sm text-brand-dark">
                                {{ item.product_variation.display_label || `${item.product_variation.option_name}: ${item.product_variation.option_value}` }}
                            </p>
                        </li>
                    </ul>
                </article>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <BaseButton href="/my-orders" class="flex-1">View my orders</BaseButton>
                <BaseButton href="/products" variant="secondary" class="flex-1">Continue shopping</BaseButton>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import BaseButton from '@/Components/ui/BaseButton.vue';
import AlertBanner from '@/Components/ui/AlertBanner.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    order: Object,
    orders: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
});

const normalizedOrders = computed(() => {
    if (props.orders?.length) return props.orders;
    return props.order ? [props.order] : [];
});

const shopsCount = computed(() => Number(props.summary?.shops_count ?? normalizedOrders.value.length ?? 1));
const itemsCount = computed(() => Number(props.summary?.items_count ?? normalizedOrders.value.reduce((sum, o) => sum + (o.items?.length || 0), 0)));
const shippingTotal = computed(() => Number(props.summary?.shipping_total ?? normalizedOrders.value.reduce((sum, o) => sum + Number(o.shipping_fee || 0), 0)));
const grandTotal = computed(() => Number(props.summary?.grand_total ?? normalizedOrders.value.reduce((sum, o) => sum + Number(o.total_amount || 0), 0)));

const allOrdersCod = computed(() => {
    const orders = normalizedOrders.value;
    return orders.length > 0 && orders.every((o) => o.payment_method === 'cod');
});

const isOfflinePayment = (o) => o.payment_method === 'cod' || o.payment_method === 'purchase_order';

const hasPurchaseOrders = computed(() => normalizedOrders.value.some((o) => o.payment_method === 'purchase_order'));

const hasOnlinePaidOrders = computed(() => {
    return normalizedOrders.value.some((o) => {
        if (isOfflinePayment(o)) return false;
        return o.invoice?.status === 'paid' || 
               o.invoice?.payments?.some(p => p.status === 'verified');
    });
});

const hasUnpaidOnlineOrders = computed(() => {
    return normalizedOrders.value.some((o) => {
        if (isOfflinePayment(o)) return false;
        return !o.invoice || (o.invoice.status !== 'paid' && !o.invoice.payments?.some(p => p.status === 'verified'));
    });
});

const mixedPaymentTypes = computed(() => {
    const orders = normalizedOrders.value;
    const hasCod = orders.some((o) => o.payment_method === 'cod');
    const hasOnline = orders.some((o) => o.payment_method && o.payment_method !== 'cod');
    return hasCod && hasOnline;
});

const codOrdersTotal = computed(() =>
    normalizedOrders.value
        .filter((o) => o.payment_method === 'cod')
        .reduce((sum, o) => sum + Number(o.total_amount || 0), 0)
);

const onlineOrdersTotal = computed(() =>
    normalizedOrders.value
        .filter((o) => o.payment_method && !isOfflinePayment(o))
        .reduce((sum, o) => sum + Number(o.total_amount || 0), 0)
);

const formatPaymentMethod = (method) => {
    const labels = {
        'gcash': 'GCash',
        'paymaya': 'Maya',
        'card': 'Credit/Debit Card',
        'grab_pay': 'GrabPay',
        'bank_transfer': 'Bank Transfer',
        'paymongo': 'Online Payment',
        'wallet': 'Wallet',
        'cod': 'Cash on Delivery',
        'purchase_order': 'Purchase Order (Net-30)',
    };
    return labels[method] || method;
};
</script>
