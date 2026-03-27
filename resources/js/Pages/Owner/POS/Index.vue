<template>
    <OwnerLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Point of Sale (POS)</h2>
        </template>

        <div class="py-6 h-[calc(100vh-100px)] flex flex-col md:flex-row gap-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div v-if="form.errors.message || page.props.errors?.message" class="w-full md:col-span-2 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3">
                {{ form.errors.message || page.props.errors?.message }}
            </div>
            <!-- Left Panel: Products List -->
            <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Search by name or scan barcode..." 
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                            @keyup.enter="handleBarcodeScan"
                            ref="searchInput"
                        >
                    </div>
                    <button
                        @click="openScanner"
                        title="Scan Barcode / QR Code"
                        class="flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg text-sm font-semibold transition shrink-0"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        <span class="hidden sm:inline">Scan</span>
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 bg-gray-50/50">
                    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div 
                            v-for="product in filteredProducts" 
                            :key="product.id"
                            @click="addToCart(product)"
                            class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm cursor-pointer hover:border-blue-500 hover:shadow-md transition group select-none relative"
                        >
                            <div class="aspect-square bg-gray-100 rounded-md mb-3 overflow-hidden">
                                <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            </div>
                            <h3 class="font-semibold text-sm text-gray-900 line-clamp-2" :title="product.name">{{ product.name }}</h3>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="font-bold text-blue-600">₱{{ Number(product.base_price).toLocaleString() }}</span>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ product.stock }} in stock</span>
                            </div>
                        </div>
                        <div v-if="filteredProducts.length === 0" class="col-span-full py-12 text-center text-gray-500">
                            No products found.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Cart -->
            <div class="w-full md:w-96 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col overflow-hidden shrink-0">
                <div class="p-4 border-b border-gray-100 bg-gray-900 text-white flex justify-between items-center">
                    <h3 class="font-bold flex items-center gap-2">
                        <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Current Sale
                    </h3>
                    <button @click="clearCart" v-if="cart.length > 0" class="text-xs text-gray-400 hover:text-white transition">Clear All</button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-2">
                    <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center text-gray-400 p-6 text-center space-y-3">
                        <svg class="h-16 w-16 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <p>Cart is empty. Scan a barcode or select products to add.</p>
                    </div>
                    
                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="(item, index) in cart" :key="item.product.id" class="p-3 flex gap-3 hover:bg-gray-50">
                            <div class="flex-1 flex flex-col justify-between">
                                <div class="flex justify-between items-start gap-2">
                                    <h4 class="text-sm font-semibold text-gray-900 line-clamp-2 leading-tight">{{ item.product.name }}</h4>
                                    <button @click="removeFromCart(index)" class="text-red-400 hover:text-red-600 transition p-1 shrink-0"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden shrink-0">
                                        <button @click="updateQuantity(index, item.quantity - 1)" class="w-8 h-8 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 transition">-</button>
                                        <input type="number" v-model.number="item.quantity" class="w-10 h-8 border-none text-center text-xs p-0 focus:ring-0" @change="validateQuantity(index)">
                                        <button @click="updateQuantity(index, item.quantity + 1)" class="w-8 h-8 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 transition">+</button>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs text-gray-500">₱{{ Number(item.product.base_price).toLocaleString() }} x {{ item.quantity }}</div>
                                        <div class="font-bold text-gray-900 text-sm">₱{{ (item.product.base_price * item.quantity).toLocaleString() }}</div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="border-t border-gray-100 p-4 bg-gray-50">
                    <!-- Totals -->
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600 font-medium">Items</span>
                        <span class="font-semibold text-gray-900">{{ totalItems }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-4 text-xl">
                        <span class="font-bold text-gray-900">Total</span>
                        <span class="font-black text-blue-600">₱{{ cartTotal.toLocaleString() }}</span>
                    </div>

                    <!-- ── Payment Method Selector ── -->
                    <div class="mb-3">
                        <label class="block text-xs text-gray-500 font-medium mb-2">PAYMENT METHOD</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="method in paymentMethods"
                                :key="method.value"
                                @click="paymentMethod = method.value"
                                :class="[
                                    'flex flex-col items-center gap-1 py-2.5 px-3 rounded-xl border-2 text-sm font-semibold transition',
                                    paymentMethod === method.value
                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'
                                ]"
                            >
                                <span class="text-lg">{{ method.icon }}</span>
                                <span>{{ method.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- ── Cash Tendered (only for cash) ── -->
                    <div v-if="paymentMethod === 'cash'" class="mb-4">
                        <label class="block text-xs text-gray-500 font-medium mb-1">CASH TENDERED</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold">₱</span>
                            <input 
                                v-model.number="amountPaid" 
                                type="number" 
                                class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 font-bold text-lg text-right shadow-sm"
                                placeholder="0.00"
                            >
                        </div>
                        <div class="flex justify-between items-center mt-2 text-sm">
                            <span class="text-gray-500">Change:</span>
                            <span :class="changeAmount >= 0 ? 'text-green-600 font-bold' : 'text-red-500 font-medium'">₱{{ changeAmount > 0 ? changeAmount.toLocaleString() : '0.00' }}</span>
                        </div>
                    </div>

                    <!-- ── PayMongo note ── -->
                    <div v-else class="mb-4 bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3 text-sm text-indigo-700">
                        <div class="font-semibold mb-0.5">Online Payment via PayMongo</div>
                        <div class="text-xs text-indigo-500">Customer will be redirected to a secure payment page (Card, GCash, or Maya).</div>
                    </div>

                    <!-- ── Complete Sale Button ── -->
                    <button 
                        @click="checkout"
                        :disabled="!canCheckout || form.processing"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-4 px-4 rounded-xl transition shadow-lg flex justify-center items-center gap-2"
                    >
                        <svg v-if="form.processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-else-if="paymentMethod === 'cash'" class="text-lg">Complete Sale</span>
                        <span v-else class="text-lg flex items-center gap-2">Pay Online <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Camera Scanner Modal -->
        <Teleport to="body">
            <div v-if="scannerOpen" class="fixed inset-0 bg-black/80 z-50 flex flex-col items-center justify-center p-4" @click.self="closeScanner">
                <div class="bg-gray-900 rounded-2xl overflow-hidden shadow-2xl w-full max-w-sm">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-700">
                        <div>
                            <h3 class="text-white font-bold text-lg">Scan Barcode / QR</h3>
                            <p class="text-gray-400 text-xs mt-0.5">Point camera at a product barcode</p>
                        </div>
                        <button @click="closeScanner" class="text-gray-400 hover:text-white transition">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="relative bg-black aspect-square">
                        <video ref="videoEl" class="w-full h-full object-cover" autoplay playsinline muted></video>
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-52 h-52 relative">
                                <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-blue-400 rounded-tl-lg"></div>
                                <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-blue-400 rounded-tr-lg"></div>
                                <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-blue-400 rounded-bl-lg"></div>
                                <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-blue-400 rounded-br-lg"></div>
                                <div v-if="scanning" class="absolute top-0 left-0 right-0 h-0.5 bg-blue-400 opacity-75 animate-bounce"></div>
                            </div>
                        </div>
                        <div v-if="cameraError" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900 text-center p-6">
                            <svg class="h-12 w-12 text-red-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M3 8a2 2 0 00-2 2v4a2 2 0 002 2h8a2 2 0 002-2v-4a2 2 0 00-2-2H3z"/></svg>
                            <p class="text-white font-semibold mb-1">Camera Error</p>
                            <p class="text-gray-400 text-sm">{{ cameraError }}</p>
                        </div>
                    </div>
                    <div class="px-5 py-4">
                        <div v-if="lastScanned" class="flex items-center gap-3 bg-green-900/50 border border-green-700 text-green-300 rounded-lg px-4 py-3 mb-3">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm font-semibold">{{ lastScanned }}</span>
                        </div>
                        <div v-else-if="scanning" class="flex items-center gap-2 text-gray-400 text-sm">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Scanning for barcode...
                        </div>
                        <select v-if="cameras.length > 1" v-model="selectedCamera" @change="switchCamera" class="w-full mt-3 bg-gray-800 text-gray-200 text-sm rounded-lg border border-gray-600 px-3 py-2">
                            <option v-for="cam in cameras" :key="cam.deviceId" :value="cam.deviceId">{{ cam.label || `Camera ${cam.deviceId.slice(0,6)}` }}</option>
                        </select>
                        <button @click="closeScanner" class="mt-3 w-full bg-gray-700 hover:bg-gray-600 text-white rounded-lg py-2.5 text-sm font-semibold transition">
                            Done Scanning
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </OwnerLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import { BrowserMultiFormatReader } from '@zxing/browser';

const props = defineProps({
    products: Array
});
const page = usePage();

const searchQuery = ref('');
const searchInput = ref(null);
const cart = ref([]);
const amountPaid = ref('');
const paymentMethod = ref('cash');

const paymentMethods = [
    { value: 'cash',      label: 'Cash',    icon: '' },
    { value: 'paymongo',  label: 'Online',  icon: '' },
];

const form = useForm({
    items: [],
    amount_paid: 0,
    payment_method: 'cash',
});

// --- Barcode Scanner ---
const scannerOpen = ref(false);
const videoEl = ref(null);
const scanning = ref(false);
const lastScanned = ref('');
const cameraError = ref('');
const cameras = ref([]);
const selectedCamera = ref(null);
let codeReader = null;
let scannerControls = null;

const openScanner = async () => {
    scannerOpen.value = true;
    lastScanned.value = '';
    cameraError.value = '';
    scanning.value = false;
    await new Promise(r => setTimeout(r, 150));
    await startScanner();
};

const startScanner = async () => {
    try {
        codeReader = new BrowserMultiFormatReader();
        const devices = await BrowserMultiFormatReader.listVideoInputDevices();
        cameras.value = devices;
        if (devices.length === 0) { cameraError.value = 'No camera found on this device.'; return; }
        const backCam = devices.find(d => /back|rear|environment/i.test(d.label));
        selectedCamera.value = backCam ? backCam.deviceId : (selectedCamera.value || devices[0].deviceId);
        scanning.value = true;
        scannerControls = await codeReader.decodeFromVideoDevice(
            selectedCamera.value, videoEl.value,
            (result) => { if (result) handleScanResult(result.getText()); }
        );
    } catch (e) {
        cameraError.value = e.message || 'Camera access denied. Please allow camera permissions.';
        scanning.value = false;
    }
};

const handleScanResult = (code) => {
    const matched = props.products.find(p => p.barcode === code || p.sku === code);
    if (matched) { addToCart(matched); lastScanned.value = `Added: ${matched.name}`; }
    else          { lastScanned.value = `No product found for: ${code}`; }
    setTimeout(() => { lastScanned.value = ''; }, 2500);
};

const switchCamera = async () => {
    if (scannerControls) scannerControls.stop();
    await startScanner();
};

const closeScanner = () => {
    if (scannerControls) { scannerControls.stop(); scannerControls = null; }
    scanning.value = false;
    scannerOpen.value = false;
    if (searchInput.value) searchInput.value.focus();
};

onBeforeUnmount(() => { closeScanner(); });
onMounted(() => { if (searchInput.value) searchInput.value.focus(); });

// --- Product search ---
const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products;
    const q = searchQuery.value.toLowerCase();
    return props.products.filter(p =>
        p.name.toLowerCase().includes(q) ||
        (p.sku && p.sku.toLowerCase().includes(q)) ||
        (p.barcode && p.barcode.includes(q))
    );
});

