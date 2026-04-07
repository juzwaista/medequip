<template>
    <MainLayout>
        <div class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 py-6 sm:py-8 pb-24 md:pb-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
                <p class="text-gray-600 mt-2">Complete your order</p>
            </div>

            <form @submit.prevent="submitOrder" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Order Form -->
                <div class="lg:col-span-2 space-y-6">
                    <div
                        v-if="cart_has_prescription_items"
                        class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900"
                    >
                        <p class="font-semibold">This order includes prescription medicine</p>
                        <p class="mt-1 text-blue-800/90">
                            After placing the order, you’ll be asked to upload a photo of your prescription. Payment (card, GCash, or Maya) is available after the distributor approves it. Wallet checkout is not available for these orders.
                        </p>
                    </div>
                    <!-- Error Display -->
                    <div v-if="form.errors && Object.keys(form.errors).length > 0" class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800 mb-2">Please correct the following errors:</h3>
                                <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                                    <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Information -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-900">Delivery Information</h2>
                            <Link href="/addresses" class="text-sm font-semibold text-blue-600 hover:text-blue-800">Manage Addresses</Link>
                        </div>
                        
                        <div class="space-y-4">
                            <!-- No Saved Addresses -->
                            <div v-if="savedAddresses.length === 0" class="bg-amber-50 rounded-lg p-6 text-center border border-amber-200">
                                <svg class="w-12 h-12 text-amber-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <h3 class="text-lg font-bold text-amber-900 mb-1">No saved addresses</h3>
                                <p class="text-amber-800 text-sm mb-4">Please add a delivery address to proceed with checkout.</p>
                                <a href="/addresses" class="inline-block bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-700 transition">Manage Addresses</a>
                            </div>

                            <!-- Saved Addresses -->
                            <div v-if="savedAddresses.length > 0" class="mb-6 p-4 rounded-lg border border-gray-200 bg-gray-50">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Select Delivery Address *</label>
                                <select 
                                    v-model="selectedSavedAddress"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white min-h-[44px] touch-manipulation"
                                >
                                    <option value="" disabled hidden>Select a delivery address</option>
                                    <option v-for="addr in savedAddresses" :key="addr.id" :value="addr.id">
                                        {{ addr.label ? `${addr.label}: ` : '' }}{{ addr.address_line }}, Brgy. {{ addr.barangay }}, {{ addr.city }}
                                    </option>
                                </select>
                            </div>

                            <!-- Selected Address Preview -->
                            <div v-if="selectedSavedAddress" class="mt-4 p-5 rounded-xl border-2 border-blue-500 bg-blue-50 relative overflow-hidden">
                                <div class="absolute top-0 right-0 pt-4 pr-4 text-blue-500">
                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                
                                <div v-if="currentAddressObj" class="flex flex-col">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-bold text-gray-900">{{ currentAddressObj.label || 'Delivery Address' }}</h3>
                                        <span v-if="currentAddressObj.is_default" class="bg-blue-200 text-blue-800 text-xs px-2.5 py-0.5 rounded-full font-semibold border border-blue-300">
                                            Default
                                        </span>
                                    </div>
                                    <p class="font-bold text-gray-900">{{ currentAddressObj.recipient_name }}</p>
                                    <p class="text-gray-700 font-medium">{{ currentAddressObj.contact_number }}</p>
                                    <p class="text-gray-600 mt-2">{{ form.delivery_address }}</p>
                                </div>
                            </div>

                            <!-- Order Notes -->
                            <div v-if="savedAddresses.length > 0" class="pt-4 border-t border-gray-100">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Order Notes (Optional)</label>
                                <textarea 
                                    v-model="form.notes"
                                    rows="2"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Special instructions for your order"
                                ></textarea>
                            </div>
                        </div>
                    </div>


                    <!-- Order Items -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Order Items</h2>
                        
                        <div class="space-y-3">
                            <div 
                                v-for="item in cartItems" 
                                :key="item.line_key"
                                class="flex justify-between items-center py-3 border-b last:border-0"
                            >
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900">{{ item.product.name }}</p>
                                    <p v-if="item.variation_label" class="text-sm text-blue-700 font-medium">{{ item.variation_label }}</p>
                                    <p class="text-sm text-gray-600">{{ item.product.brand || 'Generic' }}</p>
                                    <span v-if="item.is_wholesale" class="inline-block mt-1 bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                        Wholesale Price
                                    </span>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">{{ item.quantity }} × ₱{{ Number(item.unit_price).toLocaleString() }}</p>
                                    <p class="font-semibold text-gray-900">₱{{ Number(item.subtotal).toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 lg:sticky lg:top-24">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Order Summary</h2>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal ({{ cartItems.length }} items)</span>
                                <span>₱{{ Number(subtotal).toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Delivery Fee</span>
                                <span>₱{{ Number(shipping_fee_total || 0).toLocaleString() }}</span>
                            </div>
                            <p v-if="distributor_count > 1" class="text-xs text-gray-500">
                                Delivery fees vary per shipment based on required vehicle size.
                            </p>
                            <div class="border-t pt-3 flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span class="text-blue-600">₱{{ Number(grandTotal).toLocaleString() }}</span>
                            </div>
                        </div>

                        <!-- Payment Method Selection -->
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Payment Method *</h3>
                            <div class="flex flex-wrap gap-2">
                                <!-- GCash -->
                                <label :class="[
                                    'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 cursor-pointer transition-all select-none',
                                    form.payment_method === 'gcash'
                                        ? 'border-blue-500 bg-blue-50 shadow-sm'
                                        : 'border-gray-200 hover:border-gray-300 bg-white'
                                ]">
                                    <input type="radio" v-model="form.payment_method" value="gcash" class="sr-only" />
                                    <!-- GCash logo -->
                                    <svg width="22" height="22" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="100" height="100" rx="20" fill="#007DFE"/>
                                        <path d="M73 50c0 12.703-10.297 23-23 23S27 62.703 27 50s10.297-23 23-23c6.364 0 12.12 2.578 16.3 6.75l-6.6 6.6A13 13 0 0050 37c-7.18 0-13 5.82-13 13s5.82 13 13 13c5.89 0 10.86-3.92 12.4-9.32H50V47h23v3z" fill="white"/>
                                    </svg>
                                    <span class="text-sm font-semibold" :class="form.payment_method === 'gcash' ? 'text-blue-700' : 'text-gray-700'">GCash</span>
                                    <svg v-if="form.payment_method === 'gcash'" class="h-4 w-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </label>

                                <!-- Maya -->
                                <label :class="[
                                    'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 cursor-pointer transition-all select-none',
                                    form.payment_method === 'paymaya'
                                        ? 'border-green-500 bg-green-50 shadow-sm'
                                        : 'border-gray-200 hover:border-gray-300 bg-white'
                                ]">
                                    <input type="radio" v-model="form.payment_method" value="paymaya" class="sr-only" />
                                    <!-- Maya logo -->
                                    <svg width="22" height="22" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="100" height="100" rx="20" fill="#2CC84D"/>
                                        <text x="50" y="68" font-family="Arial" font-weight="bold" font-size="40" text-anchor="middle" fill="white">M</text>
                                    </svg>
                                    <span class="text-sm font-semibold" :class="form.payment_method === 'paymaya' ? 'text-green-700' : 'text-gray-700'">Maya</span>
                                    <svg v-if="form.payment_method === 'paymaya'" class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </label>

                                <!-- Credit/Debit Card -->
                                <label :class="[
                                    'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 cursor-pointer transition-all select-none',
                                    form.payment_method === 'card'
                                        ? 'border-purple-500 bg-purple-50 shadow-sm'
                                        : 'border-gray-200 hover:border-gray-300 bg-white'
                                ]">
                                    <input type="radio" v-model="form.payment_method" value="card" class="sr-only" />
                                    <!-- Card icon -->
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="24" height="24" rx="4" fill="#7C3AED"/>
                                        <rect x="2" y="7" width="20" height="10" rx="1.5" fill="white" fill-opacity="0.2"/>
                                        <rect x="2" y="10" width="20" height="3" fill="white" fill-opacity="0.6"/>
                                        <rect x="4" y="14" width="5" height="1.5" rx="0.75" fill="white"/>
                                    </svg>
                                    <span class="text-sm font-semibold" :class="form.payment_method === 'card' ? 'text-purple-700' : 'text-gray-700'">Card</span>
                                    <svg v-if="form.payment_method === 'card'" class="h-4 w-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </label>

                                <!-- Wallet -->
                                <label :class="[
                                    'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 transition-all select-none',
                                    cart_has_prescription_items
                                        ? 'border-gray-100 bg-gray-100 cursor-not-allowed opacity-60'
                                        : form.payment_method === 'wallet'
                                            ? 'border-emerald-500 bg-emerald-50 shadow-sm cursor-pointer'
                                            : 'border-gray-200 hover:border-gray-300 bg-white cursor-pointer'
                                ]">
                                    <input
                                        type="radio"
                                        v-model="form.payment_method"
                                        value="wallet"
                                        class="sr-only"
                                        :disabled="cart_has_prescription_items"
                                    />
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="24" height="24" rx="4" fill="#059669"/>
                                        <path d="M4 8a2 2 0 012-2h12a2 2 0 012 2v2H4V8z" fill="white" fill-opacity="0.3"/>
                                        <path d="M4 10h16v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6z" fill="white" fill-opacity="0.6"/>
                                        <circle cx="16.5" cy="13.5" r="1.5" fill="#059669"/>
                                    </svg>
                                    <span class="text-sm font-semibold" :class="form.payment_method === 'wallet' ? 'text-emerald-700' : 'text-gray-700'">Wallet</span>
                                </label>

                                <!-- Cash on Delivery -->
                                <label :class="[
                                    'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 transition-all select-none',
                                    cod_available
                                        ? form.payment_method === 'cod'
                                            ? 'border-orange-400 bg-orange-50 shadow-sm cursor-pointer'
                                            : 'border-gray-200 hover:border-gray-300 bg-white cursor-pointer'
                                        : 'border-gray-100 bg-gray-100 cursor-not-allowed opacity-60'
                                ]">
                                    <input type="radio" v-model="form.payment_method" value="cod" class="sr-only" :disabled="!cod_available" />
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="24" height="24" rx="4" fill="#EA580C"/>
                                        <rect x="2" y="7" width="20" height="10" rx="1.5" fill="white" fill-opacity="0.25"/>
                                        <circle cx="12" cy="12" r="3" fill="white" fill-opacity="0.7"/>
                                        <path d="M4 9.5h2M18 9.5h2M4 14.5h2M18 14.5h2" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                    <span class="text-sm font-semibold" :class="form.payment_method === 'cod' ? 'text-orange-700' : 'text-gray-700'">Cash on Delivery</span>
                                    <svg v-if="form.payment_method === 'cod'" class="h-4 w-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </label>
                            </div>
                            <p v-if="form.payment_method === 'wallet'" class="mt-2 text-xs text-gray-600">
                                Wallet Balance: <span class="font-semibold">₱{{ Number(wallet_balance || 0).toLocaleString() }}</span>
                            </p>
                            <p v-if="form.payment_method === 'wallet' && Number(wallet_balance || 0) < grandTotal" class="mt-1 text-xs text-red-600">
                                Insufficient wallet balance for this checkout total.
                            </p>
                            <p v-if="cart_has_prescription_items" class="mt-2 text-xs text-gray-600">
                                Wallet is unavailable when the cart includes prescription medicine.
                            </p>
                            <p v-if="!cod_available" class="mt-2 text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                                Cash on delivery isn’t available when too many of your past orders were rejected (including prescription declines). Your current rate is about
                                <strong>{{ Number(cod_rejection_rate_percent || 0).toFixed(1) }}%</strong>
                                — use card, e-wallet, or Maya instead.
                            </p>
                        </div>

                        <!-- Payment info banner -->
                        <div v-if="form.payment_method === 'cod'" class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-6">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-orange-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8a2 2 0 012-2z"/></svg>
                                <p class="text-sm text-orange-800">
                                    <strong>Cash on Delivery:</strong> Please hand <strong>₱{{ Number(grandTotal).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</strong> to your courier upon receiving the item.
                                </p>
                            </div>
                        </div>
                        <div v-else class="bg-green-50 border border-green-200 rounded-lg p-3 mb-6">
                            <div class="flex items-start">
                                <svg class="h-5 w-5 text-green-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <p class="text-xs text-green-800">
                                    <strong>Buyer protection:</strong> Your payment is held by the platform until you confirm receipt of your order.
                                </p>
                            </div>
                        </div>

                        <button 
                            type="submit"
                            :disabled="form.processing || !isFormValid"
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-xl hover:shadow-xl transition font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        >
                            <svg v-if="form.processing" class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Processing...' : `Pay ₱${Number(grandTotal).toLocaleString()} & Place Order` }}
                        </button>

                        <a 
                            href="/cart"
                            class="block w-full text-center text-blue-600 hover:text-blue-700 mt-4 font-medium"
                        >
                            ← Back to Cart
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    cartItems: Array,
    subtotal: Number,
    shipping_fee_per_order: {
        type: Number,
        default: 0
    },
    shipping_fee_total: {
        type: Number,
        default: 0
    },
    estimated_total: {
        type: Number,
        default: 0
    },
    distributor_count: {
        type: Number,
        default: 1
    },
    wallet_balance: {
        type: Number,
        default: 0
    },
    cities: Object,
    barangays: Object,
    savedAddresses: {
        type: Array,
        default: () => []
    },
    cart_has_prescription_items: {
        type: Boolean,
        default: false,
    },
    cod_available: {
        type: Boolean,
        default: true,
    },
    cod_rejection_rate_percent: {
        type: Number,
        default: 0,
    },
});

