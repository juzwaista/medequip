<template>
    <CourierLayout>
        <!-- Step 1: Camera Scanning -->
        <div v-if="step === 'scan'" class="flex flex-col h-full">
            <!-- Header -->
            <div class="px-4 py-4 border-b border-line flex items-center gap-3">
                <Link href="/courier/dashboard" class="text-ink-faint hover:text-ink-soft">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </Link>
                <div>
                    <h2 class="text-base font-bold text-ink">
                        {{ form.action === 'pickup' ? 'Scan Waybill QR' : 'Scan to Confirm Delivery' }}
                    </h2>
                    <p class="text-xs text-ink-soft">Point camera at the QR code on the waybill</p>
                </div>
            </div>

            <!-- Camera View -->
            <div class="relative bg-black flex-1 min-h-[300px]">
                <qrcode-stream
                    @detect="onCameraScanDetect"
                    @error="onCameraError"
                    :track="paintBoundingBox"
                    class="w-full h-full"
                ></qrcode-stream>

                <!-- Scanning reticle overlay -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-56 h-56 border-2 border-white/60 rounded-card relative">
                        <span class="absolute top-0 left-0 w-6 h-6 border-t-4 border-l-4 border-brand rounded-tl-control"></span>
                        <span class="absolute top-0 right-0 w-6 h-6 border-t-4 border-r-4 border-brand rounded-tr-control"></span>
                        <span class="absolute bottom-0 left-0 w-6 h-6 border-b-4 border-l-4 border-brand rounded-bl-control"></span>
                        <span class="absolute bottom-0 right-0 w-6 h-6 border-b-4 border-r-4 border-brand rounded-br-control"></span>
                    </div>
                </div>

                <!-- Error overlay -->
                <div v-if="cameraError" class="absolute inset-0 bg-ink/90 flex flex-col items-center justify-center p-6 text-center">
                    <svg class="h-10 w-10 text-red-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <p class="text-white font-semibold mb-1">Camera Unavailable</p>
                    <p class="text-ink-faint text-sm">{{ cameraError }}</p>
                    <p class="text-ink-soft text-xs mt-2">Use manual entry below instead.</p>
                </div>
            </div>

            <!-- Removed Manual Entry Fallback for stricter verification -->
            <div class="p-4 bg-white border-t border-line flex-1">
                <div v-if="initialOrder" class="bg-brand-tint border border-brand-soft rounded-card px-4 py-3 flex items-center gap-3 w-full">
                    <svg class="h-5 w-5 text-brand flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="text-xs font-bold text-brand-dark">Expected Package</p>
                        <p class="text-sm font-mono font-bold text-brand-dark">{{ initialOrder }}</p>
                        <p class="text-xs text-brand mt-0.5">Point camera at the waybill QR code on the physical package to verify and confirm.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Verify Scanned Order -->
        <div v-else-if="step === 'verify'" class="flex flex-col h-full">
            <!-- Header -->
            <div class="px-4 py-4 border-b border-line flex items-center gap-3">
                <button @click="resetToScan" class="text-ink-faint hover:text-ink-soft">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </button>
                <div>
                    <h2 class="text-base font-bold text-ink">Verify Order</h2>
                    <p class="text-xs text-ink-soft">Confirm you have the correct package</p>
                </div>
            </div>

            <div class="flex-1 p-4 space-y-4 overflow-y-auto">
                <!-- Badge: Context depends on how order was found -->
                <div class="bg-brand-tint border border-brand-soft rounded-card p-3 flex items-center gap-3">
                    <div class="h-8 w-8 bg-brand-tint rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="h-5 w-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-brand-dark">QR Scanned Successfully</p>
                        <p class="text-xs text-brand font-mono">{{ form.order_number }}</p>
                    </div>
                </div>

                <!-- Order details -->
                <div v-if="scannedOrder" class="bg-white border border-line rounded-card overflow-hidden">
                    <div class="bg-mist px-4 py-3 border-b border-line">
                        <p class="text-xs font-bold text-ink-soft  ">Order Details</p>
                    </div>
                    <div class="p-4 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-soft">Order #</span>
                            <span class="font-mono font-bold text-ink">{{ scannedOrder.order_number }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-soft">Customer</span>
                            <span class="font-semibold text-ink">{{ scannedOrder.customer?.name }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-soft">Deliver to</span>
                            <span class="font-semibold text-ink text-right max-w-[200px]">{{ scannedOrder.delivery_address }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-soft">Status</span>
                            <span class="font-bold capitalize" :class="scannedOrder.status === 'packed' ? 'text-brand' : 'text-ink'">{{ scannedOrder.status }}</span>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div v-else-if="lookingUp" class="text-center py-8 text-ink-faint">
                    <svg class="animate-spin h-8 w-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-sm">Looking up order...</p>
                </div>

                <!-- Not found -->
                <div v-else class="bg-red-50 border border-red-200 rounded-card p-4 text-center">
                    <p class="text-red-700 font-semibold text-sm">Order Not Found</p>
                    <p class="text-red-500 text-xs mt-1">No order matched "{{ form.order_number }}"</p>
                    <button @click="resetToScan" class="mt-3 text-brand font-semibold text-sm hover:underline">Try Again</button>
                </div>
            </div>

            <!-- Confirm / Cancel -->
            <div v-if="scannedOrder" class="p-4 border-t border-line bg-white">
                <button
                    @click="step = 'confirm'"
                    class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-3.5 rounded-card transition"
                >
                    This is the correct package
                </button>
                <button
                    @click="resetToScan"
                    class="w-full mt-2 text-ink-soft font-medium py-2 text-sm hover:text-ink"
                >
                    Scan a different package
                </button>
            </div>
        </div>

        <!-- Step 3a: Slide to Confirm Pickup -->
        <div v-else-if="step === 'confirm' && form.action === 'pickup'" class="flex flex-col h-full">
            <div class="px-4 py-4 border-b border-line flex items-center gap-3">
                <button @click="step = 'verify'" class="text-ink-faint hover:text-ink-soft">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </button>
                <div>
                    <h2 class="text-base font-bold text-ink">Confirm Pickup</h2>
                    <p class="text-xs text-ink-soft">Slide to confirm you have collected the package</p>
                </div>
            </div>

            <div class="flex-1 flex flex-col items-center justify-center p-8 space-y-8">
                <!-- Order summary pill -->
                <div class="bg-mist rounded-card px-6 py-4 text-center w-full max-w-xs">
                    <p class="text-xs font-bold text-ink-soft   mb-1">Collecting Package</p>
                    <p class="font-mono text-lg font-bold text-ink">{{ form.order_number }}</p>
                    <p class="text-sm text-ink-soft mt-1">{{ scannedOrder?.customer?.name }}</p>
                </div>

                <!-- Slide to confirm -->
                <div class="w-full space-y-3">
                    <div class="relative w-full h-16 bg-line rounded-full overflow-hidden flex items-center shadow-inner select-none">
                        <div class="absolute left-0 h-full bg-brand transition-none rounded-full" :style="{ width: sliderValue + '%' }"></div>
                        <input
                            type="range"
                            min="0"
                            max="100"
                            v-model="sliderValue"
                            @change="checkSlider"
                            @touchend="resetSlider"
                            @mouseup="resetSlider"
                            class="absolute w-full h-full opacity-0 cursor-pointer z-10"
                        />
                        <div class="absolute w-full text-center font-bold text-sm pointer-events-none transition-colors duration-200" :class="sliderValue > 50 ? 'text-white' : 'text-ink-soft'">
                            <span v-if="form.processing">Processing...</span>
                            <span v-else>Slide to confirm pickup</span>
                        </div>
                        <div class="absolute h-12 w-12 bg-white rounded-full shadow-md flex items-center justify-center pointer-events-none z-0 transition-none" :style="{ left: `calc(${sliderValue}% - ${sliderValue * 0.48}px)`, marginLeft: '4px' }">
                            <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </div>
                    <p class="text-xs text-center text-ink-faint">Slide all the way to the right to confirm</p>
                </div>
            </div>
        </div>

        <!-- Step 3b: Proof of Delivery Photo -->
        <div v-else-if="step === 'confirm' && form.action === 'deliver'" class="flex flex-col h-full">
            <div class="px-4 py-4 border-b border-line flex items-center gap-3">
                <button @click="step = 'verify'" class="text-ink-faint hover:text-ink-soft">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </button>
                <div>
                    <h2 class="text-base font-bold text-ink">Proof of Delivery</h2>
                    <p class="text-xs text-ink-soft">Take a photo as proof of delivery</p>
                </div>
            </div>

            <div class="flex-1 flex flex-col p-4 space-y-4 overflow-y-auto">
                <!-- Order pill -->
                <div class="bg-mist rounded-card px-4 py-3 flex items-center gap-3">
                    <div class="h-8 w-8 bg-brand-tint rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="h-5 w-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-ink-soft font-medium">Delivering to</p>
                        <p class="text-sm font-bold text-ink">{{ scannedOrder?.customer?.name }}</p>
                        <p class="text-xs text-ink-soft">{{ scannedOrder?.delivery_address }}</p>
                    </div>
                </div>

                <!-- Photo capture area -->
                <input type="file" ref="photoInput" id="proofPhoto" accept="image/*" capture="environment" class="hidden" @change="handlePhotoUpload" />

                <div v-if="!photoPreview" class="flex-1 flex flex-col items-center justify-center border-2 border-dashed border-line rounded-card p-8 text-center bg-mist min-h-[220px]">
                    <svg class="h-14 w-14 text-ink-faint mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <p class="text-sm font-semibold text-ink-soft mb-1">No photo yet</p>
                    <p class="text-xs text-ink-faint mb-4">A photo is required to confirm delivery</p>
                    <label for="proofPhoto" class="cursor-pointer bg-brand hover:bg-brand-dark text-white font-bold px-6 py-3 rounded-card text-sm transition">
                        Take Photo
                    </label>
                </div>

                <div v-else class="relative rounded-card overflow-hidden bg-black flex items-center justify-center min-h-[220px]">
                    <div v-if="photoPreview === 'processing'" class="text-white text-center">
                        <svg class="animate-spin h-8 w-8 mx-auto mb-2 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-xs font-semibold">Adding GPS Watermark...</p>
                    </div>
                    <template v-else>
                        <img :src="photoPreview" class="w-full object-cover max-h-64 rounded-card" />
                        <button @click="clearPhoto" class="absolute top-2 right-2 bg-white/90 text-ink rounded-full p-1.5 shadow hover:bg-red-50 hover:text-red-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                        <label for="proofPhoto" class="absolute bottom-2 right-2 bg-white/90 text-ink text-xs font-bold px-3 py-1.5 rounded-full shadow cursor-pointer hover:bg-white transition">
                            Retake
                        </label>
                    </template>
                </div>

                <p v-if="form.errors.photo" class="text-sm text-red-600 text-center">{{ form.errors.photo }}</p>
            </div>

            <div class="p-4 border-t border-line bg-white">
                <button
                    @click="submitDelivery"
                    :disabled="form.processing || !form.photo"
                    class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-3.5 rounded-card transition disabled:opacity-50"
                >
                    <span v-if="form.processing">Submitting...</span>
                    <span v-else>Confirm Delivery</span>
                </button>
            </div>
        </div>

        <!-- Step 4: Success Screen -->
        <div v-else-if="step === 'done'" class="flex flex-col items-center justify-center h-full p-8 text-center space-y-4">
            <div class="h-20 w-20 bg-brand-tint rounded-full flex items-center justify-center">
                <svg class="h-10 w-10 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-2xl font-bold text-ink">
                {{ successMessage }}
            </h3>
            <p class="text-ink-soft text-sm">{{ form.order_number }}</p>
            <Link href="/courier/dashboard" class="mt-4 inline-block bg-brand text-white font-bold px-8 py-3 rounded-card hover:bg-brand-dark transition">
                Back to Dashboard
            </Link>
        </div>
    </CourierLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import CourierLayout from '@/Layouts/CourierLayout.vue';
import { QrcodeStream } from 'vue-qrcode-reader';

// Step state: 'scan' | 'verify' | 'confirm' | 'done'
const step = ref('scan');
const cameraError = ref('');
const barcodeInput = ref(null);
const photoInput = ref(null);
const photoPreview = ref(null);
const sliderValue = ref(0);
const manualOrderNumber = ref('');
const scannedOrder = ref(null);
const lookingUp = ref(false);
const successMessage = ref('');

// Read action from query string
const initialAction = new URLSearchParams(window.location.search).get('action') || 'pickup';
const initialOrder = new URLSearchParams(window.location.search).get('order') || '';

const form = useForm({
    order_number: initialOrder,
    action: initialAction,
    photo: null,
    proof_latitude: null,
    proof_longitude: null,
});

// Do NOT auto-lookup on mount — courier must physically scan the waybill QR.
// The initialOrder is used as a hint shown on the scan screen.
onMounted(() => {
    // intentionally empty — camera opens automatically via QrcodeStream
});

const onCameraScanDetect = (result) => {
    if (result && result.length > 0) {
        const value = result[0].rawValue.trim();
        form.order_number = value;
        cameraError.value = '';
        lookupOrder(value);
    }
};

const onCameraError = (err) => {
    switch (err.name) {
        case 'NotAllowedError':
            cameraError.value = 'Camera access denied. Please allow camera access in your browser settings.';
            break;
        case 'NotFoundError':
            cameraError.value = 'No camera found on this device.';
            break;
        case 'NotSupportedError':
            cameraError.value = 'Camera access requires HTTPS or localhost.';
            break;
        case 'NotReadableError':
            cameraError.value = 'Camera is in use by another application.';
            break;
        default:
            cameraError.value = err.message || 'Camera unavailable.';
    }
};

const paintBoundingBox = (detectedCodes, ctx) => {
    for (const detectedCode of detectedCodes) {
        const { boundingBox: { x, y, width, height } } = detectedCode;
        ctx.lineWidth = 3;
        ctx.strokeStyle = '#2563eb';
        ctx.strokeRect(x, y, width, height);
    }
};

// Manual entry method removed stringently for security.

const lookupOrder = async (orderNumber) => {
    lookingUp.value = true;
    scannedOrder.value = null;
    step.value = 'verify';

    try {
        const response = await window.axios.get(`/courier/lookup-order?order_number=${encodeURIComponent(orderNumber)}`);
        scannedOrder.value = response.data;
    } catch (error) {
        console.error('Order lookup failed:', error);
        scannedOrder.value = null;
    } finally {
        lookingUp.value = false;
    }
};

const resetToScan = () => {
    step.value = 'scan';
    scannedOrder.value = null;
    form.order_number = '';
    manualOrderNumber.value = '';
    sliderValue.value = 0;
    cameraError.value = '';
};

const checkSlider = () => {
    if (sliderValue.value >= 100) {
        submitPickup();
    } else {
        resetSlider();
    }
};

const resetSlider = () => {
    if (sliderValue.value < 100) {
        sliderValue.value = 0;
    }
};

const submitPickup = () => {
    form.post('/courier/scan', {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            successMessage.value = 'Package Picked Up!';
            step.value = 'done';
        },
        onError: () => {
            sliderValue.value = 0;
        }
    });
};

const handlePhotoUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    photoPreview.value = 'processing';
    let lat = null;
    let lng = null;
    
    try {
        // Try GPS with a race against a fallback timeout
        try {
            const position = await Promise.race([
                new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: false, // Switch to false for faster response
                        timeout: 5000,
                        maximumAge: 60000
                    });
                }),
                new Promise((_, reject) => setTimeout(() => reject(new Error('GPS Timeout')), 6000))
            ]);
            lat = position.coords.latitude;
            lng = position.coords.longitude;
        } catch (gpsErr) {
            console.warn("GPS failed, continuing without watermark coordinates:", gpsErr);
        }

        form.proof_latitude = lat;
        form.proof_longitude = lng;
        
        const photoUrl = URL.createObjectURL(file);
        const img = new Image();
        
        await new Promise((resolve, reject) => {
            img.onload = resolve;
            img.onerror = reject;
            img.src = photoUrl;
            // timeout for image load
            setTimeout(() => reject(new Error('Image Load Timeout')), 10000);
        });
        
        const canvas = document.createElement('canvas');
        // Cap resolution for faster processing and lower memory usage
        const MAX_DIM = 1600;
        let width = img.width;
        let height = img.height;
        if (width > height) {
            if (width > MAX_DIM) {
                height *= MAX_DIM / width;
                width = MAX_DIM;
            }
        } else {
            if (height > MAX_DIM) {
                width *= MAX_DIM / height;
                height = MAX_DIM;
            }
        }
        
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);
        
        // Watermark styling
        const fontSize = Math.max(14, height * 0.025); 
        const padding = fontSize;
        const lineCount = 3;
        const boxHeight = (fontSize * lineCount) + (padding * 2) + (fontSize * 0.5 * (lineCount - 1));
        
        ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
        ctx.fillRect(0, height - boxHeight, width, boxHeight);
        
        ctx.fillStyle = 'white';
        ctx.font = `bold ${fontSize}px monospace`;
        
        const dateStr = new Date().toLocaleString() + ' (Local)';
        const locStr = lat ? `GPS: ${lat.toFixed(6)}, ${lng.toFixed(6)}` : 'GPS: Data Unavailable';
        const orderStr = `Order: ${form.order_number}`;
        
        ctx.fillText(orderStr, padding, height - boxHeight + padding + fontSize * 0.8);
        ctx.fillText(locStr, padding, height - boxHeight + padding + (fontSize * 2));
        ctx.fillText(dateStr, padding, height - boxHeight + padding + (fontSize * 3.2));
        
        canvas.toBlob((blob) => {
            if (blob) {
                const newFile = new File([blob], file.name, { type: 'image/jpeg' });
                form.photo = newFile;
                photoPreview.value = URL.createObjectURL(newFile);
            } else {
                throw new Error('Canvas conversion failed');
            }
        }, 'image/jpeg', 0.85);
        
        URL.revokeObjectURL(photoUrl);
        
    } catch (err) {
        console.error("Proof of Delivery Image Error:", err);
        // Final fallback: Use the original file if everything failed
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
        if (err.message !== 'GPS Timeout') {
            alert("Warning: Could not process photo watermark, using original image.");
        }
    }
};

const clearPhoto = () => {
    form.photo = null;
    photoPreview.value = null;
    if (photoInput.value) photoInput.value.value = '';
};

const submitDelivery = () => {
    if (!form.photo) {
        form.setError('photo', 'A delivery photo is required.');
        return;
    }
    form.post('/courier/scan', {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            successMessage.value = 'Delivery Confirmed!';
            step.value = 'done';
        },
        onError: () => {
            // Stay on photo step
        }
    });
};
</script>
