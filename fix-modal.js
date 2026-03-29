const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'resources/js/Pages/Courier/Dashboard.vue');
const content = fs.readFileSync(file, 'utf8');

// Find start: first line after <CourierLayout>
const startMarker = '<CourierLayout>';
const startIdx = content.indexOf(startMarker) + startMarker.length;

// Find end: the <!-- Header with tab switcher --> comment (or the px-5 py-4 header div)
// We'll find the first occurrence of the header div that's NOT inside the modal
const endMarker = '<!-- Header with tab switcher -->';
let endIdx = content.indexOf(endMarker);
if (endIdx === -1) {
    // fallback: find the sticky header div
    endIdx = content.indexOf("class=\"px-5 py-4 bg-gradient-to-r from-blue-700 to-blue-600 sticky top-0 z-10\"");
    // go back to find the opening <!-- comment or <div
    endIdx = content.lastIndexOf('\n', endIdx) + 1;
}

const before = content.substring(0, startIdx);
const after  = content.substring(endIdx);

const newModal = `
        <!-- Pickup Flow: Full-screen takeover — no scroll, button pinned at bottom -->
        <div v-if="pickupModal.show" class="fixed inset-0 z-50 bg-white flex flex-col">

            <!-- Top bar -->
            <div class="flex-shrink-0 flex items-center justify-between px-5 pt-4 pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="flex gap-1.5">
                        <div class="w-2 h-2 rounded-full transition-colors" :class="pickupModal.step === 'navigate' ? 'bg-blue-600' : 'bg-gray-200'"></div>
                        <div class="w-2 h-2 rounded-full transition-colors" :class="pickupModal.step === 'scan'     ? 'bg-purple-600' : 'bg-gray-200'"></div>
                        <div class="w-2 h-2 rounded-full transition-colors" :class="pickupModal.step === 'confirm'  ? 'bg-green-600' : 'bg-gray-200'"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-500">
                        {{ pickupModal.step === 'navigate' ? 'Step 1 of 3' : pickupModal.step === 'scan' ? 'Step 2 of 3' : 'Step 3 of 3' }}
                    </span>
                </div>
                <button @click="closePickupModal" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- STEP 1: Go to Pickup -->
            <template v-if="pickupModal.step === 'navigate'">
                <div class="flex-1 px-5 pt-6 pb-6 flex flex-col">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-11 h-11 bg-blue-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <h2 class="font-bold text-gray-900 text-base">Go to Pickup Location</h2>
                            <p class="text-xs text-gray-500">Navigate to seller and collect the package</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Package</p>
                            <p class="font-mono text-sm font-bold text-gray-900">{{ pickupModal.delivery?.tracking_number }}</p>
                        </div>
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
                            <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">📦 Pickup From</p>
                            <p class="font-semibold text-amber-900">{{ pickupModal.delivery?.order?.distributor?.company_name }}</p>
                            <p class="text-sm text-amber-700 mt-0.5">{{ pickupModal.delivery?.seller_address || pickupModal.delivery?.order?.distributor?.address || 'Address not available' }}</p>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-2xl p-4">
                            <p class="text-xs font-bold text-green-700 uppercase tracking-wider mb-1">🏠 Deliver To</p>
                            <p class="font-semibold text-green-900">{{ pickupModal.delivery?.order?.customer?.name }}</p>
                            <p class="text-sm text-green-700 mt-0.5">{{ pickupModal.delivery?.delivery_address }}</p>
                        </div>
                    </div>
                    <div class="mt-auto pt-6">
                        <button @click="doStartPickup" :disabled="pickupModal.loading"
                            class="w-full bg-blue-600 text-white font-bold py-4 rounded-2xl text-base disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="pickupModal.loading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            I'm heading to pickup →
                        </button>
                    </div>
                </div>
            </template>

            <!-- STEP 2: Scan & Verify -->
            <template v-else-if="pickupModal.step === 'scan'">
                <div class="flex-1 px-5 pt-6 pb-6 flex flex-col">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-11 h-11 bg-purple-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        </div>
                        <div>
                            <h2 class="font-bold text-gray-900 text-base">Scan &amp; Verify Item</h2>
                            <p class="text-xs text-gray-500">Confirm you have the correct package</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4">
                            <p class="text-xs font-bold text-blue-700 uppercase mb-1">Expected Package</p>
                            <p class="font-mono text-base font-bold text-blue-900">{{ pickupModal.delivery?.order?.order_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Scan or type order number</label>
                            <input v-model="pickupModal.scannedOrderNumber"
                                type="text"
                                placeholder="e.g. ORD-2026-00123"
                                class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-2xl text-sm focus:outline-none focus:border-purple-500 font-mono">
                            <p v-if="pickupModal.scanError" class="text-red-600 text-xs mt-1.5 font-medium">{{ pickupModal.scanError }}</p>
                        </div>
                        <a href="/courier/scanner?action=pickup" class="flex items-center justify-center gap-2 text-sm text-purple-600 font-semibold py-3 border-2 border-purple-200 rounded-2xl bg-purple-50">
                            Open QR Scanner instead
                        </a>
                    </div>
                    <div class="mt-auto pt-6 flex gap-3">
                        <button @click="pickupModal.step = 'navigate'" class="px-5 py-4 border-2 border-gray-200 text-gray-600 font-semibold rounded-2xl text-sm">
                            ← Back
                        </button>
                        <button @click="doConfirmScan" :disabled="pickupModal.loading || !pickupModal.scannedOrderNumber"
                            class="flex-1 bg-purple-600 text-white font-bold py-4 rounded-2xl disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="pickupModal.loading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Verify Item →
                        </button>
                    </div>
                </div>
            </template>

            <!-- STEP 3: Confirm Pickup -->
            <template v-else-if="pickupModal.step === 'confirm'">
                <div class="flex-1 px-5 pt-6 pb-6 flex flex-col">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-11 h-11 bg-green-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <h2 class="font-bold text-gray-900 text-base">Confirm Pickup</h2>
                            <p class="text-xs text-gray-500">Ready to mark as In Transit</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="bg-green-50 border border-green-200 rounded-2xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="h-5 w-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-sm font-bold text-green-800">Package Verified ✓</p>
                            </div>
                            <p class="font-mono text-xs text-green-700">{{ pickupModal.delivery?.order?.order_number }}</p>
                            <p class="text-xs text-green-600 mt-1">For: {{ pickupModal.delivery?.order?.customer?.name }}</p>
                        </div>
                        <div v-if="pickupModal.delivery?.order?.payment_method === 'cod'" class="bg-orange-50 border border-orange-200 rounded-2xl p-4">
                            <p class="text-sm font-bold text-orange-800 mb-1">⚠️ COD Order</p>
                            <p class="text-sm text-orange-700">Collect <strong>₱{{ Number(pickupModal.delivery.order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</strong> upon delivery.</p>
                        </div>
                        <p class="text-center text-xs text-gray-400 pt-1">Tapping confirm changes order to <strong>In Transit</strong></p>
                    </div>
                    <div class="mt-auto pt-6 flex gap-3">
                        <button @click="pickupModal.step = 'scan'" class="px-5 py-4 border-2 border-gray-200 text-gray-600 font-semibold rounded-2xl text-sm">
                            ← Back
                        </button>
                        <button @click="doConfirmPickup" :disabled="pickupModal.loading"
                            class="flex-1 bg-green-600 text-white font-bold py-4 rounded-2xl disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="pickupModal.loading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            ✓ Picked Up — Start Transit
                        </button>
                    </div>
                </div>
            </template>

        </div>

        `;

const result = before + newModal + after;
fs.writeFileSync(file, result, 'utf8');
console.log('Done. File updated successfully.');
