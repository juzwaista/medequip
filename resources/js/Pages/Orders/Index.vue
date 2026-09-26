<template>
    <MainLayout>
        <div class="max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl sm:text-[28px] font-semibold tracking-tight text-ink">My orders</h1>
                <p class="mt-1 text-ink-soft">Track deliveries, pay for pending orders and confirm what you've received.</p>
            </div>

            <!-- Tabs -->
            <div class="border-b border-line">
                <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="Order groups">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        @click="applyBucket(tab.value)"
                        :aria-current="activeBucket === tab.value ? 'page' : undefined"
                        :class="[
                            'whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand',
                            activeBucket === tab.value
                                ? 'border-brand text-brand'
                                : 'border-transparent text-ink-soft hover:border-line hover:text-ink',
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Search and filters -->
            <div class="mt-5">
                <button
                    type="button"
                    class="md:hidden flex w-full items-center justify-between gap-2 rounded-control border border-line bg-white px-3 py-2.5 text-sm font-medium text-ink"
                    @click="showAdvancedFilters = !showAdvancedFilters"
                    :aria-expanded="showAdvancedFilters"
                >
                    <span>Search and filters</span>
                    <svg class="h-5 w-5 flex-shrink-0 text-ink-faint transition-transform" :class="{ 'rotate-180': showAdvancedFilters }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div class="mt-3 grid grid-cols-1 gap-4 md:mt-0 md:grid-cols-[1.4fr_1fr_1.6fr]" :class="showAdvancedFilters ? '' : 'hidden md:grid'">
                    <TextInput
                        v-model="localFilters.search"
                        @input="debouncedSearch"
                        label="Search"
                        type="search"
                        placeholder="Order number or product name"
                    />
                    <SelectInput v-model="localFilters.status" @change="applyFilters" label="Status">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="packed">Packed</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="rejected">Rejected</option>
                    </SelectInput>
                    <div>
                        <p class="mb-1 text-sm font-medium text-ink">Date range</p>
                        <div class="flex items-center gap-2">
                            <TextInput v-model="localFilters.date_from" @change="applyFilters" type="date" aria-label="From date" class="min-w-0 flex-1" />
                            <span class="text-sm text-ink-soft">to</span>
                            <TextInput v-model="localFilters.date_to" @change="applyFilters" type="date" aria-label="To date" class="min-w-0 flex-1" />
                        </div>
                    </div>
                </div>

                <div v-if="hasActiveFilters" class="mt-2 flex justify-end">
                    <button type="button" @click="clearFilters" class="text-sm font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">
                        Clear all filters
                    </button>
                </div>
            </div>

            <!-- Orders -->
            <div class="mt-5 space-y-4">
                <article
                    v-for="order in orders.data"
                    :key="order.id"
                    class="overflow-hidden rounded-card border border-line bg-white"
                >
                    <div class="p-4 sm:p-5">
                        <!-- Order header -->
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h2 class="truncate font-semibold text-ink">
                                    <Link :href="`/orders/${order.order_number}`" class="hover:text-brand hover:underline underline-offset-2">{{ order.order_number }}</Link>
                                </h2>
                                <p class="mt-0.5 text-sm text-ink-soft">
                                    Placed <DateFormat :date="order.created_at" format="datetime" />
                                </p>
                            </div>
                            <StatusBadge :status="order.status" type="order" class="self-start" />
                        </div>

                        <!-- Progress -->
                        <div v-if="stepIndex(order.status) >= 0" class="mt-4" role="img" :aria-label="`Order progress: step ${stepIndex(order.status) + 1} of 5`">
                            <div class="flex gap-1">
                                <span
                                    v-for="(step, i) in orderSteps"
                                    :key="step"
                                    class="h-1.5 flex-1 rounded-full"
                                    :class="i <= stepIndex(order.status) ? 'bg-brand' : 'bg-line'"
                                ></span>
                            </div>
                        </div>
                        <p v-if="statusMessage(order.status)" class="mt-2 text-sm leading-relaxed text-ink-soft">
                            {{ statusMessage(order.status) }}
                        </p>

                        <!-- Items and totals -->
                        <div class="mt-4 flex flex-col gap-4 border-t border-line pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-3">
                                <div v-if="order.preview_images && order.preview_images.length > 0" class="flex items-center gap-1.5">
                                    <div
                                        v-for="(imagePath, idx) in order.preview_images"
                                        :key="idx"
                                        class="flex h-12 w-12 flex-shrink-0 items-center justify-center overflow-hidden rounded-control border border-line bg-[#F7FAFA] sm:h-14 sm:w-14"
                                    >
                                        <img v-if="imagePath" :src="`/storage/${imagePath}`" :alt="`Product ${idx + 1}`" class="h-full w-full object-cover" />
                                        <svg v-else class="h-6 w-6 text-ink-faint/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <span v-if="order.remaining_items > 0" class="text-sm font-medium text-ink-soft">+{{ order.remaining_items }} more</span>
                                </div>
                                <div class="min-w-0 text-sm">
                                    <p class="text-ink-soft">Sold by</p>
                                    <Link
                                        v-if="order.distributor.slug"
                                        :href="`/seller/${order.distributor.slug}`"
                                        class="block truncate font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2"
                                    >
                                        {{ order.distributor.company_name }}
                                    </Link>
                                    <p v-else class="truncate font-medium text-ink">{{ order.distributor.company_name }}</p>
                                </div>
                            </div>

                            <div class="text-left sm:text-right">
                                <p class="text-sm text-ink-soft">{{ order.items.length }} item{{ order.items.length === 1 ? '' : 's' }}</p>
                                <PriceDisplay :amount="order.total_amount" size="large" />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <BaseButton
                                v-if="canPayNow(order)"
                                size="sm"
                                @click="payNow(order)"
                            >
                                Pay now
                            </BaseButton>
                            <BaseButton
                                v-if="order.status === 'delivered' && !order.received_at"
                                size="sm"
                                @click="confirmReceived(order)"
                            >
                                Confirm received
                            </BaseButton>
                            <BaseButton :href="`/orders/${order.order_number}`" variant="secondary" size="sm">
                                {{ order.status === 'shipped' ? 'Track order' : 'View details' }}
                            </BaseButton>
                            <button
                                v-if="order.status === 'pending' || order.status === 'approved'"
                                type="button"
                                @click="confirmCancel(order)"
                                class="ml-auto inline-flex h-9 items-center rounded-control px-3.5 text-sm font-medium text-danger transition-colors hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-danger"
                            >
                                Cancel order
                            </button>
                        </div>
                    </div>
                </article>

                <!-- Empty state -->
                <div v-if="orders.data.length === 0" class="flex flex-col items-center rounded-card border border-line bg-white px-6 py-14 text-center">
                    <svg class="mb-4 h-12 w-12 text-ink-faint/60" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <h2 class="text-lg font-semibold text-ink">{{ hasActiveFilters ? 'No orders match these filters' : 'No orders yet' }}</h2>
                    <p class="mb-6 mt-1 max-w-sm text-ink-soft">
                        {{ hasActiveFilters ? 'Try a different search term or clear the filters.' : 'When you place an order, you can follow it here from packing to delivery.' }}
                    </p>
                    <BaseButton v-if="!hasActiveFilters" href="/products">Browse products</BaseButton>
                    <BaseButton v-else variant="secondary" @click="clearFilters">Clear filters</BaseButton>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="orders.data.length > 0" class="mt-6 flex flex-wrap justify-center gap-2">
                <template v-for="link in orders.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'min-w-10 rounded-control border px-3.5 py-2 text-center text-sm font-medium tabular-nums transition-colors',
                            link.active
                                ? 'border-brand bg-brand text-white'
                                : 'border-line bg-white text-ink hover:border-brand hover:text-brand'
                        ]"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="min-w-10 cursor-not-allowed rounded-control border border-line bg-mist px-3.5 py-2 text-center text-sm text-ink-faint"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import BaseButton from '@/Components/ui/BaseButton.vue';
