<?php
$file = __DIR__ . '/resources/js/Pages/Courier/Dashboard.vue';

$content = <<<'VUE'
<template>
    <CourierLayout>

        <!-- ============================================================ -->
        <!-- PICKUP FLOW (scheduled → in_transit) -->
        <!-- ============================================================ -->
        <div v-if="activeFlow === 'pickup'">
            <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                <button @click="closeFlow" class="flex items-center gap-2 text-gray-600 font-semibold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    Cancel
                </button>
                <div class="flex items-center gap-2">
                    <div class="flex gap-1.5">
                        <div class="w-2 h-2 rounded-full" :class="flow.step===1?'bg-blue-600':'bg-gray-300'"></div>
                        <div class="w-2 h-2 rounded-full" :class="flow.step===2?'bg-purple-600':'bg-gray-300'"></div>
                        <div class="w-2 h-2 rounded-full" :class="flow.step===3?'bg-green-600':'bg-gray-300'"></div>
                    </div>
                    <span class="text-xs text-gray-500 font-medium">{{ flow.step }} / 3</span>
                </div>
            </div>

            <!-- Pickup Step 1: Head to pickup -->
            <div v-if="flow.step === 1" class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-blue-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900">Head to Pickup Location</h2>
                        <p class="text-xs text-gray-500">Navigate to the seller and collect the package</p>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tracking Number</p>
                    <p class="font-mono text-sm font-bold text-gray-900">{{ flow.delivery?.tracking_number }}</p>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">Pickup From</p>
                    <p class="font-semibold text-amber-900">{{ flow.delivery?.order?.distributor?.company_name }}</p>
                    <p class="text-sm text-amber-700 mt-0.5">{{ flow.delivery?.seller_address || flow.delivery?.order?.distributor?.address || 'Address not available' }}</p>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                    <p class="text-xs font-bold text-green-700 uppercase tracking-wider mb-1">Deliver To</p>
                    <p class="font-semibold text-green-900">{{ flow.delivery?.order?.customer?.name }}</p>
                    <p class="text-sm text-green-700 mt-0.5">{{ flow.delivery?.delivery_address }}</p>
                </div>
                <p v-if="flow.error" class="text-red-600 text-sm font-medium bg-red-50 border border-red-200 rounded-xl p-3">{{ flow.error }}</p>
                <button @click="doStartPickup" :disabled="flow.loading"
                    class="w-full bg-blue-600 text-white font-bold py-4 rounded-xl text-base disabled:opacity-50 flex items-center justify-center gap-2">
                    <svg v-if="flow.loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    I'm heading to pickup
                </button>
            </div>

            <!-- Pickup Step 2: Scan at seller -->
            <div v-else-if="flow.step === 2" class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-purple-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900">Scan Package at Seller</h2>
                        <p class="text-xs text-gray-500">Verify you have the correct package before leaving</p>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <p class="text-xs font-bold text-blue-700 uppercase mb-1">Expected Order Number</p>
                    <p class="font-mono text-base font-bold text-blue-900">{{ flow.delivery?.order?.order_number }}</p>
                </div>
                <ScannerView :scanning="flow.scanning" :scannedCode="flow.scannedCode" :cameraError="flow.cameraError"
                    :cameras="flow.cameras" :selectedCamera="flow.selectedCamera"
                    :videoEl="videoEl"
                    @switchCamera="switchCamera" />
                <p v-if="flow.error" class="text-red-600 text-sm font-medium bg-red-50 border border-red-200 rounded-xl p-3">{{ flow.error }}</p>
                <div class="flex gap-3">
                    <button @click="flow.step = 1; stopCamera()" class="px-5 py-4 border-2 border-gray-200 text-gray-600 font-semibold rounded-xl text-sm">Back</button>
                    <button @click="doPickupScan" :disabled="!flow.scannedCode || flow.loading"
                        class="flex-1 bg-purple-600 text-white font-bold py-4 rounded-xl disabled:opacity-40 flex items-center justify-center gap-2">
                        <svg v-if="flow.loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Next — Confirm Pickup
                    </button>
                </div>
            </div>

            <!-- Pickup Step 3: Confirm pickup ready -->
            <div v-else-if="flow.step === 3" class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-green-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900">Confirm Pickup</h2>
                        <p class="text-xs text-gray-500">Package verified — confirm to mark as In Transit</p>
                    </div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm font-bold text-green-800">Package Scanned and Verified</p>
                    </div>
                    <p class="font-mono text-xs text-green-700">{{ flow.delivery?.order?.order_number }}</p>
                    <p class="text-xs text-green-600 mt-1">For: {{ flow.delivery?.order?.customer?.name }}</p>
                </div>
                <div v-if="flow.delivery?.order?.payment_method === 'cod'" class="bg-orange-50 border border-orange-200 rounded-xl p-4">
                    <p class="text-sm font-bold text-orange-800 mb-1">COD Order</p>
                    <p class="text-sm text-orange-700">Collect <strong>₱{{ Number(flow.delivery.order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</strong> upon delivery. Return cash to distributor after.</p>
                </div>
                <p v-if="flow.error" class="text-red-600 text-sm font-medium bg-red-50 border border-red-200 rounded-xl p-3">{{ flow.error }}</p>
                <div class="flex gap-3">
                    <button @click="flow.step = 2; startCamera()" class="px-5 py-4 border-2 border-gray-200 text-gray-600 font-semibold rounded-xl text-sm">Back</button>
                    <button @click="doConfirmPickup" :disabled="flow.loading"
                        class="flex-1 bg-green-600 text-white font-bold py-4 rounded-xl disabled:opacity-40 flex items-center justify-center gap-2">
                        <svg v-if="flow.loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Confirm Pickup — Start Transit
                    </button>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- DELIVERY FLOW (in_transit → delivered) -->
        <!-- ============================================================ -->
        <div v-else-if="activeFlow === 'delivery'">
            <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                <button @click="closeFlow" class="flex items-center gap-2 text-gray-600 font-semibold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    Cancel
                </button>
                <div class="flex items-center gap-2">
                    <div class="flex gap-1.5">
                        <div class="w-2 h-2 rounded-full" :class="flow.step===1?'bg-purple-600':'bg-gray-300'"></div>
                        <div class="w-2 h-2 rounded-full" :class="flow.step===2?'bg-blue-600':'bg-gray-300'"></div>
                        <div class="w-2 h-2 rounded-full" :class="flow.step===3?'bg-green-600':'bg-gray-300'"></div>
                    </div>
                    <span class="text-xs text-gray-500 font-medium">{{ flow.step }} / 3</span>
                </div>
            </div>

            <!-- Delivery Step 1: Scan at customer location -->
            <div v-if="flow.step === 1" class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-purple-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900">Scan Package at Delivery</h2>
                        <p class="text-xs text-gray-500">Verify you are delivering the correct item to the right customer</p>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <p class="text-xs font-bold text-blue-700 uppercase mb-1">Expected Order Number</p>
                    <p class="font-mono text-base font-bold text-blue-900">{{ flow.delivery?.order?.order_number }}</p>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                    <p class="text-xs font-bold text-green-700 uppercase mb-1">Delivering to</p>
                    <p class="font-semibold text-green-900">{{ flow.delivery?.order?.customer?.name }}</p>
                    <p class="text-sm text-green-700 mt-0.5">{{ flow.delivery?.delivery_address }}</p>
                </div>
                <ScannerView :scanning="flow.scanning" :scannedCode="flow.scannedCode" :cameraError="flow.cameraError"
                    :cameras="flow.cameras" :selectedCamera="flow.selectedCamera"
                    :videoEl="videoEl"
                    @switchCamera="switchCamera" />
                <p v-if="flow.error" class="text-red-600 text-sm font-medium bg-red-50 border border-red-200 rounded-xl p-3">{{ flow.error }}</p>
                <button @click="goToDeliveryStep2" :disabled="!flow.scannedCode"
                    class="w-full bg-purple-600 text-white font-bold py-4 rounded-xl disabled:opacity-40">
                    Next — Take Proof Photo
                </button>
            </div>

            <!-- Delivery Step 2: Take proof photo -->
            <div v-else-if="flow.step === 2" class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-blue-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900">Proof of Delivery Photo</h2>
                        <p class="text-xs text-gray-500">Take a photo of the handover as proof of delivery</p>
                    </div>
                </div>
                <!-- Photo preview -->
                <div v-if="flow.photoPreview" class="rounded-xl overflow-hidden border-2 border-green-300">
                    <img :src="flow.photoPreview" class="w-full max-h-64 object-cover" alt="Proof of delivery">
                </div>
                <div v-else class="bg-gray-100 rounded-xl border-2 border-dashed border-gray-300 flex flex-col items-center justify-center py-10">
                    <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <p class="text-gray-500 text-sm font-semibold">No photo taken yet</p>
                </div>
                <label class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white font-bold py-4 rounded-xl cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                    {{ flow.photoPreview ? 'Retake Photo' : 'Take Photo' }}
                    <input type="file" accept="image/*" capture="environment" class="hidden" @change="onPhotoSelected">
                </label>
                <p v-if="flow.error" class="text-red-600 text-sm font-medium bg-red-50 border border-red-200 rounded-xl p-3">{{ flow.error }}</p>
                <div class="flex gap-3">
                    <button @click="flow.step = 1; flow.photoPreview = ''; flow.photoFile = null; startCamera()" class="px-5 py-4 border-2 border-gray-200 text-gray-600 font-semibold rounded-xl text-sm">Back</button>
                    <button @click="flow.step = 3" :disabled="!flow.photoPreview"
                        class="flex-1 bg-gray-800 text-white font-bold py-4 rounded-xl disabled:opacity-40">
                        Next — Confirm Delivery
                    </button>
                </div>
            </div>

            <!-- Delivery Step 3: Final confirm -->
            <div v-else-if="flow.step === 3" class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-green-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900">Confirm Delivery</h2>
                        <p class="text-xs text-gray-500">Hand over the package and confirm</p>
                    </div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                    <p class="text-xs font-bold text-green-700 uppercase mb-1">Delivering to</p>
                    <p class="font-semibold text-green-900">{{ flow.delivery?.order?.customer?.name }}</p>
                </div>
                <div v-if="flow.photoPreview" class="rounded-xl overflow-hidden border-2 border-green-200">
                    <img :src="flow.photoPreview" class="w-full max-h-40 object-cover" alt="Proof">
                    <p class="text-center text-xs text-green-700 bg-green-50 py-1.5 font-semibold">Proof photo ready</p>
                </div>
                <div v-if="flow.delivery?.order?.payment_method === 'cod'" class="bg-orange-50 border border-orange-200 rounded-xl p-4">
                    <p class="text-sm font-bold text-orange-800 mb-1">COD — Collect Cash</p>
                    <p class="text-sm text-orange-700">Collect <strong>₱{{ Number(flow.delivery.order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</strong> from customer now. Return cash to distributor later.</p>
                </div>
                <p v-if="flow.error" class="text-red-600 text-sm font-medium bg-red-50 border border-red-200 rounded-xl p-3">{{ flow.error }}</p>
                <div class="flex gap-3">
                    <button @click="flow.step = 2" class="px-5 py-4 border-2 border-gray-200 text-gray-600 font-semibold rounded-xl text-sm">Back</button>
                    <button @click="doConfirmDelivery" :disabled="flow.loading"
                        class="flex-1 bg-green-600 text-white font-bold py-4 rounded-xl disabled:opacity-40 flex items-center justify-center gap-2">
                        <svg v-if="flow.loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Confirm Delivery — Package Handed Over
                    </button>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- NORMAL DASHBOARD -->
        <!-- ============================================================ -->
        <div v-else>
            <div class="px-5 py-4 bg-gradient-to-r from-blue-700 to-blue-600 sticky top-0 z-10">
                <div class="flex justify-between items-center mb-3">
                    <h1 class="text-lg font-bold text-white tracking-tight">Courier Dashboard</h1>
                    <div class="flex items-center gap-2 bg-white/20 rounded-full px-3 py-1">
                        <span class="text-xs font-bold text-white">₱{{ earnings.wallet_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
                        <span class="text-blue-200 text-xs">Wallet</span>
                    </div>
                </div>
                <div class="flex space-x-1 bg-blue-800/40 p-1 rounded-xl">
                    <button @click="tab = 'active'"
                        class="flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold rounded-lg transition"
                        :class="tab === 'active' ? 'bg-white text-blue-700 shadow' : 'text-blue-100 hover:bg-white/10'">
                        <span>Active</span>
                        <span v-if="myDeliveries.length > 0" class="bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-black">{{ myDeliveries.length }}</span>
                    </button>
                    <button @click="tab = 'pool'"
                        class="flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold rounded-lg transition"
                        :class="tab === 'pool' ? 'bg-white text-blue-700 shadow' : 'text-blue-100 hover:bg-white/10'">
                        <span>Pool</span>
                        <span v-if="availableDeliveries.length > 0" class="bg-green-400 text-green-900 text-[10px] px-1.5 py-0.5 rounded-full font-black">{{ availableDeliveries.length }}</span>
                    </button>
                    <button @click="tab = 'history'"
                        class="flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold rounded-lg transition"
                        :class="tab === 'history' ? 'bg-white text-blue-700 shadow' : 'text-blue-100 hover:bg-white/10'">
                        History
                    </button>
                </div>
            </div>

            <div class="p-4 bg-gray-50">

                <!-- ACTIVE TAB -->
                <div v-if="tab === 'active'">
                    <div v-if="myDeliveries.length > 0" class="space-y-4">
                        <div v-for="delivery in myDeliveries" :key="delivery.id"
                            class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="p-4 border-b border-gray-100 flex justify-between items-center"
                                :class="{
                                    'bg-blue-50': delivery.status === 'in_transit',
                                    'bg-amber-50': delivery.status === 'scheduled',
                                    'bg-yellow-50': delivery.status === 'picking_up',
                                }">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tracking</span>
                                    <p class="font-mono text-sm font-bold text-gray-900">{{ delivery.tracking_number }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="delivery.order?.payment_method === 'cod'"
                                        class="bg-orange-100 text-orange-700 text-[10px] font-black px-2 py-1 rounded-full uppercase border border-orange-200">COD</span>
                                    <span class="px-2.5 py-1 text-xs font-black rounded-lg uppercase"
                                        :class="{
                                            'bg-blue-100 text-blue-800': delivery.status === 'in_transit',
                                            'bg-amber-100 text-amber-800': delivery.status === 'scheduled',
                                            'bg-yellow-100 text-yellow-800': delivery.status === 'picking_up',
                                        }">{{ delivery.status.replace('_', ' ') }}</span>
                                </div>
                            </div>
                            <div class="p-4 space-y-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase">Pickup</p>
                                        <p class="text-sm font-semibold text-gray-900">{{ delivery.order?.distributor?.company_name }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 pl-1 border-l-2 border-dashed border-gray-200 ml-3">
                                    <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 -ml-4">
                                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase">Dropoff</p>
                                        <p class="text-sm font-semibold text-gray-900">{{ delivery.order?.customer?.name }}</p>
                                        <p class="text-xs text-gray-500">{{ delivery.delivery_address }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-gray-50 flex gap-2">
                                <button v-if="delivery.status === 'scheduled'"
                                    @click="openPickupFlow(delivery)"
                                    class="flex-1 bg-blue-600 text-white font-bold py-2.5 rounded-xl text-sm">
                                    Start Delivery
                                </button>
                                <button v-if="delivery.status === 'picking_up'"
                                    @click="openPickupFlow(delivery, 2)"
                                    class="flex-1 bg-yellow-500 text-white font-bold py-2.5 rounded-xl text-sm">
                                    Continue — Scan Package
                                </button>
                                <button v-if="delivery.status === 'in_transit'"
                                    @click="openDeliveryFlow(delivery)"
                                    class="flex-1 bg-green-600 text-white font-bold py-2.5 rounded-xl text-sm">
                                    Deliver Package
                                </button>
                                <button v-if="delivery.status === 'in_transit'"
                                    @click="markFailed(delivery.id)"
                                    class="flex-none bg-red-100 text-red-600 font-bold py-2.5 px-4 rounded-xl text-sm">
                                    Failed
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-16">
                        <p class="text-gray-500 font-semibold">No active deliveries</p>
                        <p class="text-xs text-gray-400 mt-1">Check the pool to pick up jobs.</p>
                    </div>
                </div>

                <!-- POOL TAB -->
                <div v-else-if="tab === 'pool'">
                    <div v-if="availableDeliveries.length > 0" class="space-y-4">
                        <div v-for="job in availableDeliveries" :key="job.id"
                            class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-2">
                                    <span v-if="job.order?.payment_method === 'cod'"
                                        class="bg-orange-100 text-orange-700 text-[10px] font-black px-2 py-1 rounded-full border border-orange-200">COD</span>
                                    <span class="bg-blue-100 text-blue-700 text-[10px] font-black px-2 py-1 rounded-full">{{ job.order?.items?.length || 0 }} item(s)</span>
                                </div>
                                <div v-if="job.courier_fee" class="text-right">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase">Earn</p>
                                    <p class="text-sm font-black text-green-600">+₱{{ Number(job.courier_fee).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                                </div>
                            </div>
                            <p class="text-base font-bold text-gray-900 mb-1">{{ job.order?.distributor?.company_name }}</p>
                            <p class="text-sm text-gray-500 mb-1">{{ job.order?.customer?.name }} — {{ job.delivery_address }}</p>
                            <div v-if="job.order?.payment_method === 'cod'" class="mb-3 text-xs text-orange-700 bg-orange-50 rounded-lg px-3 py-2 border border-orange-100">
                                Collect <strong>₱{{ Number(job.order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</strong> cash from customer
                            </div>
                            <button @click="acceptDelivery(job.id)"
                                class="w-full bg-gray-900 text-white font-bold py-3 rounded-xl text-sm">
                                Accept Job
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-center py-16">
                        <p class="text-gray-500 font-semibold">No jobs available right now</p>
                    </div>
                </div>

                <!-- HISTORY TAB -->
                <div v-else-if="tab === 'history'">
                    <div class="grid grid-cols-3 gap-3 mb-5">
                        <div class="bg-white rounded-2xl p-4 text-center border border-gray-100 shadow-sm">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Balance</p>
                            <p class="text-lg font-black text-blue-600">₱{{ earnings.wallet_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                        </div>
                        <div class="bg-white rounded-2xl p-4 text-center border border-gray-100 shadow-sm">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">All Time</p>
                            <p class="text-lg font-black text-green-600">₱{{ earnings.total_earned.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                        </div>
                        <div class="bg-white rounded-2xl p-4 text-center border border-gray-100 shadow-sm">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Deliveries</p>
                            <p class="text-lg font-black text-gray-800">{{ earnings.total_deliveries }}</p>
                        </div>
                    </div>
                    <div v-if="history.data?.length > 0" class="space-y-3">
                        <div v-for="item in history.data" :key="item.id"
                            class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="p-4 flex justify-between items-start">
                                <div class="flex-1 min-w-0 pr-3">
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="text-xs font-bold text-gray-900 font-mono">{{ item.tracking_number }}</p>
                                        <span v-if="item.order?.payment_method === 'cod'"
                                            class="text-[9px] bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full font-bold border border-orange-200">COD</span>
                                    </div>
                                    <p class="text-xs text-gray-500 truncate">{{ item.order?.customer?.name }}</p>
                                    <p class="text-[10px] text-gray-400 mt-1">{{ item.order?.distributor?.company_name }}</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="inline-block text-[10px] font-black px-2 py-1 rounded-full uppercase"
                                        :class="item.status === 'delivered' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'">
                                        {{ item.status }}
                                    </span>
                                    <p class="text-[10px] text-gray-400 mt-1">{{ item.actual_delivery_at ? new Date(item.actual_delivery_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '' }}</p>
                                    <p v-if="item.courier_fee && item.status === 'delivered'" class="text-xs font-bold text-green-600 mt-1">+₱{{ Number(item.courier_fee).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                                </div>
                            </div>
                            <!-- COD Remittance -->
                            <div v-if="item.order?.payment_method === 'cod' && item.status === 'delivered'"
                                class="px-4 py-3 border-t"
                                :class="item.cod_remitted_at ? 'bg-green-50 border-green-100' : 'bg-orange-50 border-orange-100'">
                                <p class="text-[10px] font-bold mb-2"
                                    :class="item.cod_remitted_at ? 'text-green-700' : 'text-orange-700'">
                                    {{ item.cod_remitted_at ? 'Cash remitted to distributor' : 'Pending: Remit ₱' + Number(item.cod_amount || item.order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) + ' to distributor' }}
                                </p>
                                <!-- Remittance action button -->
                                <button v-if="!item.cod_remitted_at && !item.cod_remittance_sent_at"
                                    @click="markRemittanceSent(item.id)"
                                    class="w-full bg-orange-600 text-white font-bold py-2.5 rounded-xl text-xs">
                                    I have given cash to the distributor
                                </button>
                                <p v-else-if="item.cod_remittance_sent_at && !item.cod_remitted_at"
                                    class="text-[10px] text-orange-600 font-semibold">
                                    Waiting for distributor to confirm receipt...
                                </p>
                            </div>
                        </div>
                        <div v-if="history.last_page > 1" class="flex justify-center gap-2 py-4">
                            <button v-if="history.prev_page_url" @click="goToPage(history.current_page - 1)"
                                class="px-4 py-2 text-sm font-bold bg-white border border-gray-200 rounded-xl">Prev</button>
                            <span class="px-4 py-2 text-sm text-gray-500">{{ history.current_page }} / {{ history.last_page }}</span>
                            <button v-if="history.next_page_url" @click="goToPage(history.current_page + 1)"
                                class="px-4 py-2 text-sm font-bold bg-white border border-gray-200 rounded-xl">Next</button>
                        </div>
                    </div>
                    <div v-else class="text-center py-16">
                        <p class="text-gray-500 font-semibold">No delivery history yet</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Inline Camera view (rendered in DOM but visibility driven by flow state) -->
        <video ref="videoEl" class="hidden" autoplay playsinline muted></video>

    </CourierLayout>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import CourierLayout from '@/Layouts/CourierLayout.vue';
import { BrowserMultiFormatReader } from '@zxing/browser';

// Inline scanner component
const ScannerView = {
    props: ['scanning', 'scannedCode', 'cameraError', 'cameras', 'selectedCamera', 'videoEl'],
    emits: ['switchCamera'],
    template: `
        <div class="bg-gray-900 rounded-2xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-700">
                <p class="text-white text-sm font-semibold">Camera Scanner</p>
                <p class="text-gray-400 text-xs">Point camera at the barcode or QR code on the package</p>
            </div>
            <div class="relative aspect-square bg-black">
                <slot name="video"></slot>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-48 h-48 relative">
                        <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-blue-400 rounded-tl-lg"></div>
                        <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-blue-400 rounded-tr-lg"></div>
                        <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-blue-400 rounded-bl-lg"></div>
                        <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-blue-400 rounded-br-lg"></div>
                        <div v-if="scanning" class="absolute top-0 left-0 right-0 h-0.5 bg-blue-400 opacity-75 animate-bounce"></div>
                    </div>
                </div>
                <div v-if="cameraError" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900 text-center p-6">
                    <p class="text-white font-semibold mb-1">Camera unavailable</p>
                    <p class="text-gray-400 text-sm">{{ cameraError }}</p>
                </div>
            </div>
            <div class="px-4 py-3">
                <div v-if="scannedCode" class="flex items-center gap-3 bg-green-900/50 border border-green-700 text-green-300 rounded-lg px-4 py-3 mb-2">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <div>
                        <p class="text-xs text-green-400 font-semibold uppercase">Scanned</p>
                        <p class="font-mono font-bold text-sm">{{ scannedCode }}</p>
                    </div>
                </div>
                <div v-else-if="scanning" class="flex items-center gap-2 text-gray-400 text-sm py-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Scanning...
                </div>
                <select v-if="cameras.length > 1" :value="selectedCamera" @change="$emit('switchCamera', $event.target.value)"
                    class="w-full bg-gray-800 text-gray-200 text-sm rounded-lg border border-gray-600 px-3 py-2">
                    <option v-for="cam in cameras" :key="cam.deviceId" :value="cam.deviceId">{{ cam.label || 'Camera ' + cam.deviceId.slice(0,6) }}</option>
                </select>
            </div>
        </div>
    `
};

const props = defineProps({
    availableDeliveries: Array,
    myDeliveries: Array,
    history: Object,
    courier: Object,
    earnings: Object,
});

const tab = ref('active');
const activeFlow = ref(null); // null | 'pickup' | 'delivery'
const videoEl = ref(null);
let codeReader = null;
let cameraControls = null;

const flow = ref({
    step: 1,
    delivery: null,
    loading: false,
    error: '',
    scanning: false,
    scannedCode: '',
    cameraError: '',
    cameras: [],
    selectedCamera: null,
    photoPreview: '',
    photoFile: null,
});

const resetFlow = () => ({
    step: 1, delivery: null, loading: false, error: '',
    scanning: false, scannedCode: '', cameraError: '', cameras: [], selectedCamera: null,
    photoPreview: '', photoFile: null,
});

const openPickupFlow = (delivery, step = 1) => {
    flow.value = { ...resetFlow(), delivery, step };
    activeFlow.value = 'pickup';
    if (step === 2) setTimeout(startCamera, 300);
};

const openDeliveryFlow = (delivery) => {
    flow.value = { ...resetFlow(), delivery, step: 1 };
    activeFlow.value = 'delivery';
    setTimeout(startCamera, 300);
};

const closeFlow = () => {
    stopCamera();
    activeFlow.value = null;
    flow.value = resetFlow();
};

// Camera
const startCamera = async () => {
    flow.value.scanning = false;
    flow.value.scannedCode = '';
    flow.value.cameraError = '';
    await new Promise(r => setTimeout(r, 200));
    try {
        codeReader = new BrowserMultiFormatReader();
        const devices = await BrowserMultiFormatReader.listVideoInputDevices();
        flow.value.cameras = devices;
        if (!devices.length) { flow.value.cameraError = 'No camera found.'; return; }
        const back = devices.find(d => /back|rear|environment/i.test(d.label));
        flow.value.selectedCamera = back?.deviceId ?? devices[0].deviceId;
        flow.value.scanning = true;
        cameraControls = await codeReader.decodeFromVideoDevice(
            flow.value.selectedCamera, videoEl.value,
            (result) => {
                if (result && !flow.value.scannedCode) {
                    flow.value.scannedCode = result.getText();
                    flow.value.scanning = false;
                }
            }
        );
    } catch (e) {
        flow.value.cameraError = e.message || 'Camera access denied.';
        flow.value.scanning = false;
    }
};

const stopCamera = () => {
    cameraControls?.stop();
    cameraControls = null;
    if (flow.value) flow.value.scanning = false;
};

const switchCamera = async (deviceId) => {
    flow.value.selectedCamera = deviceId;
    cameraControls?.stop();
    await startCamera();
};

// Photo selection
const onPhotoSelected = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    flow.value.photoFile = file;
    flow.value.photoPreview = URL.createObjectURL(file);
};

// ── Pickup flow actions ──
const doStartPickup = () => {
    flow.value.loading = true;
    flow.value.error = '';
    router.post('/courier/deliveries/' + flow.value.delivery.id + '/start-pickup', {}, {
        preserveScroll: true,
        onSuccess: () => { flow.value.step = 2; flow.value.loading = false; setTimeout(startCamera, 300); },
        onError: (errors) => { flow.value.error = Object.values(errors)[0] || 'Something went wrong.'; flow.value.loading = false; },
    });
};

const doPickupScan = () => {
    flow.value.loading = true;
    flow.value.error = '';
    stopCamera();
    router.post('/courier/deliveries/' + flow.value.delivery.id + '/confirm-scan',
        { order_number: flow.value.scannedCode },
        {
            preserveScroll: true,
            onSuccess: () => { flow.value.step = 3; flow.value.loading = false; },
            onError: (errors) => {
                flow.value.error = Object.values(errors)[0] || 'Code mismatch. Re-scan the package.';
                flow.value.loading = false;
                setTimeout(startCamera, 300);
            },
        }
    );
};

const doConfirmPickup = () => {
    flow.value.loading = true;
    flow.value.error = '';
    router.post('/courier/deliveries/' + flow.value.delivery.id + '/confirm-pickup', {}, {
        preserveScroll: true,
        onSuccess: () => { closeFlow(); tab.value = 'active'; },
        onError: (errors) => { flow.value.error = Object.values(errors)[0] || 'Something went wrong.'; flow.value.loading = false; },
    });
};

// ── Delivery flow actions ──
const goToDeliveryStep2 = () => {
    stopCamera();
    flow.value.step = 2;
};

const doConfirmDelivery = () => {
    if (!flow.value.photoFile) { flow.value.error = 'Please take a proof of delivery photo.'; return; }
    flow.value.loading = true;
    flow.value.error = '';
    const formData = new FormData();
    formData.append('order_number', flow.value.scannedCode);
    formData.append('proof_photo', flow.value.photoFile);
    formData.append('_method', 'POST');

    router.post('/courier/deliveries/' + flow.value.delivery.id + '/confirm-delivery', formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { closeFlow(); tab.value = 'history'; },
        onError: (errors) => { flow.value.error = Object.values(errors)[0] || 'Something went wrong.'; flow.value.loading = false; },
    });
};

// ── Other actions ──
const acceptDelivery = (id) => {
    router.post('/courier/deliveries/' + id + '/accept', {}, {
        preserveScroll: true, onSuccess: () => { tab.value = 'active'; }
    });
};

const markFailed = (id) => {
    if (confirm('Mark this delivery as failed?')) {
        router.post('/courier/deliveries/' + id + '/status', { status: 'failed' }, { preserveScroll: true });
    }
};

const markRemittanceSent = (id) => {
    if (confirm('Confirm that you have physically handed the cash to the distributor?')) {
        router.post('/courier/deliveries/' + id + '/remittance-sent', {}, { preserveScroll: true });
    }
};

const goToPage = (page) => {
    router.get('/courier/dashboard', { page }, { preserveScroll: true, preserveState: true });
};

onBeforeUnmount(stopCamera);
</script>
VUE;

file_put_contents($file, $content);
echo "Done. Lines: " . count(file($file)) . PHP_EOL;
