<template>
    <div class="receipt-container">
        <!-- Minimalistic thermal POS receipt -->
        <div class="text-center mb-4">
            <h1 class="font-bold text-xl">{{ order.distributor?.company_name }}</h1>
            <p class="text-sm text-gray-600">{{ order.distributor?.address || 'Cavite, Philippines' }}</p>
            <p class="text-xs text-gray-500 mt-2">Order: {{ order.order_number }}</p>
            <p class="text-xs text-gray-500">Date: {{ new Date(order.created_at).toLocaleString() }}</p>
        </div>

        <div class="border-t border-dashed border-gray-400 my-4"></div>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-300">
                    <th class="text-left font-semibold py-1">Item</th>
                    <th class="text-right font-semibold py-1">Qty</th>
                    <th class="text-right font-semibold py-1">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in order.items" :key="item.id">
                    <td class="py-1 pr-2 line-clamp-2 leading-tight">{{ item.product?.name }}</td>
                    <td class="text-right py-1 align-top">{{ item.quantity }}</td>
                    <td class="text-right py-1 align-top">₱{{ Number(item.total_price).toLocaleString() }}</td>
                </tr>
            </tbody>
        </table>

        <div class="border-t border-dashed border-gray-400 my-4"></div>

        <div class="flex justify-between text-sm mb-1 text-gray-600">
            <span>Subtotal</span>
            <span>₱{{ Number(order.subtotal).toLocaleString() }}</span>
        </div>
        <div class="flex justify-between font-bold text-lg mb-2">
            <span>Total</span>
            <span>₱{{ Number(order.total_amount).toLocaleString() }}</span>
        </div>
        <div class="flex justify-between text-sm text-gray-600">
            <span>Payment Method</span>
            <span class="uppercase">{{ order.payment_method }}</span>
        </div>

        <div class="border-t border-dashed border-gray-400 my-4 inline-block w-full"></div>
        <div class="text-center text-xs text-gray-500 pb-8 cursor-pointer no-print" @click="handlePrint">
            <p class="font-bold text-blue-600">Tap here to print again</p>
        </div>
        <div class="text-center text-xs text-gray-500">
            <p>Thank you for your purchase!</p>
            <p>Please come again.</p>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';

const props = defineProps({
    order: Object
});

const handlePrint = () => {
    window.print();
};

onMounted(() => {
    // Automatically open print dialog when receipt is loaded
    setTimeout(() => {
        handlePrint();
    }, 500); // slight delay to allow rendering
});
</script>

<style>
/* Remove standard inertia styling/backgrounds for this page */
html, body {
    background: white !important;
    color: black !important;
    margin: 0 !important;
    padding: 0 !important;
    min-height: 100vh;
}

#app {
    background: white;
}

.receipt-container {
    width: 100%;
    max-width: 80mm; /* standard thermal receipt width */
    margin: 0 auto;
    padding: 10mm 5mm;
    font-family: 'Courier New', Courier, monospace; /* generic monospace for thermal look */
    color: #000;
}

@media print {
    body, html, #app {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .receipt-container {
        padding: 0;
        margin: 0;
    }
    .no-print {
        display: none !important;
    }
    @page { 
        margin: 0; 
        size: 80mm auto; /* roll paper length */
    }
}
</style>
