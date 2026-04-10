<template>
    <OwnerLayout>
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Add New Branch</h1>
                <p class="text-gray-600 mt-2">Add a new branch location for {{ distributor.company_name }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Branch Name *</label>
                        <input
                            v-model="form.branch_name"
                            type="text"
                            required
                            placeholder="e.g. Main Branch, Makati Branch"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        <p v-if="form.errors.branch_name" class="text-red-500 text-sm mt-1">{{ form.errors.branch_name }}</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Street / Building / Unit <span class="text-red-500">*</span></label>
                            <input v-model="form.address_line" type="text" placeholder="e.g. Blk 5 Lot 10 Sampaguita St."
                                class="w-full px-4 py-3 border rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                :class="form.errors.address_line ? 'border-red-300 bg-red-50' : 'border-gray-300'"/>
                            <p v-if="form.errors.address_line" class="text-red-500 text-xs mt-1">{{ form.errors.address_line }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">City / Municipality <span class="text-red-500">*</span></label>
                                <select v-model="selectedCity" @change="onCityChange"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white"
                                    :class="form.errors.city ? 'border-red-300 bg-red-50' : ''">
                                    <option value="">Select city</option>
                                    <option v-for="(data, city) in cities" :key="city" :value="city">{{ city }}</option>
                                </select>
                                <p v-if="form.errors.city" class="text-red-500 text-xs mt-1">{{ form.errors.city }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Barangay <span class="text-red-500">*</span></label>
                                <select v-if="availableBarangays.length > 0" v-model="selectedBarangay"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white"
                                    :class="form.errors.barangay ? 'border-red-300 bg-red-50' : ''">
                                    <option value="">Select barangay</option>
                                    <option v-for="brgy in availableBarangays" :key="brgy" :value="brgy">{{ brgy }}</option>
                                    <option value="other">Other (type manually)</option>
                                </select>
                                <input v-else v-model="manualBarangay" type="text" placeholder="Enter barangay name"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                    :class="form.errors.barangay ? 'border-red-300 bg-red-50' : ''"/>
                                <p v-if="form.errors.barangay" class="text-red-500 text-xs mt-1">{{ form.errors.barangay }}</p>
                            </div>
                        </div>

                        <div v-if="selectedBarangay === 'other' && availableBarangays.length > 0">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Barangay Name <span class="text-red-500">*</span></label>
                            <input v-model="manualBarangay" type="text" placeholder="Type your barangay name"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                        </div>

                        <div class="max-w-[150px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Zip Code</label>
                            <input v-model="zipCode" type="text" readonly placeholder="Auto-filled"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 text-gray-500 text-sm cursor-not-allowed"/>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5 flex items-center gap-2">
                                Branch Location Pin <span class="text-red-500">*</span>
                                <span class="text-[10px] font-normal text-gray-400 italic">(Drop a pin to detect address)</span>
                            </label>
                            <MapPicker 
                                v-model:lat="form.latitude" 
                                v-model:lng="form.longitude"
                                :geocodeQuery="geocodeQuery"
                                height="250px"
                                @update:address="onMapAddressPicked"
                            />
                            <p v-if="detectedLocation" class="mt-2 text-xs text-blue-800 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                {{ detectedLocation }}
                            </p>
                            <p v-if="form.errors.latitude" class="text-red-500 text-xs mt-1">{{ form.errors.latitude }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Contact Number</label>
                            <input
                                v-model="form.contact_number"
                                @input="sanitizeContactNumber"
                                type="tel"
                                inputmode="numeric"
                                pattern="09[0-9]{9}"
                                maxlength="11"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.contact_number" class="text-red-500 text-sm mt-1">{{ form.errors.contact_number }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Add Branch' }}
                        </button>
                        <Link
                            :href="`/owner/distributor/${distributor.id}/branches`"
                            class="px-6 py-3 border-2 border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </OwnerLayout>
</template>

import { ref, computed, watch, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import MapPicker from '@/Components/MapPicker.vue';

const props = defineProps({
    distributor: Object,
    cities: Object,
    barangays: Object,
});

// Structured Address Refs
const selectedCity = ref('');
const selectedBarangay = ref('');
const manualBarangay = ref('');
const zipCode = ref('');

// For map <-> form sync
const geocodeQuery = ref(null);
const detectedLocation = ref('');
let detectedTimer = null;
let isProgrammaticChange = false;

// Persistence state
const STORAGE_KEY = `branch_creation_draft_${props.distributor.id}`;
const lastSaved = ref(null);

const form = useForm({
    branch_name: '',
    address: '',
    address_line: '',
    city: '',
    barangay: '',
    latitude: null,
    longitude: null,
    contact_number: '',
    email: '',
});

onMounted(() => {
    const draft = localStorage.getItem(STORAGE_KEY);
    if (draft) {
        try {
            const parsed = JSON.parse(draft);
            Object.keys(parsed).forEach(key => {
                if (parsed[key]) form[key] = parsed[key];
            });

            // Re-sync local refs
            selectedCity.value = form.city;
            _applyCityChange(form.city);
            
            if (form.city && props.barangays?.[form.city]) {
                const list = props.barangays[form.city];
                if (list.includes(form.barangay)) {
                    selectedBarangay.value = form.barangay;
                } else if (form.barangay) {
                    selectedBarangay.value = 'other';
                    manualBarangay.value = form.barangay;
                }
            }
        } catch (e) {
            console.error('Failed to load branch draft', e);
        }
    }
});

// Auto-save draft
watch(() => ({
    branch_name: form.branch_name,
    address_line: form.address_line,
    city: form.city,
    barangay: form.barangay,
    contact_number: form.contact_number,
    email: form.email,
    latitude: form.latitude,
    longitude: form.longitude,
}), (newVal) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
    lastSaved.value = new Date().toLocaleTimeString();
}, { deep: true });

// ─── Fuzzy match helper ──────────────────────────────────────────────────────
const normalize = (str) =>
    String(str)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s]/g, '')
        .replace(/\s+/g, ' ')
        .trim();

const fuzzyMatch = (input, list) => {
    if (!input || !list || !list.length) return null;
    const normalInput = normalize(input);
    if (!normalInput) return null;
    const exact = list.find(item => normalize(item) === normalInput);
    if (exact) return exact;
    const startsWith = list.find(item => normalize(item).startsWith(normalInput + ' ') || normalize(item) === normalInput);
    if (startsWith) return startsWith;
    const subString = list.find(item => normalize(item).includes(normalInput));
    if (subString) return subString;
    return null;
};

// ─── Internal city-change helper ─────────────────────────────────────────────
const _applyCityChange = (city) => {
    selectedBarangay.value = '';
    manualBarangay.value = '';
    if (city && props.cities[city]) {
        zipCode.value = props.cities[city].zip;
    } else {
        zipCode.value = '';
    }
};

const onCityChange = () => {
    _applyCityChange(selectedCity.value);
    if (!isProgrammaticChange && selectedCity.value) {
        geocodeQuery.value = `${selectedCity.value}, Cavite, Philippines`;
    }
};

// User changes barangay — pan map
watch(selectedBarangay, (brgy) => {
    if (!isProgrammaticChange && brgy && brgy !== 'other' && selectedCity.value) {
        geocodeQuery.value = `Barangay ${brgy}, ${selectedCity.value}, Cavite, Philippines`;
    }
});

// Sync structured fields to form.address
watch([() => form.address_line, selectedCity, selectedBarangay, manualBarangay], () => {
    const b = selectedBarangay.value === 'other' ? manualBarangay.value : selectedBarangay.value;
    const parts = [];
    if (form.address_line) parts.push(form.address_line);
    if (b) parts.push(`Brgy. ${b}`);
    if (selectedCity.value) parts.push(selectedCity.value);
    parts.push('Cavite');
    
    form.address = parts.join(', ');
    form.city = selectedCity.value;
    form.barangay = b;
});

const availableBarangays = computed(() => {
    if (!selectedCity.value || !props.barangays) return [];
    return props.barangays[selectedCity.value] || [];
});

// Map pin → form
const onMapAddressPicked = ({ city, barangay }) => {
    if (!city && !barangay) return;
    isProgrammaticChange = true;

    const cityKeys = Object.keys(props.cities || {});
    const matchedCity = fuzzyMatch(city, cityKeys);
    let matchedBrgy = null;

    if (matchedCity) {
        selectedCity.value = matchedCity;
        _applyCityChange(matchedCity);

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

    const cityLabel = matchedCity || city || '';
    const brgyLabel = matchedBrgy || (barangay ? `${barangay} (unmatched)` : '');
    if (cityLabel) {
        detectedLocation.value = `Detected: ${cityLabel}${brgyLabel ? ' · Brgy. ' + brgyLabel : ''}`;
        clearTimeout(detectedTimer);
        detectedTimer = setTimeout(() => { detectedLocation.value = ''; }, 5000);
    }
    isProgrammaticChange = false;
};

const sanitizeContactNumber = () => {
    form.contact_number = String(form.contact_number || '').replace(/\D/g, '').slice(0, 11);
};

const submit = () => {
    form.post(`/owner/distributor/${props.distributor.id}/branches`, {
        onSuccess: () => {
            localStorage.removeItem(STORAGE_KEY);
        }
    });
};
