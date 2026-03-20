<template>
    <MainLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
                <p class="text-gray-600 mt-2">Complete your order</p>
            </div>

            <form @submit.prevent="submitOrder" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Order Form -->
                <div class="lg:col-span-2 space-y-6">
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
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Delivery Information</h2>
                        
                        <div class="space-y-4">
                            <!-- Saved Addresses -->
                            <div v-if="savedAddresses.length > 0" class="mb-6 bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-blue-900">Saved Addresses</h3>
                                    <Link href="/addresses" class="text-xs text-blue-600 hover:underline">Manage Addresses</Link>
                                </div>
                                <select 
                                    v-model="selectedSavedAddress"
                                    class="w-full px-4 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white"
                                >
                                    <option value="">-- Select a saved address --</option>
                                    <option v-for="addr in savedAddresses" :key="addr.id" :value="addr.id">
                                        {{ addr.label ? `${addr.label}: ` : '' }}{{ addr.address_line }}
                                    </option>
                                </select>
                            </div>

                            <!-- Customer Name -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Recipient Name *
                                    <span v-if="form.errors.customer_name" class="text-red-600 font-normal ml-2">{{ form.errors.customer_name }}</span>
                                </label>
                                <input 
                                    v-model="form.customer_name"
                                    type="text"
                                    required
                                    :class="[
                                        'w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                        form.errors.customer_name ? 'border-red-500' : 'border-gray-300'
                                    ]"
                                    placeholder="Juan Dela Cruz"
                                />
                            </div>

                            <!-- City Selection -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        City/Municipality *
                                    </label>
                                    <select 
                                        v-model="selectedCity"
                                        @change="onCityChange"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="">Select City</option>
                                        <option v-for="(data, city) in cities" :key="city" :value="city">
                                            {{ city }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Barangay *
                                    </label>
                                    <select 
                                        v-if="availableBarangays.length > 0"
                                        v-model="selectedBarangay"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="">Select Barangay</option>
                                        <option v-for="brgy in availableBarangays" :key="brgy" :value="brgy">
                                            {{ brgy }}
                                        </option>
                                        <option value="other">Other (type manually)</option>
                                    </select>
                                    <input
                                        v-else
                                        v-model="manualBarangay"
                                        type="text"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Enter barangay name"
                                    />
                                </div>
                            </div>

                            <!-- Manual Barangay Input (when "Other" is selected) -->
                            <div v-if="selectedBarangay === 'other' && availableBarangays.length > 0">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Barangay Name *
                                </label>
                                <input 
                                    v-model="manualBarangay"
                                    type="text"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Type your barangay name"
                                />
                            </div>

                            <!-- Street Address -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Complete Address *
                                    <span class="font-normal text-gray-500">(Block, Lot, Street, Subdivision)</span>
                                </label>
                                <input 
                                    v-model="streetAddress"
                                    type="text"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., Blk 5 Lot 10 Sampaguita St., Golden Meadows Subd."
                                />
                            </div>

                            <!-- Zip Code & Contact -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Zip Code *
                                    </label>
                                    <input 
                                        v-model="zipCode"
                                        type="text"
                                        readonly
                                        class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-600"
                                        placeholder="Auto-filled"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Contact Number *
                                        <span v-if="form.errors.contact_number" class="text-red-600 font-normal ml-2">{{ form.errors.contact_number }}</span>
                                    </label>
                                    <input 
                                        v-model="form.contact_number"
                                        type="tel"
                                        required
                                        :class="[
                                            'w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                            form.errors.contact_number ? 'border-red-500' : 'border-gray-300'
                                        ]"
                                        placeholder="09XX XXX XXXX"
                                    />
                                </div>
                            </div>

                            <!-- Order Notes -->
                            <div>
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
                                :key="item.product.id"
                                class="flex justify-between items-center py-3 border-b last:border-0"
                            >
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900">{{ item.product.name }}</p>
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
                    <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Order Summary</h2>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal ({{ cartItems.length }} items)</span>
                                <span>₱{{ Number(subtotal).toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Delivery Fee</span>
                                <span class="text-green-600 font-semibold">FREE</span>
                            </div>
                            <div class="border-t pt-3 flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span class="text-blue-600">₱{{ Number(subtotal).toLocaleString() }}</span>
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
                            </div>
                        </div>

                        <!-- Escrow Info -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-6">
                            <div class="flex items-start">
                                <svg class="h-5 w-5 text-green-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <p class="text-xs text-green-800">
                                    <strong>Buyer Protection:</strong> Your payment is held securely until you confirm receipt of your order.
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
                            {{ form.processing ? 'Processing...' : `Pay ₱${Number(subtotal).toLocaleString()} & Place Order` }}
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
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    cartItems: Array,
    subtotal: Number,
    cities: Object,
    barangays: Object,
    savedAddresses: {
        type: Array,
        default: () => []
    },
});

