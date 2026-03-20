<template>
    <!-- Trigger button (slot or default) -->
    <button
        type="button"
        @click="open"
        class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg text-sm font-semibold transition shrink-0"
        title="Scan Barcode"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
        </svg>
        Scan
    </button>

    <!-- Modal -->
    <Teleport to="body">
        <div v-if="isOpen" class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4" @click.self="close">
            <div class="bg-gray-900 rounded-2xl overflow-hidden shadow-2xl w-full max-w-sm">
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-700">
                    <div>
                        <h3 class="text-white font-bold text-lg">Scan Product Barcode</h3>
                        <p class="text-gray-400 text-xs mt-0.5">Point camera at the product barcode</p>
                    </div>
                    <button @click="close" class="text-gray-400 hover:text-white transition">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Camera -->
                <div class="relative bg-black aspect-square">
                    <video ref="videoEl" class="w-full h-full object-cover" autoplay playsinline muted></video>

                    <!-- Reticle -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-52 h-52 relative">
                            <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-blue-400 rounded-tl-lg"></div>
                            <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-blue-400 rounded-tr-lg"></div>
                            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-blue-400 rounded-bl-lg"></div>
                            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-blue-400 rounded-br-lg"></div>
                            <div v-if="scanning" class="absolute top-0 left-0 right-0 h-0.5 bg-blue-400 opacity-75 animate-bounce"></div>
                        </div>
                    </div>

                    <!-- Camera error overlay -->
                    <div v-if="cameraError" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900 text-center p-6">
                        <svg class="h-10 w-10 text-red-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M3 8a2 2 0 00-2 2v4a2 2 0 002 2h8a2 2 0 002-2v-4a2 2 0 00-2-2H3z"/>
                        </svg>
                        <p class="text-white font-semibold mb-1">Camera unavailable</p>
                        <p class="text-gray-400 text-sm">{{ cameraError }}</p>
                    </div>
                </div>

                <!-- Status / Result -->
                <div class="px-5 py-4 space-y-3">
                    <!-- Success -->
                    <div v-if="scannedCode" class="flex items-center gap-3 bg-green-900/50 border border-green-700 text-green-300 rounded-lg px-4 py-3">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <p class="text-xs text-green-400 font-semibold uppercase tracking-wide">Barcode Detected</p>
                            <p class="font-mono font-bold text-sm">{{ scannedCode }}</p>
                        </div>
                    </div>

                    <!-- Scanning spinner -->
                    <div v-else-if="scanning" class="flex items-center gap-2 text-gray-400 text-sm">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Scanning...
                    </div>

                    <!-- Camera switcher -->
                    <select v-if="cameras.length > 1" v-model="selectedCamera" @change="switchCamera"
                        class="w-full bg-gray-800 text-gray-200 text-sm rounded-lg border border-gray-600 px-3 py-2">
                        <option v-for="cam in cameras" :key="cam.deviceId" :value="cam.deviceId">
                            {{ cam.label || `Camera ${cam.deviceId.slice(0, 6)}` }}
                        </option>
                    </select>

                    <!-- Use / Cancel buttons -->
                    <div class="flex gap-2">
                        <button v-if="scannedCode" @click="confirmScan" type="button"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2.5 text-sm font-bold transition">
                            Use This Barcode
                        </button>
                        <button @click="close" type="button"
                            class="flex-1 bg-gray-700 hover:bg-gray-600 text-white rounded-lg py-2.5 text-sm font-semibold transition">
                            {{ scannedCode ? 'Cancel' : 'Close' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue';
import { BrowserMultiFormatReader } from '@zxing/browser';

const emit = defineEmits(['scanned']);

const isOpen    = ref(false);
const scanning  = ref(false);
const scannedCode = ref('');
const cameraError = ref('');
const cameras   = ref([]);
const selectedCamera = ref(null);
const videoEl   = ref(null);
let codeReader   = null;
let controls     = null;

const open = async () => {
    isOpen.value    = true;
    scannedCode.value = '';
    cameraError.value = '';
    scanning.value  = false;
    // Wait for DOM to render the video element
    await new Promise(r => setTimeout(r, 150));
    await startCamera();
};

const startCamera = async () => {
    try {
        codeReader = new BrowserMultiFormatReader();
        const devices = await BrowserMultiFormatReader.listVideoInputDevices();
        cameras.value = devices;

        if (!devices.length) {
            cameraError.value = 'No camera found on this device.';
            return;
        }

        const backCam = devices.find(d => /back|rear|environment/i.test(d.label));
        selectedCamera.value = backCam?.deviceId ?? (selectedCamera.value ?? devices[0].deviceId);
        scanning.value = true;

        controls = await codeReader.decodeFromVideoDevice(
            selectedCamera.value,
            videoEl.value,
            (result) => {
                if (result && !scannedCode.value) {
                    scannedCode.value = result.getText();
                    scanning.value = false;
                }
            }
        );
    } catch (e) {
        cameraError.value = e.message || 'Camera access denied.';
        scanning.value = false;
    }
};

const switchCamera = async () => {
    controls?.stop();
    await startCamera();
};

const confirmScan = () => {
    emit('scanned', scannedCode.value);
    close();
};

const close = () => {
    controls?.stop();
    controls = null;
    scanning.value = false;
    isOpen.value = false;
    scannedCode.value = '';
};

onBeforeUnmount(close);
</script>
