<template>
    <CourierLayout>

        <!-- ============================================================ -->
        <!-- PICKUP FLOW (scheduled → in_transit) -->
        <!-- ============================================================ -->
        <div v-if="activeFlow === 'pickup'">
            <div class="bg-white border-b border-line px-4 py-3 flex items-center justify-between">
                <button @click="closeFlow" class="flex items-center gap-2 text-ink-soft font-semibold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    Cancel
                </button>
                <div class="flex items-center gap-2">
                    <div class="flex gap-1.5">
                        <div class="w-2 h-2 rounded-full" :class="flow.step===1?'bg-brand':'bg-line'"></div>
                        <div class="w-2 h-2 rounded-full" :class="flow.step===2?'bg-brand':'bg-line'"></div>
                        <div class="w-2 h-2 rounded-full" :class="flow.step===3?'bg-brand':'bg-line'"></div>
                    </div>
                    <span class="text-xs text-ink-soft font-medium">{{ flow.step }} / 3</span>
                </div>
            </div>

            <!-- Pickup Step 1: Head to pickup -->
            <div v-if="flow.step === 1" class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-brand-tint rounded-card flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-ink">Head to Pickup Location</h2>
                        <p class="text-xs text-ink-soft">Navigate to the seller and collect the package</p>
                    </div>
                </div>
                <div class="bg-mist rounded-card p-4 border border-line">
                    <p class="text-xs font-bold text-ink-faint   mb-1">Tracking Number</p>
                    <p class="font-mono text-sm font-bold text-ink">{{ flow.delivery?.tracking_number }}</p>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-card p-4">
                    <div v-if="flow.delivery?.order?.is_fragile" class="mb-3 flex items-center gap-1.5 px-2 py-1 rounded-control bg-rose-100 border border-rose-300 text-rose-800 text-xs font-semibold  ">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Fragile: Handle with care
                    </div>
                    <p class="text-xs font-bold text-amber-700   mb-1">Pickup From</p>
                    <p class="font-semibold text-amber-900">{{ flow.delivery?.order?.distributor?.company_name }}</p>
                    <p class="text-sm text-amber-700 mt-0.5">{{ flow.delivery?.seller_address || flow.delivery?.order?.distributor?.address || 'Address not available' }}</p>
                    <div v-if="flow.delivery?.order?.distributor?.user?.phone_number" class="mt-2 flex items-center gap-2">
                        <a :href="`tel:${flow.delivery.order.distributor.user.phone_number}`" class="bg-amber-100 text-amber-800 text-xs sm:text-xs px-3 py-1.5 rounded-control border border-amber-300 font-bold flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            Call Shop
                        </a>
                        <span class="text-xs font-mono font-bold text-amber-900">{{ flow.delivery.order.distributor.user.phone_number }}</span>
                    </div>
                </div>
                <div class="bg-brand-tint border border-brand-soft rounded-card p-4">
                    <p class="text-xs font-bold text-brand-dark   mb-1">Deliver To</p>
                    <p class="font-semibold text-brand-dark">{{ flow.delivery?.order?.customer?.name }}</p>
                    <p class="text-sm text-brand-dark mt-0.5">{{ flow.delivery?.delivery_address }}</p>
                    <div v-if="flow.delivery?.order?.contact_number" class="mt-2 flex items-center gap-2">
                        <a :href="`tel:${flow.delivery.order.contact_number}`" class="bg-brand-tint text-brand-dark text-xs sm:text-xs px-3 py-1.5 rounded-control border border-brand-soft font-bold flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            Call Customer
                        </a>
                        <span class="text-xs font-mono font-bold text-brand-dark">{{ flow.delivery.order.contact_number }}</span>
                    </div>
                </div>
                <p v-if="flow.error" class="text-red-600 text-sm font-medium bg-red-50 border border-red-200 rounded-card p-3">{{ flow.error }}</p>
                <button @click="doStartPickup" :disabled="flow.loading"
                    class="w-full bg-brand text-white font-bold py-4 rounded-card text-base disabled:opacity-50 flex items-center justify-center gap-2">
                    <svg v-if="flow.loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    I'm heading to pickup
                </button>
            </div>

            <!-- Pickup Step 2: Scan at seller -->
            <div v-else-if="flow.step === 2" class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-brand-tint rounded-card flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-ink">Scan Package at Seller</h2>
                        <p class="text-xs text-ink-soft">Verify you have the correct package before leaving</p>
                    </div>
                </div>
                <div class="bg-brand-tint border border-brand-soft rounded-card p-4">
                    <p class="text-xs font-bold text-brand-dark  mb-1">Expected Order Number</p>
                    <p class="font-mono text-base font-bold text-brand-dark">{{ flow.delivery?.order?.order_number }}</p>
                </div>
                <!-- Camera box -->
                <div class="bg-ink rounded-card overflow-hidden">
                    <div class="px-4 py-3 border-b border-ink-faint flex items-center justify-between">
                        <div>
                            <p class="text-white text-sm font-semibold">Camera Scanner</p>
                            <p class="text-ink-faint text-xs">Point at the barcode or QR code on the package</p>
                        </div>
                        <button v-if="flow.cameraActive" @click="stopCamera"
                            class="text-xs text-ink-faint hover:text-white border border-ink-faint px-2 py-1 rounded">
                            Stop
                        </button>
                    </div>
                    <div class="relative bg-black" style="min-height:240px">
                        <video ref="videoEl" autoplay playsinline muted
                            class="w-full" style="display:block;min-height:240px;object-fit:cover"
                            v-show="flow.cameraActive"></video>
                        <!-- Open camera button (shown when camera not active) -->
                        <div v-if="!flow.cameraActive && !flow.scannedCode && !flow.cameraError"
                            class="absolute inset-0 flex flex-col items-center justify-center bg-ink">
                            <button @click="startCamera"
                                class="bg-brand hover:bg-brand-dark active:scale-95 text-white font-bold px-8 py-4 rounded-card text-base shadow-lg transition-all">
                                📷 Open Camera to Scan
                            </button>
                            <p class="text-ink-soft text-xs mt-3">Tap to scan the package barcode</p>
                        </div>
                        <!-- Scanning reticle overlay -->
                        <div v-if="flow.cameraActive" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-52 h-52 relative">
                                <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-brand rounded-tl-control"></div>
                                <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-brand rounded-tr-control"></div>
                                <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-brand rounded-bl-control"></div>
                                <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-brand rounded-br-control"></div>
                            </div>
                        </div>
                        <!-- Error overlay -->
                        <div v-if="flow.cameraError" class="absolute inset-0 flex flex-col items-center justify-center bg-ink/95 text-center p-6">
                            <p class="text-white font-semibold mb-1">Camera unavailable</p>
                            <p class="text-ink-faint text-sm mb-3">{{ flow.cameraError }}</p>
                            <button @click="startCamera" class="bg-ink-soft hover:bg-ink-soft text-white text-sm font-semibold px-4 py-2 rounded-control">Retry</button>
                        </div>
                    </div>
                    <!-- Scanned result -->
                    <div class="px-4 py-3">
                        <div v-if="flow.scannedCode" class="flex items-center gap-3 bg-brand-dark/50 border border-brand rounded-control px-4 py-3">
                            <svg class="h-5 w-5 text-brand shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <div>
                                <p class="text-xs text-brand font-semibold ">Scanned</p>
                                <p class="font-mono font-bold text-sm text-brand-soft">{{ flow.scannedCode }}</p>
                            </div>
                            <button @click="flow.scannedCode = ''; startCamera()" class="ml-auto text-xs text-ink-faint hover:text-white">Rescan</button>
                        </div>
                        <div v-else-if="flow.cameraActive" class="flex items-center gap-2 text-ink-faint text-sm py-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Scanning... point camera at the barcode
                        </div>
                    </div>
                </div>
                <p v-if="flow.error" class="text-red-600 text-sm font-medium bg-red-50 border border-red-200 rounded-card p-3">{{ flow.error }}</p>
                <div class="flex gap-3">
                    <button @click="flow.step = 1; stopCamera()" class="px-5 py-4 border-2 border-line text-ink-soft font-semibold rounded-card text-sm">Back</button>
                    <button @click="doPickupScan" :disabled="!flow.scannedCode || flow.loading"
                        class="flex-1 bg-brand text-white font-bold py-4 rounded-card disabled:opacity-40 flex items-center justify-center gap-2">
                        <svg v-if="flow.loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Next — Confirm Pickup
                    </button>
                </div>
            </div>

            <!-- Pickup Step 3: Confirm pickup ready -->
            <div v-else-if="flow.step === 3" class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-brand-tint rounded-card flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-ink">Confirm Pickup</h2>
                        <p class="text-xs text-ink-soft">Package verified — confirm to mark as In Transit</p>
                    </div>
                </div>
                <div class="bg-brand-tint border border-brand-soft rounded-card p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="h-4 w-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm font-bold text-brand-dark">Package Scanned and Verified</p>
                    </div>
                    <p class="font-mono text-xs text-brand-dark">{{ flow.delivery?.order?.order_number }}</p>
                    <p class="text-xs text-brand mt-1">For: {{ flow.delivery?.order?.customer?.name }}</p>
                </div>
                <div v-if="flow.delivery?.order?.payment_method === 'cod'" class="bg-orange-50 border border-orange-200 rounded-card p-4">
                    <p class="text-sm font-bold text-orange-800 mb-1">COD Order</p>
                    <p class="text-sm text-orange-700">Collect <strong>₱{{ Number(flow.delivery.order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</strong> upon delivery. Return cash to distributor after.</p>
                </div>
                <p v-if="flow.error" class="text-red-600 text-sm font-medium bg-red-50 border border-red-200 rounded-card p-3">{{ flow.error }}</p>
                <div class="flex gap-3">
                    <button @click="flow.step = 2; startCamera()" class="px-5 py-4 border-2 border-line text-ink-soft font-semibold rounded-card text-sm">Back</button>
                    <button @click="doConfirmPickup" :disabled="flow.loading"
                        class="flex-1 bg-brand text-white font-bold py-4 rounded-card disabled:opacity-40 flex items-center justify-center gap-2">
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
            <div class="bg-white border-b border-line px-4 py-3 flex items-center justify-between">
                <button @click="closeFlow" class="flex items-center gap-2 text-ink-soft font-semibold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    Cancel
                </button>
                <div class="flex items-center gap-2">
                    <div class="flex gap-1.5">
                        <div class="w-2 h-2 rounded-full" :class="flow.step===1?'bg-brand':'bg-line'"></div>
                        <div class="w-2 h-2 rounded-full" :class="flow.step===2?'bg-brand':'bg-line'"></div>
                        <div class="w-2 h-2 rounded-full" :class="flow.step===3?'bg-brand':'bg-line'"></div>
                    </div>
                    <span class="text-xs text-ink-soft font-medium">{{ flow.step }} / 3</span>
                </div>
            </div>

            <!-- Delivery Step 1: Scan at customer location -->
            <div v-if="flow.step === 1" class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-brand-tint rounded-card flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-ink">Scan Package at Delivery</h2>
                        <p class="text-xs text-ink-soft">Verify you are delivering the correct item to the right customer</p>
                    </div>
                </div>
                <div class="bg-brand-tint border border-brand-soft rounded-card p-4">
                    <p class="text-xs font-bold text-brand-dark  mb-1">Expected Order Number</p>
                    <p class="font-mono text-base font-bold text-brand-dark">{{ flow.delivery?.order?.order_number }}</p>
                </div>
                <div class="bg-brand-tint border border-brand-soft rounded-card p-4">
                    <p class="text-xs font-bold text-brand-dark  mb-1">Delivering to</p>
                    <p class="font-semibold text-brand-dark">{{ flow.delivery?.order?.customer?.name }}</p>
                    <p class="text-sm text-brand-dark mt-0.5">{{ flow.delivery?.delivery_address }}</p>
                    <div v-if="flow.delivery?.order?.contact_number" class="mt-2 flex items-center gap-2">
                        <a :href="`tel:${flow.delivery.order.contact_number}`" class="bg-brand-tint text-brand-dark text-xs sm:text-xs px-3 py-1.5 rounded-control border border-brand-soft font-bold flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            Call Customer
                        </a>
                        <span class="text-xs font-mono font-bold text-brand-dark">{{ flow.delivery.order.contact_number }}</span>
                    </div>
                </div>
                <!-- Camera box (delivery) -->
                <div class="bg-ink rounded-card overflow-hidden">
                    <div class="px-4 py-3 border-b border-ink-faint flex items-center justify-between">
                        <div>
                            <p class="text-white text-sm font-semibold">Camera Scanner</p>
                            <p class="text-ink-faint text-xs">Scan the package QR to confirm delivery</p>
                        </div>
                        <button v-if="flow.cameraActive" @click="stopCamera"
                            class="text-xs text-ink-faint hover:text-white border border-ink-faint px-2 py-1 rounded">
                            Stop
                        </button>
                    </div>
                    <div class="relative bg-black" style="min-height:240px">
                        <video ref="videoEl" autoplay playsinline muted
                            class="w-full" style="display:block;min-height:240px;object-fit:cover"
                            v-show="flow.cameraActive"></video>
                        <div v-if="!flow.cameraActive && !flow.scannedCode && !flow.cameraError"
                            class="absolute inset-0 flex flex-col items-center justify-center bg-ink">
                            <button @click="startCamera"
                                class="bg-brand hover:bg-brand-dark active:scale-95 text-white font-bold px-8 py-4 rounded-card text-base shadow-lg transition-all">
                                📷 Open Camera to Scan
                            </button>
                            <p class="text-ink-soft text-xs mt-3">Tap to scan the package barcode</p>
                        </div>
                        <div v-if="flow.cameraActive" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-52 h-52 relative">
                                <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-brand rounded-tl-control"></div>
                                <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-brand rounded-tr-control"></div>
                                <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-brand rounded-bl-control"></div>
                                <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-brand rounded-br-control"></div>
                            </div>
                        </div>
                        <div v-if="flow.cameraError" class="absolute inset-0 flex flex-col items-center justify-center bg-ink/95 text-center p-6">
                            <p class="text-white font-semibold mb-1">Camera unavailable</p>
                            <p class="text-ink-faint text-sm mb-3">{{ flow.cameraError }}</p>
                            <button @click="startCamera" class="bg-ink-soft hover:bg-ink-soft text-white text-sm font-semibold px-4 py-2 rounded-control">Retry</button>
                        </div>
                    </div>
                    <div class="px-4 py-3">
                        <div v-if="flow.scannedCode" class="flex items-center gap-3 bg-brand-dark/50 border border-brand rounded-control px-4 py-3">
                            <svg class="h-5 w-5 text-brand shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <div>
                                <p class="text-xs text-brand font-semibold ">Scanned</p>
                                <p class="font-mono font-bold text-sm text-brand-soft">{{ flow.scannedCode }}</p>
                            </div>
                            <button @click="flow.scannedCode = ''; startCamera()" class="ml-auto text-xs text-ink-faint hover:text-white">Rescan</button>
                        </div>
                        <div v-else-if="flow.cameraActive" class="flex items-center gap-2 text-ink-faint text-sm py-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Scanning...
                        </div>
                    </div>
                </div>
                <p v-if="flow.error" class="text-red-600 text-sm font-medium bg-red-50 border border-red-200 rounded-card p-3">{{ flow.error }}</p>
                <button @click="goToDeliveryStep2" :disabled="!flow.scannedCode"
                    class="w-full bg-brand text-white font-bold py-4 rounded-card disabled:opacity-40">
                    Next — Take Proof Photo
                </button>
            </div>

            <!-- Delivery Step 2: Take proof photo -->
            <div v-else-if="flow.step === 2" class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-brand-tint rounded-card flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-ink">Proof of Delivery Photo</h2>
                        <p class="text-xs text-ink-soft">Take a photo of the handover as proof of delivery</p>
                    </div>
                </div>
                <!-- Photo preview -->
                <div v-if="flow.photoPreview === 'processing'" class="bg-mist rounded-card border-2 border-dashed border-line flex flex-col items-center justify-center py-10">
                    <svg class="animate-spin h-8 w-8 text-brand mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-sm font-semibold text-ink-soft">Adding GPS Watermark...</p>
                </div>
                <div v-else-if="flow.photoPreview" class="rounded-card overflow-hidden border-2 border-brand-soft">
                    <img :src="flow.photoPreview" class="w-full max-h-64 object-cover" alt="Proof of delivery">
                </div>
                <div v-else class="bg-mist rounded-card border-2 border-dashed border-line flex flex-col items-center justify-center py-10">
                    <svg class="w-10 h-10 text-ink-faint mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <p class="text-ink-soft text-sm font-semibold">No photo taken yet</p>
                </div>
                <label class="w-full flex items-center justify-center gap-2 bg-brand text-white font-bold py-4 rounded-card cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                    {{ flow.photoPreview ? 'Retake Photo' : 'Take Photo' }}
                    <input type="file" accept="image/*" capture="environment" class="hidden" @change="onPhotoSelected">
                </label>
                <p v-if="flow.error" class="text-red-600 text-sm font-medium bg-red-50 border border-red-200 rounded-card p-3">{{ flow.error }}</p>
                <div class="flex gap-3">
                    <button @click="flow.step = 1; flow.photoPreview = ''; flow.photoFile = null; startCamera()" class="px-5 py-4 border-2 border-line text-ink-soft font-semibold rounded-card text-sm">Back</button>
                    <button @click="flow.step = 3" :disabled="!flow.photoPreview"
                        class="flex-1 bg-ink text-white font-bold py-4 rounded-card disabled:opacity-40">
                        Next — Confirm Delivery
                    </button>
                </div>
            </div>

            <!-- Delivery Step 3: Final confirm -->
            <div v-else-if="flow.step === 3" class="p-4 space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-brand-tint rounded-card flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-ink">Confirm Delivery</h2>
                        <p class="text-xs text-ink-soft">Hand over the package and confirm</p>
                    </div>
                </div>
                <div class="bg-brand-tint border border-brand-soft rounded-card p-4">
                    <p class="text-xs font-bold text-brand-dark  mb-1">Delivering to</p>
                    <p class="font-semibold text-brand-dark">{{ flow.delivery?.order?.customer?.name }}</p>
                </div>
                <div v-if="flow.photoPreview" class="rounded-card overflow-hidden border-2 border-brand-soft">
                    <img :src="flow.photoPreview" class="w-full max-h-40 object-cover" alt="Proof">
                    <p class="text-center text-xs text-brand-dark bg-brand-tint py-1.5 font-semibold">Proof photo ready</p>
                </div>
                <div v-if="flow.delivery?.order?.payment_method === 'cod'" class="bg-orange-50 border border-orange-200 rounded-card p-4">
                    <p class="text-sm font-bold text-orange-800 mb-1">COD — Collect Cash</p>
                    <p class="text-sm text-orange-700">Collect <strong>₱{{ Number(flow.delivery.order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</strong> from customer now. Return cash to distributor later.</p>
                </div>
                <p v-if="flow.error" class="text-red-600 text-sm font-medium bg-red-50 border border-red-200 rounded-card p-3">{{ flow.error }}</p>
                <div class="flex gap-3">
                    <button @click="flow.step = 2" class="px-5 py-4 border-2 border-line text-ink-soft font-semibold rounded-card text-sm">Back</button>
                    <button @click="doConfirmDelivery" :disabled="flow.loading"
                        class="flex-1 bg-brand text-white font-bold py-4 rounded-card disabled:opacity-40 flex items-center justify-center gap-2">
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
            <div class="px-5 py-4 bg-gradient-to-r from-brand-dark to-brand sticky top-0 z-10">
                <div class="flex justify-between items-center mb-3">
                    <h1 class="text-lg font-bold text-white tracking-tight">Courier Dashboard</h1>
                    <div class="flex items-center gap-2 bg-white/20 rounded-full px-3 py-1">
                        <span class="text-xs font-bold text-white">₱{{ earnings.pending_remittance.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
                        <span class="text-white/70 text-xs">Pending Remittance</span>
                    </div>
                </div>
                <div class="flex space-x-1 bg-brand-dark/40 p-1 rounded-card">
                    <button @click="tab = 'active'"
                        class="flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold rounded-control transition"
                        :class="tab === 'active' ? 'bg-white text-brand-dark shadow' : 'text-white/80 hover:bg-white/10'">
                        <span>Active</span>
                        <span v-if="myDeliveries.length > 0" class="bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full font-semibold">{{ myDeliveries.length }}</span>
                    </button>
                    <button @click="tab = 'pool'"
                        class="flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold rounded-control transition"
                        :class="tab === 'pool' ? 'bg-white text-brand-dark shadow' : 'text-white/80 hover:bg-white/10'">
                        <span>Pool</span>
                        <span v-if="availableDeliveries.length > 0" class="bg-brand-soft text-brand-dark text-xs px-1.5 py-0.5 rounded-full font-semibold">{{ availableDeliveries.length }}</span>
                    </button>
                    <button @click="tab = 'history'"
                        class="flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold rounded-control transition"
                        :class="tab === 'history' ? 'bg-white text-brand-dark shadow' : 'text-white/80 hover:bg-white/10'">
                        History
                    </button>
                </div>
            </div>

            <div class="p-4 bg-mist">

                <!-- ACTIVE TAB -->
                <div v-if="tab === 'active'">
                    <div v-if="myDeliveries.length > 0" class="space-y-4">
                        <div v-for="delivery in myDeliveries" :key="delivery.id"
                            class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                            <div class="p-4 border-b border-line flex justify-between items-center"
                                :class="{
                                    'bg-brand-tint': delivery.status === 'in_transit',
                                    'bg-amber-50': delivery.status === 'scheduled',
                                    'bg-yellow-50': delivery.status === 'picking_up',
                                }">
                                <div>
                                    <span class="text-xs font-bold text-ink-faint  ">Tracking</span>
                                    <p class="font-mono text-sm font-bold text-ink">{{ delivery.tracking_number }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="delivery.order?.payment_method === 'cod'"
                                        class="bg-orange-100 text-orange-700 text-xs font-semibold px-2 py-1 rounded-full  border border-orange-200">COD</span>
                                    <span v-if="delivery.order?.is_fragile"
                                        class="bg-rose-100 text-rose-700 text-xs font-semibold px-2 py-1 rounded-full  border border-rose-200">Fragile</span>
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-control "
                                        :class="{
                                            'bg-brand-tint text-brand-dark': delivery.status === 'in_transit',
                                            'bg-amber-100 text-amber-800': delivery.status === 'scheduled',
                                            'bg-yellow-100 text-yellow-800': delivery.status === 'picking_up',
                                        }">{{ delivery.status.replace('_', ' ') }}</span>
                                </div>
                            </div>
                            <div class="p-4 space-y-3">
                                    <div>
                                        <p class="text-xs text-ink-faint font-bold ">Pickup</p>
                                        <p class="text-sm font-semibold text-ink">{{ delivery.order?.distributor?.company_name }}</p>
                                        <p class="text-xs text-ink-soft">{{ delivery.seller_address || delivery.order?.distributor?.address }}</p>
                                        <div v-if="delivery.status === 'scheduled' || delivery.status === 'picking_up'" class="mt-2">
                                            <a :href="`https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(delivery.seller_address || delivery.order?.distributor?.address)}`"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 text-xs font-bold text-brand hover:text-brand-dark">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                Navigate to Shop
                                            </a>
                                        </div>
                                    </div>
                                <div class="flex items-start gap-3 pl-1 border-l-2 border-dashed border-line ml-3">
                                    <div class="w-6 h-6 bg-brand-tint rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 -ml-4">
                                        <svg class="w-3 h-3 text-brand" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-ink-faint font-bold ">Dropoff</p>
                                        <p class="text-sm font-semibold text-ink">{{ delivery.order?.customer?.name }}</p>
                                        <p class="text-xs text-ink-soft">{{ delivery.delivery_address }}</p>
                                        <p v-if="delivery.order?.contact_number" class="text-xs text-brand font-bold mt-1">📞 {{ delivery.order.contact_number }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 mt-1">
                                    <div class="w-6 h-6 bg-amber-50 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-ink-faint font-bold ">Seller Contact</p>
                                        <p v-if="delivery.order?.distributor?.user?.phone_number" class="text-xs text-amber-700 font-bold">📞 {{ delivery.order.distributor.user.phone_number }}</p>
                                        <p v-else class="text-xs text-ink-faint">Not listed</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-mist flex gap-2">
                                <button v-if="delivery.status === 'scheduled'"
                                    @click="openPickupFlow(delivery)"
                                    class="flex-1 bg-brand text-white font-bold py-2.5 rounded-card text-sm">
                                    Start Delivery
                                </button>
                                <button v-if="delivery.status === 'scheduled'"
                                    @click="cancelDelivery(delivery.id)"
                                    class="flex-none bg-red-100 text-red-600 font-bold py-2.5 px-4 rounded-card text-sm transition hover:bg-red-200">
                                    Cancel
                                </button>
                                <button v-if="delivery.status === 'picking_up'"
                                    @click="openPickupFlow(delivery, 2)"
                                    class="flex-1 bg-yellow-500 text-white font-bold py-2.5 rounded-card text-sm">
                                    Continue — Scan Package
                                </button>
                                <button v-if="delivery.status === 'in_transit'"
                                    @click="openDeliveryFlow(delivery)"
                                    class="flex-1 bg-brand text-white font-bold py-2.5 rounded-card text-sm">
                                    Deliver Package
                                </button>
                                <a v-if="delivery.status === 'in_transit' && delivery.order?.delivery_latitude && delivery.order?.delivery_longitude"
                                    :href="`https://www.google.com/maps/dir/?api=1&destination=${delivery.order.delivery_latitude},${delivery.order.delivery_longitude}`"
                                    target="_blank"
                                    class="flex-1 bg-white border border-brand-soft text-brand font-bold py-2.5 rounded-card text-sm flex items-center justify-center">
                                    Navigate
                                </a>
                                <button v-if="delivery.status === 'in_transit'"
                                    @click="openFailureModal(delivery)"
                                    class="flex-none bg-red-100 text-red-600 font-bold py-2.5 px-4 rounded-card text-sm transition hover:bg-red-200">
                                    Report Issue
                                </button>
                                <div v-if="delivery.attempts_count > 0" class="absolute top-2 right-2">
                                    <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-1.5 py-0.5 rounded-full border border-amber-200 ">Attempt {{ delivery.attempts_count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-16">
                        <p class="text-ink-soft font-semibold">No active deliveries</p>
                        <p class="text-xs text-ink-faint mt-1">Check the pool to pick up jobs.</p>
                    </div>
                </div>

                <!-- POOL TAB -->
                <div v-else-if="tab === 'pool'">
                    <div v-if="availableDeliveries.length > 0" class="space-y-4">
                        <div v-for="job in availableDeliveries" :key="job.id"
                            class="bg-white rounded-card shadow-sm border border-line p-4">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-2">
                                    <span v-if="job.order?.payment_method === 'cod'"
                                        class="bg-orange-100 text-orange-700 text-xs font-semibold px-2 py-1 rounded-full border border-orange-200">COD</span>
                                    <span class="bg-brand-tint text-brand-dark text-xs font-semibold px-2 py-1 rounded-full">{{ job.order?.items?.length || 0 }} item(s)</span>
                                    
                                    <span v-if="job.order?.is_fragile"
                                        class="bg-rose-100 text-rose-700 text-xs font-semibold px-2 py-1 rounded-full border border-rose-200 ">Fragile</span>
                                    <span class="flex items-center gap-1 bg-mist text-ink text-xs font-semibold px-2 py-1 rounded-full border border-line ">
                                        <svg v-if="job.order?.required_vehicle_type === 'motorcycle' || !job.order?.required_vehicle_type" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V6m0 0l-3 3m3-3l3 3M5 19h14M8 12a4 4 0 108 0 4 4 0 00-8 0z" /></svg>
                                        <svg v-else-if="job.order?.required_vehicle_type.includes('car')" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 10V8a2 2 0 012-2h10a2 2 0 012 2v2M4 14a2 2 0 110-4 2 2 0 010 4zm16 0a2 2 0 110-4 2 2 0 010 4z" /></svg>
                                        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h10M8 11h10M5 15h14v2a2 2 0 01-2 2H7a2 2 0 01-2-2v-2zM3 5h1a2 2 0 012 2v8" /></svg>
                                        {{ formatVehicleType(job.order?.required_vehicle_type) }} Required
                                    </span>
                                </div>
                                <div v-if="job.courier_fee" class="text-right">
                                    <p class="text-xs text-ink-faint font-bold ">Earn</p>
                                    <p class="text-sm font-semibold text-brand">+₱{{ Number(job.courier_fee).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                                </div>
                            </div>
                            <div class="mb-4">
                                <p class="text-xs text-amber-600 font-bold   mb-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    Pick Up From
                                </p>
                                <p class="text-sm font-semibold text-ink leading-tight">{{ job.order?.distributor?.company_name }}</p>
                                <p class="text-xs text-ink-soft mt-0.5">{{ job.order?.distributor?.address }}</p>
                            </div>

                            <div class="mb-4">
                                <p class="text-xs text-brand font-bold   mb-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Delivery Destination
                                </p>
                                <p class="text-sm font-bold text-ink leading-tight">{{ job.delivery_address }}</p>
                                <p class="text-xs text-ink-soft mt-0.5">Customer: {{ job.order?.customer?.name }}</p>
                            </div>
                            <div v-if="job.order?.payment_method === 'cod'" class="mb-3 text-xs text-orange-700 bg-orange-50 rounded-control px-3 py-2 border border-orange-100">
                                Collect <strong>₱{{ Number(job.order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</strong> cash from customer
                            </div>
                            <button @click="acceptDelivery(job.id)"
                                :disabled="!canAcceptJob(job)"
                                class="w-full text-white font-bold py-3 rounded-card text-sm transition-colors"
                                :class="canAcceptJob(job) ? 'bg-ink hover:bg-black' : 'bg-line cursor-not-allowed'">
                                <span v-if="canAcceptJob(job)">Accept Job</span>
                                <span v-else>Vehicle Too Small</span>
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-center py-16">
                        <p class="text-ink-soft font-semibold">No jobs available right now</p>
                    </div>

                    <!-- Contact Admin / Support Section -->
                    <div class="mt-8 border-t border-line pt-8 pb-12">
                        <h3 class="text-sm font-semibold text-ink-faint   mb-4">Authority Support</h3>
                        <div class="grid grid-cols-1 gap-3">
                            <a href="tel:09123456789" class="bg-ink text-white rounded-card p-4 flex items-center justify-between shadow-lg active:scale-95 transition-all">
                                <div>
                                    <p class="text-xs font-bold text-ink-faint   mb-1">Police / Emergency</p>
                                    <p class="text-base font-semibold">911 Local Hotline</p>
                                </div>
                                <div class="w-10 h-10 bg-red-600 rounded-card flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                            </a>
                            <a href="mailto:support@medequip.com" class="bg-white border border-line rounded-card p-4 flex items-center justify-between shadow-sm active:scale-95 transition-all">
                                <div>
                                    <p class="text-xs font-bold text-ink-faint   mb-1">Platform Admin</p>
                                    <p class="text-base font-semibold text-ink">Contact Dispatch Support</p>
                                </div>
                                <div class="w-10 h-10 bg-brand-tint rounded-card flex items-center justify-center">
                                    <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                </div>
                            </a>
                        </div>
                        <p class="text-center text-xs text-ink-faint mt-4 leading-normal">
                            Use these contacts for missing recipients, wrong addresses, or accidents during transit. 
                            Platform admins can see your live GPS location if you allow it.
                        </p>
                    </div>
                </div>

                <!-- HISTORY TAB -->
                <div v-else-if="tab === 'history'">
                    <div class="grid grid-cols-3 gap-3 mb-5">
                        <div class="bg-white rounded-card p-4 text-center border border-line shadow-sm">
                            <p class="text-xs font-bold text-ink-faint   mb-1">Pending</p>
                            <p class="text-lg font-semibold text-brand">₱{{ earnings.pending_earnings.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                        </div>
                        <div class="bg-white rounded-card p-4 text-center border border-line shadow-sm">
                            <p class="text-xs font-bold text-ink-faint   mb-1">Transferred</p>
                            <p class="text-lg font-semibold text-brand">₱{{ earnings.transferred_earnings.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                        </div>
                        <div class="bg-white rounded-card p-4 text-center border border-line shadow-sm">
                            <p class="text-xs font-bold text-ink-faint   mb-1">All Time</p>
                            <p class="text-lg font-semibold text-brand">₱{{ earnings.total_earned.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                        </div>
                        <div class="bg-white rounded-card p-4 text-center border border-line shadow-sm">
                            <p class="text-xs font-bold text-ink-faint   mb-1">Deliveries</p>
                            <p class="text-lg font-semibold text-ink">{{ earnings.total_deliveries }}</p>
                        </div>
                    </div>
                    <div v-if="history.data?.length > 0" class="space-y-3">
                        <div v-for="item in history.data" :key="item.id"
                            class="bg-white rounded-card border border-line shadow-sm overflow-hidden">
                            <div class="p-4 flex justify-between items-start">
                                <div class="flex-1 min-w-0 pr-3">
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="text-xs font-bold text-ink font-mono">{{ item.tracking_number }}</p>
                                        <span v-if="item.order?.payment_method === 'cod'"
                                            class="text-xs bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full font-bold border border-orange-200">COD</span>
                                    </div>
                                    <p class="text-xs text-ink-soft truncate">{{ item.order?.customer?.name }}</p>
                                    <p class="text-xs text-ink-faint mt-1">{{ item.order?.distributor?.company_name }}</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="inline-block text-xs font-semibold px-2 py-1 rounded-full "
                                        :class="item.status === 'delivered' ? 'bg-brand-tint text-brand-dark' : 'bg-red-100 text-red-600'">
                                        {{ item.status }}
                                    </span>
                                    <p class="text-xs text-ink-faint mt-1">{{ item.actual_delivery_at ? new Date(item.actual_delivery_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '' }}</p>
                                    <p v-if="item.courier_fee && item.status === 'delivered'" class="text-xs font-bold text-brand mt-1">+₱{{ Number(item.courier_fee).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                                </div>
                            </div>
                            <!-- COD Remittance -->
                            <div v-if="item.order?.payment_method === 'cod' && item.status === 'delivered'"
                                class="px-4 py-3 border-t"
                                :class="item.cod_remitted_at ? 'bg-brand-tint border-brand-soft' : 'bg-orange-50 border-orange-100'">
                                <p class="text-xs font-bold mb-2"
                                    :class="item.cod_remitted_at ? 'text-brand-dark' : 'text-orange-700'">
                                    {{ item.cod_remitted_at ? 'Cash remitted to distributor' : 'Pending: Remit ₱' + Number(item.cod_amount || item.order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) + ' to distributor' }}
                                </p>
                                <!-- Remittance action button -->
                                <button v-if="!item.cod_remitted_at && !item.cod_remittance_sent_at"
                                    @click="markRemittanceSent(item.id)"
                                    class="w-full bg-orange-600 text-white font-bold py-2.5 rounded-card text-xs">
                                    I have given cash to the distributor
                                </button>
                                <p v-else-if="item.cod_remittance_sent_at && !item.cod_remitted_at"
                                    class="text-xs text-orange-600 font-semibold">
                                    Waiting for distributor to confirm receipt...
                                </p>
                            </div>
                        </div>
                        <div v-if="history.last_page > 1" class="flex justify-center gap-2 py-4">
                            <button v-if="history.prev_page_url" @click="goToPage(history.current_page - 1)"
                                class="px-4 py-2 text-sm font-bold bg-white border border-line rounded-card">Prev</button>
                            <span class="px-4 py-2 text-sm text-ink-soft">{{ history.current_page }} / {{ history.last_page }}</span>
                            <button v-if="history.next_page_url" @click="goToPage(history.current_page + 1)"
                                class="px-4 py-2 text-sm font-bold bg-white border border-line rounded-card">Next</button>
                        </div>
                    </div>
                    <div v-else class="text-center py-16">
                        <p class="text-ink-soft font-semibold">No delivery history yet</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Failure Reason Modal -->
        <div v-if="failureModal.show" class="fixed inset-0 z-[100] bg-black/60  flex items-end sm:items-center justify-center">
            <div class="bg-white w-full sm:max-w-md rounded-t-[2.5rem] sm:rounded-card p-6 sm:p-8   duration-300">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-ink">Report Delivery Issue</h2>
                    <button @click="failureModal.show = false" class="p-2 text-ink-faint hover:text-ink-soft">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-semibold text-ink-faint   mb-3">Reason for Failure</label>
                        <div class="grid grid-cols-1 gap-2">
                            <button v-for="reason in failureReasons" :key="reason.value"
                                @click="failureModal.reason = reason.value"
                                class="w-full text-left px-4 py-3 rounded-card border-2 transition-all font-bold text-sm"
                                :class="failureModal.reason === reason.value ? 'border-red-600 bg-red-50 text-red-900' : 'border-line text-ink-soft hover:border-line'">
                                {{ reason.label }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink-faint   mb-3">Evidence Photo (Required)</label>
                        <div class="relative group">
                            <input type="file" accept="image/*" @change="e => failureModal.photo = e.target.files[0]" 
                                class="hidden" ref="failurePhotoInput" />
                            <button @click="$refs.failurePhotoInput.click()" 
                                class="w-full h-32 rounded-card border-2 border-dashed flex flex-col items-center justify-center gap-2 transition-all"
                                :class="failureModal.photo ? 'border-brand bg-brand-tint' : 'border-line bg-mist/50 hover:border-red-200'">
                                <svg v-if="!failureModal.photo" class="w-8 h-8 text-ink-faint group-hover:text-red-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <svg v-else class="w-8 h-8 text-brand animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-xs font-semibold " :class="failureModal.photo ? 'text-brand-dark' : 'text-ink-faint group-hover:text-red-500'">
                                    {{ failureModal.photo ? 'Photo Attached' : 'Capture Proof' }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-ink-faint   mb-3">Additional Details</label>
                        <textarea v-model="failureModal.note" 
                            rows="3"
                            class="w-full rounded-card border-2 border-line focus:border-red-600 focus:ring-0 text-sm font-semibold p-4"
                            placeholder="Describe the situation (e.g. guardhouse refused entry)..."></textarea>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-card p-4">
                        <p class="text-xs text-amber-800 font-semibold leading-relaxed">
                            <strong>Note:</strong> Reporting a failure marks Attempt #{{ (failureModal.delivery?.attempts_count || 0) + 1 }}. 
                            If it fails twice, the order will be automatically flagged for <strong>Return to Sender</strong>.
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button @click="failureModal.show = false" class="flex-1 py-4 text-ink-soft font-bold">Cancel</button>
                        <button @click="submitFailure" 
                            :disabled="!failureModal.reason || flow.loading"
                            class="flex-[2] bg-red-600 text-white font-semibold py-4 rounded-card shadow-lg shadow-red-200 disabled:opacity-50 active:scale-95 transition-all">
                            Submit Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </CourierLayout>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import CourierLayout from '@/Layouts/CourierLayout.vue';
// @zxing/browser is loaded dynamically in startCamera() to avoid crashing the component

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

const failureReasons = [
    { label: "Recipient wasn't at delivery address", value: 'recipient_absent' },
    { label: "Recipient has the wrong delivery address", value: 'wrong_address' },
    { label: "Delivery accident or vehicle issues", value: 'delivery_accident' },
    { label: "Recipient refused the items", value: 'customer_refused' },
    { label: "Other problem", value: 'other' },
];

const failureModal = ref({
    show: false,
    delivery: null,
    reason: '',
    note: '',
    photo: null,
});

const flow = ref({
    step: 1,
    delivery: null,
    loading: false,
    error: '',
    cameraActive: false,
    scannedCode: '',
    cameraError: '',
    photoPreview: '',
    photoFile: null,
});

const resetFlow = () => ({
    step: 1, delivery: null, loading: false, error: '',
    cameraActive: false, scannedCode: '', cameraError: '',
    photoPreview: '', photoFile: null,
    proof_latitude: null, proof_longitude: null,
});

const openPickupFlow = (delivery, step = 1) => {
    flow.value = { ...resetFlow(), delivery, step };
    activeFlow.value = 'pickup';
};

const openDeliveryFlow = (delivery) => {
    flow.value = { ...resetFlow(), delivery, step: 1 };
    activeFlow.value = 'delivery';
};

const closeFlow = () => {
    stopCamera();
    activeFlow.value = null;
    flow.value = resetFlow();
};

// ── Camera actions ──
const startCamera = async () => {
    flow.value.cameraActive = false;
    flow.value.scannedCode = '';
    flow.value.cameraError = '';
    await new Promise(r => setTimeout(r, 150));
    try {
        // Dynamic import so a load failure doesn't white-screen the whole page
        const { BrowserMultiFormatReader } = await import('@zxing/browser');
        if (!codeReader) {
            codeReader = new BrowserMultiFormatReader();
        }
        const devices = await BrowserMultiFormatReader.listVideoInputDevices();
        if (!devices.length) { flow.value.cameraError = 'No camera found on this device.'; return; }
        const back = devices.find(d => /back|rear|environment/i.test(d.label));
        const selectedCamera = back?.deviceId ?? devices[0].deviceId;
        
        flow.value.cameraActive = true;
        cameraControls = await codeReader.decodeFromVideoDevice(
            selectedCamera, videoEl.value,
            (result) => {
                if (result && !flow.value.scannedCode) {
                    flow.value.scannedCode = result.getText();
                    stopCamera();
                }
            }
        );
    } catch (e) {
        flow.value.cameraError = e.message || 'Camera access denied. Check your browser settings.';
        flow.value.cameraActive = false;
    }
};

const stopCamera = () => {
    if (cameraControls) {
        cameraControls.stop();
        cameraControls = null;
    }
    flow.value.cameraActive = false;
};

// Photo selection
const onPhotoSelected = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    flow.value.photoPreview = 'processing';

    try {
        // GPS retrieval with absolute safety timeout
        const position = await new Promise((resolve, reject) => {
            const timeoutId = setTimeout(() => {
                reject(new Error("GPS_TIMEOUT"));
            }, 5000); // 5 seconds max for GPS
            
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    clearTimeout(timeoutId);
                    resolve(pos);
                },
                (err) => {
                    clearTimeout(timeoutId);
                    reject(err);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 4500, // Slightly less than our own timeout
                    maximumAge: 0
                }
            );
        });

        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        flow.value.proof_latitude = lat;
        flow.value.proof_longitude = lng;

        const photoUrl = URL.createObjectURL(file);
        const img = new Image();
        img.src = photoUrl;

        await new Promise((resolve, reject) => {
            img.onload = resolve;
            img.onerror = () => reject(new Error("IMG_LOAD_ERROR"));
        });

        const canvas = document.createElement('canvas');
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext('2d');

        ctx.drawImage(img, 0, 0);

        const fontSize = Math.max(16, canvas.height * 0.03); 
        const boxHeight = fontSize * 3 + 40;

        ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
        ctx.fillRect(0, canvas.height - boxHeight, canvas.width, boxHeight);

        ctx.fillStyle = 'white';
        ctx.font = `bold ${fontSize}px monospace`;

        const dateStr = new Date().toLocaleString() + ' (Local)';
        const locStr = `GPS: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        const orderStr = `Order: ${flow.value.delivery?.order?.order_number || 'Unknown'}`;

        ctx.fillText(orderStr, 20, canvas.height - boxHeight + fontSize + 10);
        ctx.fillText(locStr, 20, canvas.height - boxHeight + (fontSize * 2) + 20);
        ctx.fillText(dateStr, 20, canvas.height - boxHeight + (fontSize * 3) + 30);

        canvas.toBlob((blob) => {
            const newFile = new File([blob], file.name, { type: file.type });
            flow.value.photoFile = newFile;
            flow.value.photoPreview = URL.createObjectURL(newFile);
        }, file.type, 0.9);

    } catch (err) {
        console.error("GPS or Watermark Error:", err);
        // If GPS fails or times out, we still let them proceed with the original photo
        flow.value.proof_latitude = null;
        flow.value.proof_longitude = null;
        flow.value.photoFile = file;
        flow.value.photoPreview = URL.createObjectURL(file);
        
        if (err.message === "GPS_TIMEOUT") {
            console.warn("GPS timed out. Proceeding without watermark.");
        } else if (err.code === 1) {
            console.warn("User denied Geolocation. Proceeding without watermark.");
        }
    }
};

// ── Pickup flow actions ──
const doStartPickup = () => {
    flow.value.loading = true;
    flow.value.error = '';
    router.post('/courier/deliveries/' + flow.value.delivery.id + '/start-pickup', {}, {
        preserveScroll: true,
        onSuccess: () => { flow.value.step = 2; flow.value.loading = false; },
        onError: (errors) => { flow.value.error = Object.values(errors)[0] || 'Something went wrong.'; flow.value.loading = false; },
    });
};

const doPickupScan = () => {
    flow.value.loading = true;
    flow.value.error = '';
    router.post('/courier/deliveries/' + flow.value.delivery.id + '/confirm-scan',
        { order_number: flow.value.scannedCode },
        {
            preserveScroll: true,
            onSuccess: () => { flow.value.step = 3; flow.value.loading = false; },
            onError: (errors) => {
                flow.value.error = Object.values(errors)[0] || 'Code mismatch. Re-scan the package.';
                flow.value.loading = false;
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
    flow.value.step = 2;
};

const doConfirmDelivery = () => {
    if (!flow.value.photoFile) { flow.value.error = 'Please take a proof of delivery photo.'; return; }
    flow.value.loading = true;
    flow.value.error = '';
    const formData = new FormData();
    formData.append('order_number', flow.value.scannedCode);
    formData.append('proof_photo', flow.value.photoFile);
    if (flow.value.proof_latitude && flow.value.proof_longitude) {
        formData.append('proof_latitude', flow.value.proof_latitude);
        formData.append('proof_longitude', flow.value.proof_longitude);
    }
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

const cancelDelivery = (id) => {
    if (confirm('Are you sure you want to cancel this job? It will be returned to the dispatch pool.')) {
        router.post('/courier/deliveries/' + id + '/cancel', {}, {
            preserveScroll: true,
            onSuccess: () => {
                // If there are no more active deliveries, switch to pool tab
                if (props.myDeliveries.length <= 1) {
                    tab.value = 'pool';
                }
            }
        });
    }
};

const markRemittanceSent = (id) => {
    if (confirm('Confirm that you have physically handed the cash to the distributor?')) {
        router.post('/courier/deliveries/' + id + '/remittance-sent', {}, { preserveScroll: true });
    }
};

const openFailureModal = (delivery) => {
    failureModal.value = {
        show: true,
        delivery: delivery,
        reason: '',
        note: '',
    };
};

const submitFailure = () => {
    if (!failureModal.value.photo) {
        alert("Please provide a photo as proof of attempt.");
        return;
    }
    flow.value.loading = true;
    
    router.post(`/courier/deliveries/${failureModal.value.delivery.id}/report-failure`, {
        reason: failureModal.value.reason,
        note: failureModal.value.note,
        reason_photo: failureModal.value.photo,
    }, {
        forceFormData: true,
        onSuccess: () => {
            failureModal.value.show = false;
            flow.value.loading = false;
        },
        onError: () => {
            flow.value.loading = false;
        }
    });
};

const goToPage = (page) => {
    router.get('/courier/dashboard', { page }, { preserveScroll: true, preserveState: true });
};

const getVehicleWeight = (type) => {
    const weights = {
        'motorcycle': 1,
        'car_sedan': 2,
        'car_hatchback': 3,
        'pickup_truck': 4,
        'box_truck': 5
    };
    return weights[type] || 1;
};

const formatVehicleType = (type) => {
    if (!type) return 'Motorcycle';
    return type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
};

const canAcceptJob = (job) => {
    const jobWeight = getVehicleWeight(job.order?.required_vehicle_type);
    const courierWeight = getVehicleWeight(props.courier?.vehicle_type);
    return courierWeight >= jobWeight;
};

onBeforeUnmount(stopCamera);

</script>