import TextInput from '@/Components/ui/TextInput.vue';
import SelectInput from '@/Components/ui/SelectInput.vue';
import { reactive, computed, onMounted, ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DateFormat from '@/Components/DateFormat.vue';
import PriceDisplay from '@/Components/PriceDisplay.vue';
import { customerOrderStatusMessage } from '@/utils/customerOrderStatusMessage.js';

const props = defineProps({
    orders: Object,
    filters: Object,
});

const localFilters = reactive({
    status: props.filters.status || '',
    search: props.filters.search || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const showAdvancedFilters = ref(false);

const activeBucket = computed(() => {
    const b = props.filters.bucket;
    return b === 'pay' || b === 'ship' || b === 'receive' ? b : '';
});

let searchTimeout = null;

function syncLocalFromProps() {
    const f = props.filters;
    localFilters.status = f.status || '';
    localFilters.search = f.search || '';
    localFilters.date_from = f.date_from || '';
    localFilters.date_to = f.date_to || '';
}

watch(
    () => props.filters,
    () => {
        syncLocalFromProps();
    },
    { deep: true }
);

onMounted(() => {
    syncLocalFromProps();
    if (
        localFilters.search ||
        localFilters.status ||
        localFilters.date_from ||
        localFilters.date_to
    ) {
        showAdvancedFilters.value = true;
    }
});

const hasActiveFilters = computed(() => {
    return (
        activeBucket.value !== '' ||
        localFilters.status !== '' ||
        localFilters.search !== '' ||
        localFilters.date_from !== '' ||
        localFilters.date_to !== ''
    );
});

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
};

function applyBucket(bucket) {
    router.get(
        '/my-orders',
        bucket ? { bucket } : {},
        {
            preserveState: true,
            preserveScroll: false,
        }
    );
}

const applyFilters = () => {
    router.get(
        '/my-orders',
        {
            status: localFilters.status || undefined,
            search: localFilters.search || undefined,
            date_from: localFilters.date_from || undefined,
            date_to: localFilters.date_to || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const clearFilters = () => {
    localFilters.status = '';
    localFilters.search = '';
    localFilters.date_from = '';
    localFilters.date_to = '';

    router.get('/my-orders', {}, {
        preserveState: true,
        preserveScroll: false,
    });
};

const confirmCancel = (order) => {
    if (confirm(`Are you sure you want to cancel order ${order.order_number}? This action cannot be undone.`)) {
        router.post(`/orders/${order.order_number}/cancel`, {}, {
            onSuccess: () => {
            },
            onError: (errors) => {
                console.error('[OrdersIndex] Cancel failed', errors);
            }
        });
    }
};

const confirmReceived = (order) => {
    if (confirm(`Confirm that you received order ${order.order_number}? This completes the order and releases payment held by the platform to the seller.`)) {
        router.post(`/orders/${order.order_number}/confirm-received`, {}, {
            onSuccess: () => {
            },
            onError: (errors) => {
                console.error('[OrdersIndex] Confirm received failed', errors);
            }
        });
    }
};

const statusMessage = (s) => customerOrderStatusMessage(s);

const tabs = [
    { value: '', label: 'All' },
    { value: 'pay', label: 'To pay' },
    { value: 'ship', label: 'To ship' },
    { value: 'receive', label: 'To receive' },
];

// The five steps of a normal order, used for the small progress bar on each card.
const orderSteps = ['pending', 'approved', 'packed', 'shipped', 'delivered'];
const stepIndex = (s) => orderSteps.indexOf(String(s).toLowerCase());

const canPayNow = (order) => {
    return !!order.can_pay_now;
};

const payNow = (order) => {
    router.post(`/orders/${order.order_number}/pay-now`);
};
</script>
