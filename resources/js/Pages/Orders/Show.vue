<template>
    <MainLayout>
        <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <!-- Breadcrumb -->
            <nav class="mb-4 text-sm text-ink-soft" aria-label="Breadcrumb">
                <Link href="/my-orders" class="hover:text-brand hover:underline underline-offset-2">My orders</Link>
                <span class="mx-1.5 text-ink-faint" aria-hidden="true">/</span>
                <span class="font-medium text-ink">{{ order.order_number }}</span>
            </nav>

            <!-- Order header: status and the actions you can take right now -->
            <header class="rounded-card border border-line bg-white p-5 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h1 class="text-2xl font-semibold tracking-tight text-ink">Order {{ order.order_number }}</h1>
                        <p class="mt-1 text-ink-soft">
                            Placed <DateFormat :date="order.created_at" format="datetime" />
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusBadge :status="order.status" type="order" />
                        <span v-if="order.is_fragile" class="inline-flex items-center gap-1.5 rounded-control border border-red-200 bg-red-50 px-2 py-0.5 text-sm font-medium text-red-900">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Fragile package
                        </span>
                    </div>
                </div>

                <p v-if="statusMessage(order.status)" class="mt-3 max-w-2xl text-ink-soft">{{ statusMessage(order.status) }}</p>

                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-line pt-5">
                    <BaseButton v-if="canPayNow" @click="payNow">Pay now</BaseButton>
                    <BaseButton :href="orderMessaging.href" variant="secondary">{{ orderMessaging.label }}</BaseButton>
                    <button
                        v-if="order.status === 'pending' || order.status === 'approved'"
                        type="button"
                        @click="cancelOrder"
                        class="ml-auto inline-flex h-10 items-center rounded-control px-4 font-medium text-danger transition-colors hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-danger"
                    >
                        Cancel order
                    </button>
                </div>
            </header>

            <!-- Prescription and discount notices -->
            <div class="mt-4 space-y-3 empty:hidden">
                <AlertBanner v-if="order.prescription_status === 'awaiting_upload'" variant="warning">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold">Prescription required</p>
                            <p class="mt-0.5">Upload a clear photo of your prescription. Payment is available after the distributor approves it.</p>
                        </div>
                        <BaseButton :href="`/orders/${order.order_number}/prescription`" size="sm" class="shrink-0">Upload prescription</BaseButton>
                    </div>
                </AlertBanner>
                <AlertBanner v-else-if="order.prescription_status === 'pending_review'" variant="info">
                    <p class="font-semibold">Prescription under review</p>
                    <p class="mt-0.5">The distributor is verifying your prescription. You can pay once it's approved.</p>
                </AlertBanner>
                <AlertBanner v-else-if="order.prescription_status === 'rejected'" variant="error">
                    <p class="font-semibold">Prescription not accepted</p>
                    <p v-if="order.prescription_review_note" class="mt-0.5">{{ order.prescription_review_note }}</p>
                </AlertBanner>

                <template v-if="order.discount_status !== 'none'">
                    <AlertBanner v-if="order.discount_status === 'pending'" variant="info">
                        <p class="font-semibold capitalize">{{ order.discount_type }} discount requested</p>
                        <p class="mt-0.5">The distributor is reviewing your ID for the 20% discount and VAT exemption.</p>
                    </AlertBanner>
                    <AlertBanner v-else-if="order.discount_status === 'approved'" variant="success">
                        <p class="font-semibold capitalize">{{ order.discount_type }} discount approved</p>
                        <p class="mt-0.5">Your 20% discount and VAT exemption have been applied to this order.</p>
                    </AlertBanner>
                    <AlertBanner v-else-if="order.discount_status === 'rejected'" variant="error">
                        <p class="font-semibold capitalize">{{ order.discount_type }} discount rejected</p>
                        <p v-if="order.discount_review_note" class="mt-0.5">{{ order.discount_review_note }}</p>
                    </AlertBanner>
                </template>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main column -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Progress -->
                    <section class="rounded-card border border-line bg-white p-5 sm:p-6">
                        <OrderTimeline
                            :current-status="order.status"
                            :created-at="order.created_at"
                            :approved-at="order.approved_at"
                            :packed-at="order.packed_at"
                            :shipped-at="order.shipped_at"
                            :delivered-at="order.delivered_at"
                            :cancelled-at="order.cancelled_at"
                            :rejected-at="order.rejected_at"
                        />
                    </section>

                    <!-- Confirm receipt (required before ratings and the seller payout) -->
                    <section v-if="canConfirmReceived" class="rounded-card border border-brand-soft bg-brand-tint p-5 sm:p-6">
                        <h2 class="text-lg font-semibold text-ink">Confirm that your order arrived</h2>
                        <p class="mt-1 max-w-prose leading-relaxed text-ink-soft">
                            Mark this order as received after your items arrive. You can rate products and delivery only after you confirm. For online payments, this also allows the seller to receive their payout.
                        </p>
                        <BaseButton class="mt-4 w-full sm:w-auto" @click="confirmReceived">I have received my order</BaseButton>
                    </section>

                    <!-- Items and totals -->
                    <section class="rounded-card border border-line bg-white p-5 sm:p-6">
                        <h2 class="mb-4 text-lg font-semibold tracking-tight text-ink">Items</h2>

                        <ul class="divide-y divide-line">
                            <li v-for="item in order.items" :key="item.id" class="flex gap-4 py-4 first:pt-0">
                                <div class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-control border border-line bg-[#F7FAFA] sm:h-24 sm:w-24">
                                    <img
                                        v-if="item.product.image_path"
                                        :src="`/storage/${item.product.image_path}`"
                                        :alt="item.product.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div v-else class="flex h-full w-full items-center justify-center">
                                        <svg class="h-8 w-8 text-ink-faint/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="font-semibold text-ink">{{ item.product.name }}</h3>
                                    <p v-if="item.product_variation" class="mt-0.5 text-sm font-medium text-brand-dark">
                                        {{ item.product_variation.display_label || `${item.product_variation.option_name}: ${item.product_variation.option_value}` }}
                                    </p>
                                    <p class="mt-1 text-sm text-ink-soft tabular-nums">
                                        {{ item.quantity }} × <PriceDisplay :amount="item.unit_price" size="small" />
                                    </p>
                                    <span
                                        v-if="item.is_wholesale"
                                        class="mt-2 inline-flex items-center rounded-control border border-brand-soft bg-brand-tint px-2 py-0.5 text-sm font-medium text-brand-dark"
                                    >
                                        Wholesale price
                                    </span>
                                </div>

                                <div class="text-right">
                                    <PriceDisplay :amount="item.total_price" />
                                </div>
                            </li>
                        </ul>

                        <dl class="mt-2 space-y-2 border-t border-line pt-4 text-ink-soft">
                            <div class="flex items-center justify-between">
                                <dt>Items subtotal</dt>
                                <dd><PriceDisplay :amount="orderSubtotal" /></dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>Shipping fee</dt>
                                <dd><PriceDisplay :amount="orderShippingFee" /></dd>
                            </div>
                            <div v-if="order.discount > 0" class="flex items-center justify-between text-brand-dark">
                                <dt>{{ order.discount_type === 'senior' ? 'Senior' : 'PWD' }} discount</dt>
                                <dd>−<PriceDisplay :amount="order.discount" /></dd>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <dt>VATable sales</dt>
                                <dd class="tabular-nums">₱{{ Number(order.vatable_sales || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</dd>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <dt>VAT amount (12%)</dt>
                                <dd class="tabular-nums">₱{{ Number(order.vat_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</dd>
                            </div>
                            <div v-if="order.vat_exempt_sales > 0" class="flex items-center justify-between text-sm">
                                <dt>VAT-exempt sales</dt>
                                <dd class="tabular-nums">₱{{ Number(order.vat_exempt_sales || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between border-t border-line pt-3">
                                <dt class="text-lg font-semibold text-ink">Total</dt>
                                <dd><PriceDisplay :amount="orderGrandTotal" size="large" /></dd>
                            </div>
                        </dl>
                    </section>

                    <!-- Delivery tracking (not for pick-up orders) -->
                    <section v-if="order.delivery && order.fulfillment_method !== 'pickup'" class="rounded-card border border-line bg-white p-5 sm:p-6">
                        <h2 class="mb-4 text-lg font-semibold tracking-tight text-ink">Delivery tracking</h2>
                        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <dt class="mb-1 text-sm text-ink-soft">Tracking number</dt>
                                <dd class="text-lg font-semibold tabular-nums text-ink">{{ order.delivery.tracking_number }}</dd>
                            </div>
                            <div>
                                <dt class="mb-1 text-sm text-ink-soft">Carrier</dt>
                                <dd class="font-medium text-ink">
                                    MedEquip Express
                                    <span v-if="order.delivery.courier?.user?.name" class="font-normal text-ink-soft">({{ order.delivery.courier.user.name }})</span>
                                </dd>
                                <dd v-if="order.delivery.courier?.user?.phone_number" class="mt-1 text-sm text-ink-soft">
                                    <span class="font-medium text-ink">Driver contact:</span> {{ order.delivery.courier.user.phone_number }}
                                </dd>
                            </div>
                            <div>
                                <dt class="mb-1 text-sm text-ink-soft">Delivery status</dt>
                                <dd><StatusBadge :status="order.delivery.status" type="delivery" /></dd>
                            </div>
                            <div v-if="order.delivery.actual_delivery_at">
                                <dt class="mb-1 text-sm text-ink-soft">Delivered on</dt>
                                <dd class="font-medium text-ink"><DateFormat :date="order.delivery.actual_delivery_at" format="datetime" /></dd>
                            </div>
                            <div v-if="order.delivery.proof_of_delivery_path" class="mt-2 border-t border-line pt-4 md:col-span-2">
                                <dt class="mb-3 text-sm font-semibold text-ink">Proof of delivery</dt>
                                <dd class="inline-block rounded-card border border-line bg-mist p-2">
                                    <img
                                        :src="`/storage/${order.delivery.proof_of_delivery_path}`"
                                        alt="Proof of delivery photo"
                                        class="max-w-xs rounded-control md:max-w-md"
                                    />
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <!-- Rate this order -->
                    <section v-if="reviewState?.eligible" class="rounded-card border border-line bg-white p-5 sm:p-6">
                        <h2 class="text-lg font-semibold tracking-tight text-ink">Rate this order</h2>
                        <p class="mt-1 text-ink-soft">Let us know how your experience was.</p>

                        <div v-if="reviewState.product_rows?.length" class="mt-5 space-y-4">
                            <div v-for="row in reviewState.product_rows" :key="row.product_id" class="rounded-card border border-line p-4">
                                <p class="font-semibold text-ink">{{ row.name }}</p>
                                <div class="mt-2 flex flex-wrap gap-1" role="group" aria-label="Star rating">
                                    <button
                                        v-for="s in 5"
                                        :key="s"
                                        type="button"
                                        class="min-h-[44px] min-w-[44px] rounded-control p-1 leading-none transition-colors"
                                        :class="s <= (productRatings[row.product_id]?.stars ?? 5) ? 'text-amber-600' : 'text-line'"
                                        :aria-label="`${s} star${s === 1 ? '' : 's'}`"
                                        @click="setProductStar(row.product_id, s)"
                                    >
                                        <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 20 20"><path d="M10 1.8l2.4 5 5.5.7-4 3.8 1 5.4L10 14l-4.9 2.7 1-5.4-4-3.8 5.5-.7z"/></svg>
                                    </button>
                                </div>
                                <textarea
                                    v-model="productRatings[row.product_id].body"
                                    rows="2"
                                    maxlength="2000"
                                    placeholder="Optional review (visible on the product)"
                                    class="mt-2 block w-full rounded-control border border-line bg-white px-3 py-2 text-ink placeholder:text-ink-faint focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-tint"
                                />
                                <p v-if="row.reviewed" class="mt-2 text-sm font-medium text-brand">Saved. You can update it anytime.</p>
                            </div>
                        </div>

                        <div v-if="reviewState.delivery?.eligible" class="mt-6 border-t border-line pt-6">
                            <h3 class="font-semibold text-ink">Delivery experience</h3>
                            <p class="mt-0.5 text-sm text-ink-soft">Courier: {{ reviewState.delivery.courier_label }}</p>
                            <div class="mt-2 flex flex-wrap gap-1" role="group" aria-label="Delivery star rating">
                                <button
                                    v-for="s in 5"
                                    :key="'d-' + s"
                                    type="button"
                                    class="min-h-[44px] min-w-[44px] rounded-control p-1 leading-none transition-colors"
                                    :class="s <= deliveryStars ? 'text-amber-600' : 'text-line'"
                                    :aria-label="`${s} star${s === 1 ? '' : 's'}`"
                                    @click="deliveryStars = s"
                                >
                                    <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 20 20"><path d="M10 1.8l2.4 5 5.5.7-4 3.8 1 5.4L10 14l-4.9 2.7 1-5.4-4-3.8 5.5-.7z"/></svg>
                                </button>
                            </div>
                            <textarea
                                v-model="deliveryBody"
                                rows="2"
                                maxlength="2000"
                                placeholder="Optional: delays, handling, region notes…"
                                class="mt-2 block w-full rounded-control border border-line bg-white px-3 py-2 text-ink placeholder:text-ink-faint focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-tint"
                            />
                        </div>
                        <div v-else-if="reviewState.delivery?.submitted" class="mt-6 border-t border-line pt-6 text-ink-soft">
                            <span class="font-semibold text-ink">Delivery rated</span>
                            <span class="ml-1 inline-flex items-center gap-0.5 tabular-nums">
                                <svg class="h-3.5 w-3.5 text-amber-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 1.8l2.4 5 5.5.7-4 3.8 1 5.4L10 14l-4.9 2.7 1-5.4-4-3.8 5.5-.7z"/></svg>
                                {{ reviewState.delivery.stars }}
                            </span>
                            <span v-if="reviewState.delivery.body" class="mt-1 block">{{ reviewState.delivery.body }}</span>
                        </div>

                        <div v-if="reviewState.product_rows?.length || reviewState.delivery?.eligible" class="mt-6 border-t border-line pt-6">
                            <BaseButton @click="submitAllRatings">Save all ratings</BaseButton>
                            <p class="mt-2 text-sm text-ink-soft">Saves product reviews and your delivery rating together when both apply.</p>
                        </div>
                    </section>
                </div>

                <!-- Sidebar -->
                <aside class="space-y-6 lg:col-span-1">
                    <!-- Payment / invoice -->
                    <section v-if="order.invoice" class="rounded-card border border-line bg-white p-5">
                        <h2 class="mb-4 font-semibold text-ink">Payment</h2>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="mb-1 text-ink-soft">Payment status</dt>
                                <dd>
                                    <span
                                        :class="{
                                            'border-brand bg-brand text-white': order.customer_payment_status?.state === 'paid',
                                            'border-amber-200 bg-amber-50 text-amber-900': order.customer_payment_status?.state === 'pending_verification' || order.customer_payment_status?.state === 'cod_pending' || !order.customer_payment_status || order.customer_payment_status?.state === 'unpaid',
                                            'border-red-200 bg-red-50 text-red-900': order.customer_payment_status?.state === 'payment_failed' || order.customer_payment_status?.state === 'cancelled',
                                        }"
                                        class="inline-block rounded-control border px-2 py-0.5 font-medium"
                                    >
                                        {{ order.customer_payment_status?.label || 'Unpaid' }}
                                    </span>
                                </dd>
                            </div>
                            <div v-if="order.invoice_payment_display?.label">
                                <dt class="mb-1 text-ink-soft">Payment method</dt>
                                <dd class="font-medium text-ink">{{ order.invoice_payment_display.label }}</dd>
                            </div>
                            <div>
                                <dt class="mb-1 text-ink-soft">Invoice number</dt>
                                <dd class="font-medium tabular-nums text-ink">{{ order.invoice.invoice_number }}</dd>
                            </div>
                            <div>
                                <dt class="mb-1 text-ink-soft">Due date</dt>
                                <dd class="font-medium text-ink"><DateFormat :date="order.invoice.due_date" format="short" /></dd>
                            </div>
                        </dl>
                    </section>

                    <section
                        v-else-if="!order.invoice && order.prescription_status && order.prescription_status !== 'not_required'"
                        class="rounded-card border border-line bg-white p-5"
                    >
                        <h2 class="mb-3 font-semibold text-ink">Payment</h2>
                        <p class="mb-2 text-sm text-ink-soft">Prescription workflow</p>
                        <span
                            :class="{
                                'border-amber-200 bg-amber-50 text-amber-900': order.customer_payment_status?.state === 'rx_upload',
                                'border-[#B9C7DA] bg-[#F1F5FA] text-seal': order.customer_payment_status?.state === 'rx_review',
                                'border-red-200 bg-red-50 text-red-900': order.customer_payment_status?.state === 'rx_rejected',
                            }"
                            class="inline-block rounded-control border px-2 py-0.5 text-sm font-medium"
                        >
                            {{ order.customer_payment_status?.label || 'Pending' }}
                        </span>
                        <p class="mt-3 text-sm text-ink-soft">An invoice is created after your prescription is approved.</p>
                    </section>

                    <!-- Seller and delivery details -->
                    <section class="rounded-card border border-line bg-white p-5">
                        <h2 class="mb-4 font-semibold text-ink">Order details</h2>
                        <dl class="space-y-4 text-sm">
                            <div>
                                <dt class="mb-1 text-ink-soft">Sold by</dt>
                                <dd>
                                    <Link
                                        v-if="order.distributor.slug"
                                        :href="`/seller/${order.distributor.slug}`"
                                        class="font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2"
                                    >
                                        {{ order.distributor.company_name }}
                                    </Link>
                                    <span v-else class="font-medium text-ink">{{ order.distributor.company_name }}</span>
                                </dd>
                                <dd class="text-ink-soft">{{ order.distributor.email }}</dd>
                                <dd v-if="order.distributor.user?.phone_number" class="mt-1 text-ink-soft">
                                    <span class="font-medium text-ink">Contact:</span> {{ order.distributor.user.phone_number }}
                                </dd>
                            </div>
                            <div>
                                <dt class="mb-1 text-ink-soft">{{ order.fulfillment_method === 'pickup' ? 'Pick-up location' : 'Delivery address' }}</dt>
                                <dd class="text-ink">{{ order.fulfillment_method === 'pickup' ? order.distributor.address : order.delivery_address }}</dd>
                            </div>
                            <div>
                                <dt class="mb-1 text-ink-soft">Contact number</dt>
                                <dd class="font-medium tabular-nums text-ink">{{ order.contact_number }}</dd>
                            </div>
                            <div v-if="order.tin">
                                <dt class="mb-1 text-ink-soft">TIN</dt>
                                <dd class="font-medium tabular-nums text-ink">{{ order.tin }}</dd>
                            </div>
                            <div v-if="order.notes">
                                <dt class="mb-1 text-ink-soft">Notes</dt>
                                <dd class="text-ink">{{ order.notes }}</dd>
                            </div>
                        </dl>

                        <!-- Pick-up instructions -->
                        <div
                            v-if="order.fulfillment_method === 'pickup' && order.status !== 'pending' && order.status !== 'rejected' && order.status !== 'cancelled'"
                            class="mt-4 rounded-card border border-brand-soft bg-brand-tint p-4"
                        >
                            <h3 class="mb-1 font-semibold text-ink">Pick-up instructions</h3>
                            <p v-if="order.pickup_instructions" class="whitespace-pre-wrap text-sm text-ink-soft">{{ order.pickup_instructions }}</p>
                            <p v-else class="text-sm text-ink-soft">The distributor has not provided specific instructions. Please contact them through chat for details.</p>
                        </div>
                    </section>

                    <Link href="/products" class="block text-center font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">Continue shopping</Link>
                </aside>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import BaseButton from '@/Components/ui/BaseButton.vue';
import AlertBanner from '@/Components/ui/AlertBanner.vue';
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import OrderTimeline from '@/Components/OrderTimeline.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DateFormat from '@/Components/DateFormat.vue';
import PriceDisplay from '@/Components/PriceDisplay.vue';
import { customerOrderStatusMessage } from '@/utils/customerOrderStatusMessage.js';
import { computed, reactive, ref, watch } from 'vue';

const statusMessage = (s) => customerOrderStatusMessage(s);

const props = defineProps({
    order: Object,
    orderMessaging: {
        type: Object,
        required: true,
    },
    reviewState: { type: Object, default: null },
});

const productRatings = reactive({});
const deliveryStars = ref(5);
const deliveryBody = ref('');

watch(
    () => props.reviewState,
    (rs) => {
        if (!rs?.product_rows) {
            return;
        }
        for (const row of rs.product_rows) {
            productRatings[row.product_id] = {
                stars: row.stars ?? productRatings[row.product_id]?.stars ?? 5,
                body: row.body ?? productRatings[row.product_id]?.body ?? '',
            };
        }
    },
    { immediate: true, deep: true }
);

function setProductStar(productId, stars) {
    if (!productRatings[productId]) {
        productRatings[productId] = { stars: 5, body: '' };
    }
    productRatings[productId].stars = stars;
}

function submitAllRatings() {
    // Order's route key is order_number, not id (a numeric id here 404s).
    const orderKey = props.order.order_number;
    const hasProducts = props.reviewState?.product_rows?.length;
    const deliveryEligible = props.reviewState?.delivery?.eligible;

    const postDelivery = () => {
        if (deliveryEligible) {
            router.post(`/orders/${orderKey}/reviews/delivery`, {
                stars: deliveryStars.value,
                body: deliveryBody.value || null,
            }, { preserveScroll: true });
        }
    };

    if (hasProducts) {
        const reviews = props.reviewState.product_rows.map((row) => ({
            product_id: row.product_id,
            stars: productRatings[row.product_id]?.stars ?? 5,
            body: productRatings[row.product_id]?.body || null,
        }));
        router.post(`/orders/${orderKey}/reviews/products`, { reviews }, {
            preserveScroll: true,
            onSuccess: () => postDelivery(),
        });
        return;
    }

    postDelivery();
}

const cancelOrder = () => {
    if (confirm(`Are you sure you want to cancel order ${props.order.order_number}? This action cannot be undone.`)) {
        router.post(`/orders/${props.order.order_number}/cancel`, {}, {
            onSuccess: () => {
            },
            onError: (errors) => {
                console.error('[OrderShow] Cancel failed', errors);
            }
        });
    }
};

const confirmReceived = () => {
    if (confirm(`Confirm that you received order ${props.order.order_number}? This completes the order and releases payment held by the platform to the seller.`)) {
        router.post(`/orders/${props.order.order_number}/confirm-received`, {}, {
            onSuccess: () => {
            },
            onError: (errors) => {
                console.error('[OrderShow] Confirm failed', errors);
            }
        });
    }
};

const canPayNow = computed(() => {
    return !!props.order?.can_pay_now;
});

const canConfirmReceived = computed(() => {
    if (props.order?.received_at) return false;
    if (props.order?.fulfillment_method === 'pickup') {
        return props.order?.status === 'ready_for_pickup';
    }
    return props.order?.status === 'delivered';
});

const orderSubtotal = computed(() => Number(props.order?.subtotal || 0));
const orderShippingFee = computed(() => Number(props.order?.shipping_fee || 0));
const orderGrandTotal = computed(() => {
    const total = Number(props.order?.total_amount || 0);
    if (total > 0) return total;
    return orderSubtotal.value + orderShippingFee.value;
});

const payNow = () => {
    router.post(`/orders/${props.order.order_number}/pay-now`);
};
</script>