const handleBarcodeScan = () => {
    const q = searchQuery.value.trim();
    if (!q) return;
    const matchedProduct = props.products.find(p => p.barcode === q || p.sku === q);
    if (matchedProduct) { addToCart(matchedProduct); searchQuery.value = ''; }
};

// --- Cart logic ---
const addToCart = (product) => {
    const existing = cart.value.find(item => item.product.id === product.id);
    if (existing) {
        if (existing.quantity < product.stock) existing.quantity++;
    } else {
        if (product.stock > 0) cart.value.push({ product, quantity: 1 });
    }
    if (paymentMethod.value === 'cash' && !amountPaid.value) amountPaid.value = cartTotal.value;
};

const removeFromCart = (index) => { cart.value.splice(index, 1); };

const updateQuantity = (index, newQty) => {
    if (newQty < 1) { removeFromCart(index); return; }
    const item = cart.value[index];
    item.quantity = newQty <= item.product.stock ? newQty : item.product.stock;
};

const validateQuantity = (index) => {
    const item = cart.value[index];
    if (item.quantity < 1) item.quantity = 1;
    if (item.quantity > item.product.stock) item.quantity = item.product.stock;
};

const clearCart = () => {
    if (confirm('Are you sure you want to clear the cart?')) {
        cart.value = [];
        amountPaid.value = '';
    }
};

const totalItems  = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0));
const cartTotal   = computed(() => cart.value.reduce((sum, item) => sum + (item.product.base_price * item.quantity), 0));
const changeAmount = computed(() => Number(amountPaid.value) - cartTotal.value);

const canCheckout = computed(() => {
    if (cart.value.length === 0) return false;
    if (paymentMethod.value === 'cash') return Number(amountPaid.value) >= cartTotal.value;
    return true; // paymongo — no cash amount needed
});

const checkout = () => {
    form.items = cart.value.map(item => ({
        product_id: item.product.id,
        quantity:   item.quantity,
    }));
    form.amount_paid     = paymentMethod.value === 'cash' ? amountPaid.value : cartTotal.value;
    form.payment_method  = paymentMethod.value;

    form.post('/owner/pos/checkout', {
        preserveScroll: true,
        onSuccess: () => {
            // Only reset cart for cash — for PayMongo we redirect away
            if (paymentMethod.value === 'cash') {
                cart.value = [];
                amountPaid.value = '';
                if (searchInput.value) searchInput.value.focus();
            }
        },
    });
};
</script>