// Address fields
const selectedSavedAddress = ref('');
const currentAddressObj = ref(null);

// Auto-fill from saved address
watch(selectedSavedAddress, (newId) => {
    if (!newId) {
        currentAddressObj.value = null;
        return;
    }
    
    const address = props.savedAddresses.find(a => a.id === newId);
    if (address) {
        currentAddressObj.value = address;
        form.customer_name = address.recipient_name || '';
        form.contact_number = String(address.contact_number || '').replace(/\D/g, '').slice(0, 11);
        
        const parts = [];
        if (address.address_line) parts.push(address.address_line);
        if (address.barangay) parts.push('Brgy. ' + address.barangay);
        if (address.city) parts.push(address.city + ', Cavite');
        if (address.zip_code) parts.push(address.zip_code);
        
        form.delivery_address = parts.join(', ');
        form.delivery_latitude = address.latitude || null;
        form.delivery_longitude = address.longitude || null;
    }
});

onMounted(() => {
    const defaultAddress = props.savedAddresses.find(addr => addr.is_default) || props.savedAddresses[0];
    if (defaultAddress) {
        selectedSavedAddress.value = defaultAddress.id;
    }
});

watch(
    () => props.cart_has_prescription_items,
    (rx) => {
        if (rx && form.payment_method === 'wallet') {
            form.payment_method = 'gcash';
        }
    },
    { immediate: true }
);

