<template>
    <MainLayout>
        <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 pb-44 md:pb-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl sm:text-[28px] font-semibold tracking-tight text-ink">Shopping cart</h1>
                    <p class="text-ink-soft mt-1">{{ cartItems.length }} item{{ cartItems.length !== 1 ? 's' : '' }} in your cart</p>
                </div>
                <BaseButton
                    v-if="cartItems.length > 0"
                    variant="secondary"
                    size="sm"
                    @click="editMode = !editMode"
                >
                    {{ editMode ? 'Done' : 'Edit cart' }}
                </BaseButton>
            </div>

            <div v-if="cartItems.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart items -->
                <div class="lg:col-span-2 space-y-3">
                    <!-- Select all -->
                    <div class="bg-white border border-line rounded-card px-4 py-3 flex items-center justify-between gap-3">
                        <label class="flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                @change="toggleSelectAll"
                                class="h-5 w-5 rounded-control border-line accent-brand"
                            />
                            <span class="ml-3 font-medium text-ink">
                                Select all <span class="font-normal text-ink-soft">({{ selectedCount }} of {{ cartItems.length }} selected)</span>
                            </span>
                        </label>

                        <button
                            v-if="editMode && selectedCount > 0"
                            @click="removeSelected"
                            class="text-danger hover:text-red-800 text-sm font-medium flex items-center gap-1.5"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Remove selected
                        </button>
                    </div>

                    <!-- Cart item cards -->
                    <div
                        v-for="item in cartItems"
                        :key="item.line_key"
                        :class="[
                            'bg-white rounded-card border p-4 sm:p-5 transition-colors',
                            selectedItems[item.line_key] ? 'border-brand' : 'border-line',
                        ]"
                    >
                        <div class="flex gap-3 sm:gap-4">
                            <!-- Checkbox -->
                            <div class="flex items-start pt-1">
                                <input
                                    type="checkbox"
                                    :checked="selectedItems[item.line_key]"
                                    @change="toggleItem(item.line_key)"
                                    :aria-label="`Select ${item.product.name}`"
                                    class="h-5 w-5 rounded-control border-line accent-brand"
                                />
                            </div>

                            <!-- Product image -->
                            <div class="w-20 h-20 sm:w-24 sm:h-24 flex-shrink-0 bg-[#F7FAFA] border border-line rounded-control flex items-center justify-center overflow-hidden">
                                <img
                                    v-if="item.product.image_url"
                                    :src="item.product.image_url"
                                    :alt="item.product.name"
                                    class="w-full h-full object-contain p-1 mix-blend-multiply"
                                />
                                <svg v-else class="h-8 w-8 text-ink-faint/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-label="No image">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>

                            <!-- Product details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between gap-2">
                                    <div class="min-w-0">
                                        <Link :href="`/products/${item.product.slug}`" class="font-semibold text-ink hover:text-brand leading-snug">
                                            {{ item.product.name }}
                                        </Link>
                                        <p v-if="item.variation_label" class="text-sm text-brand-dark font-medium mt-0.5">{{ item.variation_label }}</p>
                                        <p v-if="item.units_per_pack > 1" class="text-sm text-ink-soft mt-0.5">Sold per {{ item.unit_label }}, {{ item.units_per_pack }} pcs each ({{ item.pieces }} pcs in this line)</p>
                                        <p class="text-sm text-ink-soft">{{ item.product.brand || 'Generic' }}</p>
                                        <p class="text-sm text-ink-soft">
                                            Sold by
                                            <Link
                                                v-if="item.product.distributor?.slug"
                                                :href="`/seller/${item.product.distributor.slug}`"
                                                class="text-brand hover:underline underline-offset-2"
                                            >
                                                {{ item.product.distributor?.company_name || 'Unknown seller' }}
                                            </Link>
                                            <span v-else>{{ item.product.distributor?.company_name || 'Unknown seller' }}</span>
                                        </p>

                                        <!-- Wholesale tag -->
                                        <div v-if="item.is_wholesale" class="mt-2">
                                            <span class="inline-flex items-center rounded-control border border-brand-soft bg-brand-tint px-2 py-0.5 text-sm font-medium text-brand-dark">
                                                Wholesale price applied
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Remove button (edit mode) -->
                                    <button
                                        v-if="editMode"
                                        @click="removeItem(item.line_key)"
                                        class="text-danger hover:bg-red-50 rounded-control p-2 h-fit"
                                        :aria-label="`Remove ${item.product.name}`"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Quantity and price -->
                                <div class="flex items-end justify-between gap-3 mt-4">
                                    <div class="inline-flex items-center border border-line rounded-control overflow-hidden bg-white" role="group" aria-label="Quantity">
                                        <button
                                            @click="updateQuantity(item.line_key, item.quantity - 1)"
                                            :disabled="item.quantity <= 1"
                                            class="w-10 h-10 flex items-center justify-center text-ink-soft hover:bg-mist disabled:opacity-40 disabled:cursor-not-allowed text-lg"
                                            aria-label="Decrease quantity"
                                        >−</button>
                                        <input
                                            type="number"
                                            :value="item.quantity"
                                            @input="handleQuantityInput($event)"
                                            @change="updateQuantity(item.line_key, $event.target.value)"
                                            min="1"
                                            aria-label="Quantity"
                                            class="w-12 h-10 text-center font-semibold tabular-nums text-ink border-0 border-x border-line focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-tint"
                                        />
                                        <button
                                            @click="updateQuantity(item.line_key, item.quantity + 1)"
                                            class="w-10 h-10 flex items-center justify-center text-ink-soft hover:bg-mist text-lg"
                                            aria-label="Increase quantity"
                                        >+</button>
                                    </div>

                                    <div class="text-right tabular-nums whitespace-nowrap">
                                        <p class="text-sm text-ink-soft">{{ item.quantity }} × ₱{{ Number(item.unit_price).toLocaleString() }}</p>
                                        <p v-if="item.is_wholesale" class="text-sm text-ink-faint line-through">₱{{ Number(item.retail_unit_price * item.quantity).toLocaleString() }}</p>
                                        <p class="text-lg font-semibold tracking-tight text-ink leading-tight">₱{{ Number(item.subtotal).toLocaleString() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white border border-line rounded-card p-5 lg:sticky lg:top-24">
                        <h2 class="text-lg font-semibold tracking-tight text-ink mb-4">Order summary</h2>

                        <dl class="space-y-2.5 mb-5 text-ink-soft">
                            <div class="flex justify-between gap-3">
                                <dt>Subtotal</dt>
                                <dd class="text-ink tabular-nums">₱{{ Number(selectedOriginalSubtotal).toLocaleString() }}</dd>
                            </div>
                            <div v-if="selectedTotalSavings > 0" class="flex justify-between gap-3 text-brand-dark">
                                <dt>Wholesale savings</dt>
                                <dd class="tabular-nums">−₱{{ Number(selectedTotalSavings).toLocaleString() }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt>Estimated shipping</dt>
                                <dd class="text-ink tabular-nums">₱{{ Number(selectedShippingFee).toLocaleString() }}</dd>
                            </div>
                            <div class="pt-3 border-t border-line flex justify-between items-baseline gap-3">
                                <dt class="font-semibold text-ink">Total</dt>
                                <dd class="text-2xl font-semibold tracking-tight text-ink tabular-nums">₱{{ Number(selectedGrandTotal).toLocaleString() }}</dd>
                            </div>
                        </dl>

                        <BaseButton
                            v-if="$page.props.auth.user"
                            @click="proceedToCheckout"
                            :disabled="selectedCount === 0"
                            class="w-full"
                        >
                            Checkout ({{ selectedCount }} {{ selectedCount === 1 ? 'item' : 'items' }})
                        </BaseButton>
                        <BaseButton v-else @click="redirectToLogin" class="w-full">
                            Log in to check out
                        </BaseButton>

                        <Link
                            href="/products"
                            class="block w-full text-center text-brand hover:text-brand-dark hover:underline underline-offset-2 mt-4 font-medium"
                        >
                            Continue shopping
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty cart -->
            <div v-else class="flex flex-col items-center justify-center py-20 text-center">
                <svg class="h-14 w-14 text-ink-faint/60 mb-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h2 class="text-2xl font-semibold tracking-tight text-ink mb-2">Your cart is empty</h2>
                <p class="text-ink-soft mb-6 max-w-xs">Add equipment and supplies from verified distributors and they will show up here.</p>
                <BaseButton href="/products">Browse products</BaseButton>
            </div>

            <!-- Mobile sticky checkout bar: sits above the bottom navigation (4rem tall) -->
            <div
                v-if="cartItems.length > 0"
                class="md:hidden fixed left-0 right-0 bg-white border-t border-line z-40 px-4 py-3"
                style="bottom: calc(4rem + env(safe-area-inset-bottom, 0px))"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-ink-soft">{{ selectedCount }} item{{ selectedCount !== 1 ? 's' : '' }} selected</span>
                    <span class="font-semibold text-ink tabular-nums">₱{{ Number(selectedGrandTotal).toLocaleString() }}</span>
                </div>
                <BaseButton
                    v-if="$page.props.auth.user"
                    @click="proceedToCheckout"
                    :disabled="selectedCount === 0"
                    class="w-full"
                >
                    Checkout ({{ selectedCount }} {{ selectedCount === 1 ? 'item' : 'items' }})
                </BaseButton>
                <BaseButton v-else @click="redirectToLogin" class="w-full">
                    Log in to check out
                </BaseButton>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';

const props = defineProps({
    cartItems: Array,
    subtotal: Number,
    shipping_fee: {
        type: Number,
        default: 0,
    },
    shipping_fee_per_order: {
        type: Number,
        default: 0,
    },
    distributor_count: {
        type: Number,
        default: 1,
    },
    estimated_total: {
        type: Number,
        default: 0,
    },
});

// Edit mode state
const editMode = ref(false);

// Selection state - initialize all items as selected
const selectedItems = ref({});

onMounted(() => {
    props.cartItems.forEach((item) => {
        selectedItems.value[item.line_key] = true;
    });
});

// Computed properties
const selectedCount = computed(() => {
    return Object.values(selectedItems.value).filter(v => v).length;
});

const allSelected = computed(() => {
    return props.cartItems.length > 0 && selectedCount.value === props.cartItems.length;
});

const selectedSubtotal = computed(() => {
    return props.cartItems
        .filter(item => selectedItems.value[item.line_key])
        .reduce((sum, item) => sum + Number(item.subtotal), 0);
});

const selectedOriginalSubtotal = computed(() => {
    return props.cartItems
        .filter(item => selectedItems.value[item.line_key])
        .reduce((sum, item) => sum + (Number(item.retail_unit_price) * item.quantity), 0);
});

const selectedTotalSavings = computed(() => {
    return Math.max(0, selectedOriginalSubtotal.value - selectedSubtotal.value);
});

const selectedShippingFee = computed(() => {
    if (selectedCount.value === 0) return 0;
    const selectedDistributorCount = new Set(
        props.cartItems
            .filter(item => selectedItems.value[item.line_key])
            .map(item => item.product.distributor_id)
            .filter(Boolean)
    ).size;
    return Number(props.shipping_fee_per_order || 0) * Math.max(1, selectedDistributorCount);
});

const selectedGrandTotal = computed(() => {
    return selectedSubtotal.value + selectedShippingFee.value;
});

// Actions
const toggleItem = (lineKey) => {
    selectedItems.value[lineKey] = !selectedItems.value[lineKey];
};

const toggleSelectAll = () => {
    const newValue = !allSelected.value;
    props.cartItems.forEach(item => {
        selectedItems.value[item.line_key] = newValue;
    });
};

const updateQuantity = (lineKey, quantity) => {
    const normalized = Math.max(1, parseInt(quantity, 10) || 1);
    
    router.patch(`/cart/${encodeURIComponent(lineKey)}`, {
        quantity: normalized
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const handleQuantityInput = (event) => {
    const normalized = Math.max(1, parseInt(event.target.value, 10) || 1);
    if (String(normalized) !== String(event.target.value)) {
        event.target.value = normalized;
    }
};

const removeItem = (lineKey) => {
    router.delete(`/cart/${encodeURIComponent(lineKey)}`, {
        preserveScroll: true,
        preserveState: true,
    });
    delete selectedItems.value[lineKey];
};

const removeSelected = () => {
    const selectedKeys = Object.entries(selectedItems.value)
        .filter(([_, selected]) => selected)
        .map(([k]) => k);
    if (selectedKeys.length === 0) return;
    selectedKeys.forEach((lineKey) => {
        router.delete(`/cart/${encodeURIComponent(lineKey)}`, {
            preserveScroll: true,
            preserveState: true,
        });
    });
};

const proceedToCheckout = () => {
    const selectedKeys = Object.entries(selectedItems.value)
        .filter(([_, selected]) => selected)
        .map(([key]) => key);
    
    if (selectedKeys.length === 0) return;

    // Redirect to checkout with selected items as a query parameter
    router.visit('/checkout', {
        data: {
            selected_items: selectedKeys.join(',')
        }
    });
};

const redirectToLogin = () => {
    router.visit('/login');
};
</script>