// Address fields
const selectedCity = ref('');
const selectedBarangay = ref('');
const manualBarangay = ref('');
const streetAddress = ref('');
const zipCode = ref('');
const selectedSavedAddress = ref('');

// Auto-fill from saved address
watch(selectedSavedAddress, (newId) => {
    if (!newId) return;
    
    const address = props.savedAddresses.find(a => a.id === newId);
    if (address) {
        form.customer_name = address.recipient_name;
        form.contact_number = address.contact_number;
        streetAddress.value = address.address_line;
        
        // Handle location fields
        if (Object.keys(props.cities || {}).includes(address.city)) {
            selectedCity.value = address.city;
            
            // Wait for next tick/computation of availableBarangays
            setTimeout(() => {
                const barangays = props.barangays[address.city] || [];
                if (barangays.includes(address.barangay)) {
                    selectedBarangay.value = address.barangay;
                    manualBarangay.value = '';
                } else {
                    selectedBarangay.value = 'other';
                    manualBarangay.value = address.barangay;
                }
                zipCode.value = address.zip_code;
            }, 50);
        } else {
            // Fallback for custom cities if we support them later
            selectedCity.value = '';
        }
    }
});

// Form data — now includes payment_method
const form = useForm({
    customer_name: '',
    delivery_address: '',
    contact_number: '',
    notes: '',
    payment_method: 'gcash',
    reference_number: '',
    proof: null,
});

// Payment method options — compact pill style, no external icon URLs
const paymentMethods = [
    { value: 'gcash',   label: 'GCash' },
    { value: 'paymaya', label: 'Maya' },
    { value: 'card',    label: 'Card' },
];

// Get available barangays for selected city
const availableBarangays = computed(() => {
    if (!selectedCity.value || !props.barangays) return [];
    return props.barangays[selectedCity.value] || [];
});

// Show manual barangay input when "Other" is selected OR when no barangays available
const showManualBarangay = computed(() => {
    return selectedBarangay.value === 'other' || 
           (selectedCity.value && availableBarangays.value.length === 0);
});

// Auto-fill zip code when city changes
const onCityChange = () => {
    selectedBarangay.value = '';
    manualBarangay.value = '';
    if (selectedCity.value && props.cities[selectedCity.value]) {
        zipCode.value = props.cities[selectedCity.value].zip;
        // Auto-select "other" if no barangays available
        if (availableBarangays.value.length === 0) {
            selectedBarangay.value = 'other';
        }
    } else {
        zipCode.value = '';
    }
};

// Combine address fields into delivery_address
watch([selectedCity, selectedBarangay, manualBarangay, streetAddress, zipCode], () => {
    const parts = [];
    if (streetAddress.value) parts.push(streetAddress.value);
    
    // Use manual barangay if entered, otherwise use selected
    const barangayToUse = showManualBarangay.value && manualBarangay.value 
        ? manualBarangay.value 
        : (selectedBarangay.value !== 'other' ? selectedBarangay.value : '');
    
    if (barangayToUse) parts.push('Brgy. ' + barangayToUse);
    if (selectedCity.value) parts.push(selectedCity.value + ', Cavite');
    if (zipCode.value) parts.push(zipCode.value);
    
    form.delivery_address = parts.join(', ');
});

// Form validation - relaxed to handle manual input
const isFormValid = computed(() => {
    const hasName = form.customer_name.length >= 2;
    const hasCity = !!selectedCity.value;
    const hasBarangay = selectedBarangay.value && (
        selectedBarangay.value !== 'other' || manualBarangay.value.length >= 2
    );
    const hasStreetAddress = streetAddress.value.length >= 5;
    const hasContactNumber = form.contact_number.length >= 7;
    
    // Debug logging
    console.log('Form Validation State:', {
        hasName,
        hasCity,
        hasBarangay,
        hasStreetAddress,
        hasContactNumber,
        'customer_name': form.customer_name,
        'selectedCity': selectedCity.value,
        'selectedBarangay': selectedBarangay.value,
        'manualBarangay': manualBarangay.value,
        'streetAddress': streetAddress.value,
        'contact_number': form.contact_number,
        'isValid': hasName && hasCity && hasBarangay && hasStreetAddress && hasContactNumber
    });
    
    const hasPaymentMethod = !!form.payment_method;
    const hasBankRef = form.payment_method !== 'bank_transfer' || (form.reference_number && form.reference_number.length >= 3);
    
    return hasName && hasCity && hasBarangay && hasStreetAddress && hasContactNumber && hasPaymentMethod && hasBankRef;
});

const submitOrder = () => {
    console.log('submitOrder called!');
    console.log('Form data:', {
        customer_name: form.customer_name,
        delivery_address: form.delivery_address,
        contact_number: form.contact_number,
        notes: form.notes
    });
    console.log('isFormValid:', isFormValid.value);
    
    form.post('/orders', {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Order placed successfully!');
            // Redirected to confirmation page
        },
        onError: (errors) => {
            console.error('Order placement errors:', errors);
        }
    });
};
</script>
