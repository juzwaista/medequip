<template>
    <MainLayout>
        <Head title="B2B & Procurement Guide - MedEquip" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Header Section -->
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h1 class="text-4xl sm:text-5xl font-semibold text-transparent bg-clip-text bg-gradient-to-r from-brand-dark to-brand mb-6 tracking-tight">
                    MedEquip B2B Guide
                </h1>
                <p class="text-lg text-ink-soft leading-relaxed">
                    Everything you need to know about navigating wholesale procurement, managing purchase orders, and setting up your distributor shop.
                </p>
            </div>

            <!-- Role Selector Tabs -->
            <div class="flex justify-center mb-12">
                <div class="inline-flex p-1.5 bg-mist/80  rounded-card shadow-inner border border-line">
                    <button 
                        @click="activeTab = 'buyer'"
                        :class="[
                            'px-8 py-3 rounded-card font-bold text-sm transition-all duration-300',
                            activeTab === 'buyer' ? 'bg-white text-brand-dark shadow-md ring-1 ring-ink-faint/5' : 'text-ink-soft hover:text-ink'
                        ]"
                    >
                        I am a Corporate Buyer
                    </button>
                    <button 
                        @click="activeTab = 'distributor'"
                        :class="[
                            'px-8 py-3 rounded-card font-bold text-sm transition-all duration-300',
                            activeTab === 'distributor' ? 'bg-white text-brand-dark shadow-md ring-1 ring-ink-faint/5' : 'text-ink-soft hover:text-ink'
                        ]"
                    >
                        I am a Distributor
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="max-w-4xl mx-auto">
                
                <!-- BUYER GUIDE -->
                <div v-show="activeTab === 'buyer'" class="space-y-12 animate-fade-in-up">
                    <div class="bg-white rounded-card p-8 sm:p-10 shadow-xl shadow-blue-900/5 border border-brand-soft relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-brand-tint rounded-full blur-3xl -mr-20 -mt-20 transition duration-700 group-hover:bg-brand-tint"></div>
                        <div class="relative z-10">
                            <h2 class="text-2xl font-bold text-ink mb-4 flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-brand-tint text-brand-dark text-sm">1</span>
                                Applying for a Corporate Account
                            </h2>
                            <p class="text-ink-soft mb-4">To access wholesale pricing and utilize Purchase Orders, you must upgrade your standard account to a Corporate Account.</p>
                            <ul class="space-y-3 text-sm text-ink-soft list-disc pl-5">
                                <li>Navigate to the <Link href="/business-account/apply" class="text-brand font-semibold hover:underline">Apply for Corporate Account</Link> page.</li>
                                <li>Provide your company name, TIN, and upload a valid business document (e.g. SEC Registration, Mayor's Permit).</li>
                                <li>Wait for a platform administrator to approve your application (usually within 24 hours).</li>
                            </ul>
                        </div>
                    </div>

                    <div class="bg-white rounded-card p-8 sm:p-10 shadow-xl shadow-blue-900/5 border border-brand-soft relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-brand-tint rounded-full blur-3xl -mr-20 -mt-20 transition duration-700 group-hover:bg-brand-tint"></div>
                        <div class="relative z-10">
                            <h2 class="text-2xl font-bold text-ink mb-4 flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-brand-tint text-brand-dark text-sm">2</span>
                                Finding Wholesale Products
                            </h2>
                            <p class="text-ink-soft mb-4">Once approved, you will see a <strong>"Wholesale Pricing Available"</strong> badge on eligible products.</p>
                            <div class="bg-brand-tint p-4 rounded-card border border-brand-soft">
                                <p class="text-sm text-brand-dark">
                                    <strong class="block mb-1">Volume Discounts</strong>
                                    To unlock the discounted price, you must add the <strong>Minimum Wholesale Quantity</strong> to your cart. The discount is applied automatically at checkout!
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-card p-8 sm:p-10 shadow-xl shadow-blue-900/5 border border-brand-soft relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-brand-tint rounded-full blur-3xl -mr-20 -mt-20 transition duration-700 group-hover:bg-brand-tint"></div>
                        <div class="relative z-10">
                            <h2 class="text-2xl font-bold text-ink mb-4 flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-brand-tint text-brand-dark text-sm">3</span>
                                Paying via Purchase Order
                            </h2>
                            <p class="text-ink-soft mb-4">Corporate buyers can bypass upfront payments and check out using their company's Purchase Order document (Net-30 or agreed terms).</p>
                            <ul class="space-y-3 text-sm text-ink-soft list-disc pl-5">
                                <li>At checkout, select <strong>Purchase Order (Net-30)</strong> under Payment Methods.</li>
                                <li>Upload a clear PDF or image of your official PO document. You can also save it for future use.</li>
                                <li>Place your order. The seller will review the PO and, once approved, will fulfill the order. Payment is settled directly with the seller outside the platform.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- DISTRIBUTOR GUIDE -->
                <div v-show="activeTab === 'distributor'" class="space-y-12 animate-fade-in-up">
                    <div class="bg-white rounded-card p-8 sm:p-10 shadow-xl shadow-indigo-900/5 border border-brand-soft relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-brand-tint rounded-full blur-3xl -mr-20 -mt-20 transition duration-700 group-hover:bg-brand-tint"></div>
                        <div class="relative z-10">
                            <h2 class="text-2xl font-bold text-ink mb-4 flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-brand-tint text-brand-dark text-sm">1</span>
                                Setting up Wholesale Pricing
                            </h2>
                            <p class="text-ink-soft mb-4">Attract corporate buyers by offering bulk discounts. When adding or editing a product in your Inventory:</p>
                            <ul class="space-y-3 text-sm text-ink-soft list-disc pl-5">
                                <li>Enter a <strong>Wholesale Price</strong> (must be lower than retail).</li>
                                <li>Set a <strong>Minimum Wholesale Quantity</strong>. Buyers must order at least this many units to receive the discounted rate.</li>
                                <li>The system will automatically apply the discount when a verified corporate buyer checks out with the required quantity.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="bg-white rounded-card p-8 sm:p-10 shadow-xl shadow-indigo-900/5 border border-brand-soft relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-brand-tint rounded-full blur-3xl -mr-20 -mt-20 transition duration-700 group-hover:bg-brand-tint"></div>
                        <div class="relative z-10">
                            <h2 class="text-2xl font-bold text-ink mb-4 flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-brand-tint text-brand-dark text-sm">2</span>
                                Receiving a Purchase Order
                            </h2>
                            <p class="text-ink-soft mb-4">When a buyer pays using a PO, the order will appear in your Orders dashboard with a <strong>Pending PO Verification</strong> badge.</p>
                            <div class="bg-brand-tint p-4 rounded-card border border-brand-soft">
                                <p class="text-sm text-brand-dark">
                                    <strong class="block mb-1">Your Responsibility</strong>
                                    You must click "View PO Document" to verify their terms (e.g. Net-30). If acceptable, click <strong>Approve PO</strong>. By approving, you agree to fulfill the order and collect payment from the buyer directly.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-card p-8 sm:p-10 shadow-xl shadow-indigo-900/5 border border-brand-soft relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-brand-tint rounded-full blur-3xl -mr-20 -mt-20 transition duration-700 group-hover:bg-brand-tint"></div>
                        <div class="relative z-10">
                            <h2 class="text-2xl font-bold text-ink mb-4 flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-brand-tint text-brand-dark text-sm">3</span>
                                Order Fulfillment
                            </h2>
                            <p class="text-ink-soft mb-4">Once you approve a PO, the order transitions to <strong>Approved</strong>. From there, fulfillment is identical to standard retail orders:</p>
                            <ul class="space-y-3 text-sm text-ink-soft list-disc pl-5">
                                <li>Pack the items and upload packaging evidence.</li>
                                <li>Update the status to <strong>Packed</strong>.</li>
                                <li>Hand over to the courier or wait for the buyer to pick it up.</li>
                                <li>Inventory stock is automatically deducted upon PO approval!</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const activeTab = ref('buyer');
</script>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.4s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
