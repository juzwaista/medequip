<template>
    <article
        class="group relative flex h-full min-h-0 flex-col overflow-hidden rounded-card border border-line bg-white transition-shadow hover:border-brand hover:shadow-[0_0_0_1px_#0B6E6B]"
    >
        <!-- Image -->
        <div class="relative flex aspect-[4/3] items-center justify-center border-b border-line bg-[#F7FAFA] p-4">
            <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="product.name"
                loading="lazy"
                class="max-h-full max-w-full object-contain mix-blend-multiply"
                :class="{ 'opacity-50 grayscale': isUnavailable }"
            />
            <svg v-else class="h-12 w-12 text-ink-faint/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-label="No image">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>

            <div class="absolute left-2.5 top-2.5 flex flex-col items-start gap-1">
                <span v-if="!product.is_active" class="rounded-control border border-line bg-white px-2 py-0.5 text-xs font-medium text-ink-soft">Inactive</span>
                <span v-if="showWholesale && product.wholesale_price" class="rounded-control border border-[#B9C7DA] bg-white px-2 py-0.5 text-xs font-medium text-seal">Wholesale pricing</span>
            </div>
        </div>

        <p v-if="isSuspended" class="border-b border-red-200 bg-red-50 px-4 py-2 text-sm text-danger">
            This seller is suspended. Orders are paused.
        </p>

        <!-- Details -->
        <div class="flex flex-1 flex-col gap-1 p-3 sm:p-4">
            <h3 class="line-clamp-2 min-h-[2.6em] text-[15px] font-semibold leading-snug tracking-tight text-ink sm:text-base">
                <!-- The title link stretches over the whole card so the card is one click target
                     without nesting the seller link inside another anchor. -->
                <Link :href="'/products/' + product.slug" class="after:absolute after:inset-0 after:content-[''] focus-visible:outline-none focus-visible:after:ring-2 focus-visible:after:ring-inset focus-visible:after:ring-brand">
                    {{ product?.name || 'Unnamed product' }}
                </Link>
            </h3>

            <p class="truncate text-sm text-ink-soft">
                {{ product.brand || 'Generic' }}<template v-if="showCategory && product?.category">, {{ product.category.name }}</template>
            </p>

            <p v-if="reviewCount > 0" class="flex items-center gap-1.5 text-sm text-ink-soft">
                <svg class="h-3.5 w-3.5 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 1.8l2.4 5 5.5.7-4 3.8 1 5.4L10 14l-4.9 2.7 1-5.4-4-3.8 5.5-.7z" />
                </svg>
                <span class="font-semibold tabular-nums text-ink">{{ Number(product.reviews_avg_stars || 0).toFixed(1) }}</span>
                <span class="tabular-nums">({{ reviewCount.toLocaleString() }})</span>
                <span v-if="unitsSold > 0" class="ml-1 tabular-nums">{{ unitsSold.toLocaleString() }} sold</span>
            </p>

            <div v-if="showSeller && product.distributor" class="relative z-10 mt-0.5">
                <SellerMark
                    :name="product.distributor.company_name"
                    :verified="!!product.distributor.is_verified"
                    :href="'/seller/' + product.distributor.slug"
                />
            </div>

            <div class="mt-auto flex flex-wrap items-end justify-between gap-x-3 gap-y-2 pt-3">
                <div class="min-w-0">
                    <p class="text-lg font-semibold leading-tight tracking-tight tabular-nums text-ink sm:text-xl">
                        ₱{{ Number(product.base_price).toLocaleString() }}
                    </p>
                    <p v-if="showWholesale && product.wholesale_price" class="mt-1 text-[13px] text-brand tabular-nums">
                        ₱{{ Number(product.wholesale_price).toLocaleString() }} wholesale, min. {{ product.wholesale_min_qty || 1 }}
                    </p>
                    <p v-if="availability" class="mt-1 text-[13px]" :class="availability.class">{{ availability.text }}</p>
                </div>
                <span
                    class="inline-flex h-9 items-center justify-center rounded-control border px-3.5 text-sm font-medium transition-colors max-sm:w-full"
                    :class="isUnavailable
                        ? 'border-line bg-mist text-ink-faint'
                        : 'border-brand bg-white text-brand group-hover:bg-brand group-hover:text-white'"
                    aria-hidden="true"
                >
                    {{ isUnavailable ? 'Unavailable' : 'View details' }}
                </span>
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import SellerMark from '@/Components/ui/SellerMark.vue';

const props = defineProps({
    product: {
        type: Object,
        required: true
    },
    showWholesale: { type: Boolean, default: false },
    showStock: { type: Boolean, default: false },
    showSeller: { type: Boolean, default: false },
    showCategory: { type: Boolean, default: true },
});

const stockCount = computed(() => {
    if (!props.product.inventory || !Array.isArray(props.product.inventory)) return 0;
    return props.product.inventory.reduce((total, item) => {
        const q = Number(item.quantity) || 0;
        const r = Number(item.reserved_quantity) || 0;
        return total + (q - r);
    }, 0);
});

const isOutOfStock = computed(() => stockCount.value <= 0);
const isSuspended = computed(() => !!props.product.distributor?.is_suspended);
const isUnavailable = computed(() => isOutOfStock.value || !props.product.is_active || isSuspended.value);

const reviewCount = computed(() => Number(props.product.reviews_count) || 0);
const unitsSold = computed(() => Number(props.product.units_sold) || 0);

// Exact stock is deliberately not shown: only "in stock", a low-stock warning, or out of stock.
const availability = computed(() => {
    if (isOutOfStock.value) return { text: 'Out of stock', class: 'text-danger' };
    if (stockCount.value <= 5) return { text: `Low stock, ${stockCount.value} left`, class: 'text-amber-700' };
    if (props.showStock) return { text: 'In stock', class: 'text-ink-soft' };
    return null;
});
</script>
