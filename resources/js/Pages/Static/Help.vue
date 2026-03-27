<template>
    <Head title="Help Center" />
    <MainLayout>
        <div class="max-w-4xl mx-auto py-16 px-4">
            <h1 class="text-4xl font-bold text-gray-900 mb-6">Help Center / FAQ</h1>
            
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">How do I place an order?</h3>
                    <p class="text-gray-700 leading-relaxed">Browse our product catalog, add items to your cart, and proceed to checkout. You will need a verified account and a delivery address to complete your purchase. For prescription-only products, you will be prompted to upload a valid medical prescription before payment.</p>
                </div>
                
                <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">How does the wallet system work?</h3>
                    <p class="text-gray-700 leading-relaxed">The MedEquip Wallet allows you to pre-load funds for seamless checkout. Distributors also receive their sales earnings straight into their wallets upon order fulfillment, which can then be withdrawn to their bank or e-wallets.</p>
                </div>
                
                <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">I'm a distributor. How do I start selling?</h3>
                    <p class="text-gray-700 leading-relaxed">Register as a Distributor. After you verify your email, you will be redirected to the setup wizard where you must provide your DTI/SEC registration, business address, and an authorization letter. Once our admins approve your application, your dashboard will be unlocked.</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">How is my payment protected?</h3>
                    <p class="text-gray-700 leading-relaxed">We utilize a secure escrow system. When you pay via GCash, Maya, or Card, the platform securely holds the money. Only after you mark the order as "Received" in your portal will the funds be released to the seller's wallet.</p>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
</script>