watch(
    () => props.cod_available,
    (ok) => {
        if (!ok && form.payment_method === 'cod') {
            form.payment_method = 'gcash';
        }
    },
    { immediate: true }
);

const form = useForm({
    customer_name: '',
    delivery_address: '',
    delivery_latitude: null,
    delivery_longitude: null,
    contact_number: '',
    notes: '',
    payment_method: 'gcash',
    reference_number: '',
    proof: null,
});

const sanitizeContactNumber = () => {
    form.contact_number = String(form.contact_number || '').replace(/\D/g, '').slice(0, 11);
};

// Payment method options — compact pill style, no external icon URLs
const paymentMethods = [
    { value: 'gcash',   label: 'GCash' },
    { value: 'paymaya', label: 'Maya' },
    { value: 'card',    label: 'Card' },
    { value: 'wallet',  label: 'Wallet' },
];

const grandTotal = computed(() => Number(props.subtotal || 0) + Number(props.shipping_fee_total || 0));

// Form validation
const isFormValid = computed(() => {
    const hasAddress = !!selectedSavedAddress.value && form.customer_name.length >= 2 && form.delivery_address.length > 5;
    const hasPaymentMethod = !!form.payment_method;
    const walletOk =
        form.payment_method !== 'wallet' ||
        (!props.cart_has_prescription_items && Number(props.wallet_balance || 0) >= grandTotal.value);

    return hasAddress && hasPaymentMethod && walletOk;
});

const submitOrder = () => {
    form.post('/orders', {
        preserveScroll: true,
        onSuccess: () => {
            // Redirected to confirmation page
        },
        onError: (errors) => {
            console.error('Order placement errors:', errors);
        }
    });
};
</script>
