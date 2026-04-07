<template>
    <MainLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Saved Addresses</h1>
                    <p class="text-gray-600 mt-2">Manage your delivery addresses for faster checkout</p>
                </div>
                <button 
                    @click="showAddForm = true"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium flex items-center gap-2"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New Address
                </button>
            </div>

            <!-- Add/Edit Form Modal -->
            <div v-if="showAddForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ editingAddress ? 'Edit Address' : 'Add New Address' }}</h2>
                    <div v-if="Object.keys(errors).length" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ Object.values(errors)[0] }}
                    </div>
                    
                    <form @submit.prevent="saveAddress" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Label (Optional)</label>
                                <input 
                                    v-model="form.label"
                                    type="text"
                                    placeholder="e.g., Home, Office"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p v-if="errors.label" class="text-red-500 text-sm mt-1">{{ errors.label }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Recipient Name *</label>
                                <input 
                                    v-model="form.recipient_name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p v-if="errors.recipient_name" class="text-red-500 text-sm mt-1">{{ errors.recipient_name }}</p>
                            </div>
                        </div>

                        <!-- City & Barangay -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">City/Municipality *</label>
                                <select
                                    v-model="selectedCity"
                                    @change="onCityChange"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
                                >
                                    <option value="">Select City</option>
                                    <option v-for="(data, city) in cities" :key="city" :value="city">
                                        {{ city }}
                                    </option>
                                </select>
                                <p v-if="errors.city" class="text-red-500 text-sm mt-1">{{ errors.city }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Barangay *</label>
                                <select
                                    v-if="availableBarangays.length > 0"
                                    v-model="selectedBarangay"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
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
                                    placeholder="Enter barangay name"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p v-if="errors.barangay" class="text-red-500 text-sm mt-1">{{ errors.barangay }}</p>
                            </div>
                        </div>

                        <div v-if="selectedBarangay === 'other' && availableBarangays.length > 0">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Barangay Name *</label>
                            <input
                                v-model="manualBarangay"
                                type="text"
                                required
                                placeholder="Type your barangay name"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Complete Address *</label>
                            <input 
                                v-model="form.address_line"
                                type="text"
                                required
                                placeholder="e.g., Blk 5 Lot 10 Sampaguita St., Golden Meadows Subd."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <p v-if="errors.address_line" class="text-red-500 text-sm mt-1">{{ errors.address_line }}</p>
                        </div>

                        <!-- Address Pin Location -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                Pin Exact Location
                                <span class="text-xs font-normal text-gray-500 italic">(Optional — drop a pin to auto-fill City &amp; Barangay)</span>
                            </label>
                            <MapPicker 
                                v-model:lat="form.latitude" 
                                v-model:lng="form.longitude"
                                :geocodeQuery="geocodeQuery"
                                @update:address="onMapAddressPicked"
                                height="250px"
                            />
                            <!-- Detected location banner -->
                            <transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-1"
                            >
                                <p v-if="detectedLocation" class="mt-2 text-xs text-blue-800 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                    {{ detectedLocation }}
                                </p>
                            </transition>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Zip Code *</label>
                                <input
                                    v-model="zipCode"
                                    type="text"
                                    readonly
                                    class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-600"
                                    placeholder="Auto-filled"
                                />
                                <p v-if="errors.zip_code" class="text-red-500 text-sm mt-1">{{ errors.zip_code }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Contact Number *</label>
                                <input 
                                    v-model="form.contact_number"
                                    @input="sanitizeContactNumber"
                                    type="tel"
                                    required
                                    inputmode="numeric"
                                    pattern="09[0-9]{9}"
                                    maxlength="11"
                                    placeholder="09XX XXX XXXX"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p v-if="errors.contact_number" class="text-red-500 text-sm mt-1">{{ errors.contact_number }}</p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input 
                                v-model="form.is_default"
                                type="checkbox"
                                class="h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                            />
                            <label class="ml-3 text-sm font-medium text-gray-700">Set as default address</label>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button 
                                type="button"
                                @click="cancelForm"
                                class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
                            >
                                {{ editingAddress ? 'Update Address' : 'Save Address' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Addresses List -->
            <div v-if="addresses.length > 0" class="space-y-4">
                <div 
                    v-for="address in addresses" 
                    :key="address.id"
                    class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition"
                >
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-bold text-gray-900">{{ address.label || 'Address' }}</h3>
                                <span v-if="address.is_default" class="bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-semibold">
                                    Default
                                </span>
                            </div>
                            <p class="font-semibold text-gray-900">{{ address.recipient_name }}</p>
                            <p class="text-gray-600">{{ address.contact_number }}</p>
                            <p class="text-gray-700 mt-2">{{ address.full_address }}</p>
                        </div>

                        <div class="flex gap-2">
                            <button 
                                v-if="!address.is_default"
                                @click="setAsDefault(address)"
                                class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                            >
                                Set Default
                            </button>
                            <button 
                                @click="editAddress(address)"
                                class="text-gray-600 hover:text-gray-700"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button 
                                @click="deleteAddress(address)"
                                class="text-red-600 hover:text-red-700"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-xl shadow-md p-12 text-center">
                <svg class="h-20 w-20 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Saved Addresses</h3>
                <p class="text-gray-600 mb-6">Add an address for faster checkout</p>
                <button 
                    @click="showAddForm = true"
                    class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
                >
                    Add Your First Address
                </button>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import MapPicker from '@/Components/MapPicker.vue';

const props = defineProps({
    addresses: Array,
    cities: Object,
    barangays: Object,
});
const page = usePage();
const errors = page.props.errors || {};

const showAddForm = ref(false);
const editingAddress = ref(null);
const selectedCity = ref('');
const selectedBarangay = ref('');
const manualBarangay = ref('');
const zipCode = ref('');

// For map ↔ form sync
const geocodeQuery = ref(null);
const detectedLocation = ref('');
let detectedTimer = null;

// Prevents circular updates when we auto-fill the form from a map pin
let isProgrammaticChange = false;

const form = reactive({
    label: '',
    recipient_name: '',
    contact_number: '',
    address_line: '',
    barangay: '',
    city: '',
    province: 'Cavite',
    zip_code: '',
    latitude: null,
    longitude: null,
    is_default: false,
});

// ─── Fuzzy match a raw OSM string against a list of known names ───────────────
const normalize = (str) =>
    String(str)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')   // strip diacritics (ñ → n, etc.)
        .replace(/[^a-z0-9\s]/g, '')       // remove punctuation
        .replace(/\s+/g, ' ')
        .trim();

const fuzzyMatch = (input, list) => {
    if (!input || !list || !list.length) return null;
    const normalInput = normalize(input);
    if (!normalInput) return null;

    // 1. Exact (case/diacritic insensitive)
    const exact = list.find(item => normalize(item) === normalInput);
    if (exact) return exact;

    // 2. List item starts with input (e.g. "Molino" → "Molino I")
    const startsWith = list.find(item => normalize(item).startsWith(normalInput + ' ') || normalize(item) === normalInput);
    if (startsWith) return startsWith;

    // 3. Input contains list item (e.g. "Bacoor City" → "Bacoor")
    const superString = list.find(item => normalInput.includes(normalize(item)));
    if (superString) return superString;

    // 4. List item contains input (partial)
    const subString = list.find(item => normalize(item).includes(normalInput));
    if (subString) return subString;

    return null;
};

// ─── Called when MapPicker emits reverse-geocoded city + barangay ─────────────
const onMapAddressPicked = ({ city, barangay }) => {
    if (!city && !barangay) return;

    isProgrammaticChange = true;

    const cityKeys = Object.keys(props.cities || {});
    const matchedCity = fuzzyMatch(city, cityKeys);

    let matchedBrgy = null;

    if (matchedCity) {
        selectedCity.value = matchedCity;
        // update zip, available barangays, etc. (without triggering geocodeQuery)
        _applyCityChange(matchedCity);

        // Now match the barangay against the newly available list
        if (barangay) {
            const brgys = props.barangays?.[matchedCity] || [];
            matchedBrgy = fuzzyMatch(barangay, brgys);

            if (matchedBrgy) {
                selectedBarangay.value = matchedBrgy;
            } else {
                selectedBarangay.value = 'other';
                manualBarangay.value = barangay;
            }
        }
    }

    // Show detected banner
    const cityLabel = matchedCity || city || '';
    const brgyLabel = matchedBrgy || (barangay ? `${barangay} (unmatched)` : '');
    if (cityLabel) {
        detectedLocation.value = `Detected: ${cityLabel}${brgyLabel ? ' · Brgy. ' + brgyLabel : ''}`;
        clearTimeout(detectedTimer);
        detectedTimer = setTimeout(() => { detectedLocation.value = ''; }, 5000);
    }

    isProgrammaticChange = false;
};

// ─── Internal city-change logic (shared between user-driven and programmatic) ─
const _applyCityChange = (city) => {
    selectedBarangay.value = '';
    manualBarangay.value = '';
    form.city = city || '';
    if (city && props.cities[city]) {
        zipCode.value = props.cities[city].zip;
        form.zip_code = zipCode.value;
        if ((props.barangays?.[city] || []).length === 0) {
            selectedBarangay.value = 'other';
        }
    } else {
        zipCode.value = '';
        form.zip_code = '';
    }
};

// ─── User selects city from dropdown ─────────────────────────────────────────
const onCityChange = () => {
    _applyCityChange(selectedCity.value);
    // Pan map to the chosen city (skip when called programmatically from pin)
    if (!isProgrammaticChange && selectedCity.value) {
        geocodeQuery.value = `${selectedCity.value}, Cavite, Philippines`;
    }
};

// ─── User selects barangay from dropdown ─────────────────────────────────────
watch(selectedBarangay, (brgy) => {
    if (!isProgrammaticChange && brgy && brgy !== 'other' && selectedCity.value) {
        geocodeQuery.value = `Barangay ${brgy}, ${selectedCity.value}, Cavite, Philippines`;
    }
});

watch([selectedCity, selectedBarangay, manualBarangay, zipCode], () => {
    form.city = selectedCity.value || '';
    form.zip_code = zipCode.value || '';
    form.barangay = selectedBarangay.value === 'other'
        ? (manualBarangay.value || '')
        : (selectedBarangay.value || '');
});

const availableBarangays = computed(() => {
    if (!selectedCity.value || !props.barangays) return [];
    return props.barangays[selectedCity.value] || [];
});

const resetForm = () => {
    form.label = '';
    form.recipient_name = '';
    form.contact_number = '';
    form.address_line = '';
    form.barangay = '';
    form.city = '';
    form.province = 'Cavite';
    form.zip_code = '';
    form.latitude = null;
    form.longitude = null;
    form.is_default = false;
    selectedCity.value = '';
    selectedBarangay.value = '';
    manualBarangay.value = '';
    zipCode.value = '';
    geocodeQuery.value = null;
    detectedLocation.value = '';
    editingAddress.value = null;
};

const cancelForm = () => {
    showAddForm.value = false;
    resetForm();
};

const editAddress = (address) => {
    editingAddress.value = address;
    form.label = address.label || '';
    form.recipient_name = address.recipient_name;
    form.contact_number = address.contact_number;
    form.address_line = address.address_line;
    form.barangay = address.barangay;
    form.city = address.city;
    form.province = address.province;
    form.zip_code = address.zip_code;
    form.latitude = address.latitude;
    form.longitude = address.longitude;
    form.is_default = address.is_default;
    selectedCity.value = address.city || '';
    zipCode.value = address.zip_code || '';

    const cityBarangays = props.barangays?.[address.city] || [];
    if (cityBarangays.includes(address.barangay)) {
        selectedBarangay.value = address.barangay;
        manualBarangay.value = '';
    } else if (address.barangay) {
        selectedBarangay.value = 'other';
        manualBarangay.value = address.barangay;
    } else {
        selectedBarangay.value = '';
        manualBarangay.value = '';
    }
    showAddForm.value = true;
};

const saveAddress = () => {
    if (editingAddress.value) {
        router.put(`/addresses/${editingAddress.value.id}`, form, {
            onSuccess: () => {
                showAddForm.value = false;
                resetForm();
            }
        });
    } else {
        router.post('/addresses', form, {
            onSuccess: () => {
                showAddForm.value = false;
                resetForm();
            }
        });
    }
};

const setAsDefault = (address) => {
    router.post(`/addresses/${address.id}/default`);
};

const deleteAddress = (address) => {
    if (confirm('Are you sure you want to delete this address?')) {
        router.delete(`/addresses/${address.id}`);
    }
};

const sanitizeContactNumber = () => {
    form.contact_number = String(form.contact_number || '').replace(/\D/g, '').slice(0, 11);
};
</script>
