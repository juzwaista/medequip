<template>
    <Head title="About Us" />
    <MainLayout>
        <div class="max-w-4xl mx-auto py-10 sm:py-16 px-3 sm:px-4 pb-24 md:pb-16">
            <h1 class="text-2xl sm:text-4xl font-bold text-gray-900 mb-6">About MedEquip</h1>
            <div class="bg-white rounded-2xl shadow-sm p-5 sm:p-8 border border-gray-100 max-w-none text-gray-700 text-sm sm:text-base leading-relaxed">
                <p class="text-lg leading-relaxed mb-6">
                    MedEquip Platform is the premier online marketplace dedicated to bridging the gap between medical professionals, institutions, and verified distributors in Cavite. Our mission is to provide seamless, secure, and reliable access to high-quality medical equipment, surgical instruments, and personal protective equipment.
                </p>
                <h3 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Our Vision</h3>
                <p class="leading-relaxed mb-6">
                    To be the leading digital healthcare supply chain solution in the Philippines, ensuring that every clinic, hospital, and individual has uninterrupted access to life-saving tools and essentials.
                </p>
                <h3 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Why Choose Us?</h3>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Verified Sellers:</strong> Every distributor on our platform undergoes a rigorous business verification process.</li>
                    <li><strong>Secure payments:</strong> Online payments are held by the platform until delivery is confirmed, helping protect buyers and sellers.</li>
                    <li><strong>Rx Support:</strong> Regulated checkout processes for prescription-only medical supplies.</li>
                    <li><strong>Real-time Tracking:</strong> Full transparency from warehouse to your doorstep.</li>
                </ul>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
</script>
