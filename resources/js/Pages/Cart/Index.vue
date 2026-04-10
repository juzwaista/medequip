<template>
    <MainLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900">Shopping Cart</h1>
                    <p class="text-gray-500 text-sm mt-1">{{ cartItems.length }} item{{ cartItems.length !== 1 ? 's' : '' }} in your cart</p>
                </div>
                <button
                    v-if="cartItems.length > 0"
                    @click="editMode = !editMode"
                    :class="[
                        'px-4 py-2 rounded-xl font-semibold text-sm transition flex items-center gap-2',
                        editMode ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-blue-100 text-blue-700 hover:bg-blue-200'
                    ]"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ editMode ? 'Done' : 'Edit' }}
                </button>
            </div>

            <div v-if="cartItems.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Select All -->
                    <div class="bg-white rounded-xl shadow-md p-4 flex items-center justify-between">
                        <label class="flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                :checked="allSelected"
                                @change="toggleSelectAll"
                                class="h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                            />
                            <span class="ml-3 text-sm font-medium text-gray-700">
                                Select All ({{ selectedCount }} of {{ cartItems.length }} selected)
                            </span>
                        </label>
                        
                        <button 
                            v-if="editMode && selectedCount > 0"
                            @click="removeSelected"
                            class="text-red-600 hover:text-red-700 text-sm font-medium flex items-center gap-1"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Remove Selected
                        </button>
                    </div>

                    <!-- Cart Item Cards -->
                    <div 
                        v-for="item in cartItems" 
                        :key="item.line_key"
                        :class="[
                            'bg-white rounded-xl shadow-md p-6 transition',
                            selectedItems[item.line_key] ? 'ring-2 ring-blue-500 hover:shadow-lg' : 'hover:shadow-lg',
                            editMode ? 'bg-gray-50' : ''
                        ]"
                    >
                        <div class="flex gap-4">
                            <!-- Checkbox -->
                            <div class="flex items-start pt-1">
                                <input 
                                    type="checkbox" 
                                    :checked="selectedItems[item.line_key]"
                                    @change="toggleItem(item.line_key)"
                                    class="h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                                />
                            </div>

                            <!-- Product Image -->
                            <div class="w-24 h-24 flex-shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center overflow-hidden">
                                <img
                                    v-if="item.product.image_url"
                                    :src="item.product.image_url"
                                    :alt="item.product.name"
                                    class="w-full h-full object-cover"
                                />
                                <svg v-else class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>

                            <!-- Product Details -->
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <div>
                                        <Link :href="`/products/${item.product.id}`" class="text-lg font-semibold text-gray-900 hover:text-blue-600">
                                            {{ item.product.name }}
                                        </Link>
                                        <p v-if="item.variation_label" class="text-sm text-blue-700 font-medium mt-0.5">{{ item.variation_label }}</p>
                                        <p class="text-sm text-gray-600">{{ item.product.brand || 'Generic' }}</p>
                                        <p class="text-sm text-gray-500">
                                            by 
                                            <Link 
                                                v-if="item.product.distributor?.slug"
                                                :href="`/seller/${item.product.distributor.slug}`"
                                                class="text-blue-600 hover:underline"
                                            >
                                                {{ item.product.distributor?.company_name || 'Unknown Seller' }}
                                            </Link>
                                            <span v-else>{{ item.product.distributor?.company_name || 'Unknown Seller' }}</span>
                                        </p>
                                        
                                        <!-- Wholesale Badge -->
                                        <div v-if="item.is_wholesale" class="mt-2">
                                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-semibold">
                                                Wholesale Price Applied
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Remove Button (Edit Mode) -->
                                    <button 
                                        v-if="editMode"
                                        @click="removeItem(item.line_key)"
                                        class="text-red-500 hover:text-red-700 p-2 h-fit"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Quantity and Price -->
                                <div class="flex items-center justify-between mt-4">
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Qty</label>
                                        <div class="flex items-center border-2 border-gray-200 rounded-xl overflow-hidden">
                                            <button
                                                @click="updateQuantity(item.line_key, item.quantity - 1)"
                                                :disabled="item.quantity <= 1"
                                                class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed font-bold text-gray-600"
                                            >−</button>
                                            <input
                                                type="number"
                                                :value="item.quantity"
                                                @input="handleQuantityInput($event)"
                                                @change="updateQuantity(item.line_key, $event.target.value)"
                                                min="1"
                                                class="w-12 text-center text-sm font-bold border-x-2 border-gray-200 py-2 focus:outline-none"
                                            />
                                            <button
                                                @click="updateQuantity(item.line_key, item.quantity + 1)"
                                                class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 font-bold text-gray-600"
                                            >+</button>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ item.quantity }} × ₱{{ Number(item.unit_price).toLocaleString() }}</span>
                                            
                                            <!-- Price / Total -->
                                            <div class="flex flex-col items-end gap-1 shrink-0">
                                                <!-- Original Total -->
                                                <span v-if="item.is_wholesale" class="text-xs text-gray-300 line-through font-medium">₱{{ Number(item.product.base_price * item.quantity).toLocaleString() }}</span>
                                                <span v-else class="text-xs text-gray-400 font-medium mt-0.5">₱{{ Number(item.quantity * item.unit_price).toLocaleString() }}</span>
                                                
                                                <!-- Final Price (Black) -->
                                                <span class="text-base sm:text-lg font-black text-gray-900 tabular-nums leading-none mt-0.5">
                                                    ₱{{ Number(item.subtotal).toLocaleString() }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Order Summary</h2>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between items-center text-xs font-semibold text-gray-400 uppercase tracking-widest px-1">
                                <span>Order Summary</span>
                            </div>
                            
                            <div class="flex justify-between items-center text-sm font-medium text-gray-500 px-1 pt-1">
                                <span>Order Total</span>
                                <span class="text-gray-900 font-bold">₱{{ Number(selectedOriginalSubtotal).toLocaleString() }}</span>
                            </div>

                            <div class="flex justify-between items-center text-sm font-medium text-gray-500 px-1">
                                <span>Shipping Fee</span>
                                <span class="text-gray-900">₱{{ Number(selectedShippingFee).toLocaleString() }}</span>
                            </div>

                            <div v-if="selectedTotalSavings > 0" class="flex justify-between items-center text-[10px] font-bold text-emerald-600 bg-emerald-50/30 px-2 py-1 rounded-md border border-emerald-100/30">
                                <span class="uppercase tracking-tight">Wholesale Savings</span>
                                <span>−₱{{ Number(selectedTotalSavings).toLocaleString() }}</span>
                            </div>

                            <div class="pt-4 border-t border-gray-100 flex flex-col gap-1">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Amount to Pay</span>
                                <div class="flex items-baseline gap-2">
                                    <span v-if="selectedTotalSavings > 0" class="text-sm text-gray-300 line-through font-medium">₱{{ Number(selectedOriginalSubtotal + selectedShippingFee).toLocaleString() }}</span>
                                    <span class="text-3xl font-black text-gray-900 tabular-nums leading-none">₱{{ Number(selectedGrandTotal).toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>

                        <button 
                            v-if="$page.props.auth.user"
                            @click="proceedToCheckout"
                            :disabled="selectedCount === 0"
                            class="block w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white text-center px-6 py-3 rounded-xl hover:shadow-xl transition font-bold disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Checkout ({{ selectedCount }} items)
                        </button>
                        <button 
                            v-else
                            @click="redirectToLogin"
                            class="block w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white text-center px-6 py-3 rounded-xl hover:shadow-xl transition font-bold"
                        >
                            Login to Checkout
                        </button>

                        <Link 
                            href="/products"
                            class="block w-full text-center text-blue-600 hover:text-blue-700 mt-4 font-medium"
                        >
                            ← Continue Shopping
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty Cart -->
            <div v-else class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-28 h-28 bg-blue-50 rounded-full flex items-center justify-center mb-6">
                    <svg class="h-14 w-14 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-gray-900 mb-2">Looks like your cart is empty.</h2>
                <p class="text-gray-500 mb-8 max-w-xs">Stock up on equipment and daily essentials for your practice.</p>
                <Link href="/products" class="inline-flex items-center gap-2 bg-blue-600 text-white px-8 py-3.5 rounded-2xl font-bold hover:bg-blue-700 transition shadow-sm hover:shadow-md">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Go to Shop
                </Link>
            </div>

            <!-- Mobile sticky checkout bar -->
            <div v-if="cartItems.length > 0" class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-2xl z-40 px-4 py-3" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-gray-500 font-medium">{{ selectedCount }} item{{ selectedCount !== 1 ? 's' : '' }} selected</span>
                    <span class="text-sm font-black text-gray-900">₱{{ Number(selectedGrandTotal).toLocaleString() }}</span>
                </div>
                <button
                    v-if="$page.props.auth.user"
                    @click="proceedToCheckout"
                    :disabled="selectedCount === 0"
                    class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Checkout ({{ selectedCount }} items)
                </button>
                <button
                    v-else
                    @click="redirectToLogin"
                    class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition"
                >
                    Login to Checkout
                </button>
            </div>
            </div>

    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

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
        .reduce((sum, item) => sum + (Number(item.product.base_price) * item.quantity), 0);
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
