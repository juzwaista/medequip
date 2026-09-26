<template>
    <Head title="Help center · MedEquip" />
    <MainLayout>
        <div class="max-w-3xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 pb-24 md:pb-16">
            <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-ink">Help center</h1>
            <p class="mt-3 text-ink-soft">Answers to common questions about buying and selling on MedEquip.</p>

            <div class="mt-8 divide-y divide-line overflow-hidden rounded-card border border-line bg-white">
                <details v-for="(faq, i) in faqs" :key="faq.q" class="group" :open="i === 0">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-semibold text-ink hover:bg-mist focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand sm:px-6 [&::-webkit-details-marker]:hidden">
                        {{ faq.q }}
                        <svg class="h-5 w-5 flex-none text-ink-faint transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="max-w-[65ch] px-5 pb-5 leading-relaxed text-ink-soft sm:px-6">{{ faq.a }}</p>
                </details>
            </div>

            <div class="mt-8 flex flex-col items-start gap-3 rounded-card border border-line bg-white p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div>
                    <p class="font-semibold text-ink">Didn't find your answer?</p>
                    <p class="text-ink-soft">Send us a message and we'll get back to you.</p>
                </div>
                <BaseButton href="/contact" variant="secondary">Contact us</BaseButton>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import BaseButton from '@/Components/ui/BaseButton.vue';
import { Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const faqs = [
    {
        q: 'How do I place an order?',
        a: 'Browse our product catalog, add items to your cart, and proceed to checkout. You will need a verified account and a delivery address to complete your purchase. For prescription-only products, you will be prompted to upload a valid medical prescription before payment.',
    },
    {
        q: 'I am a distributor. How do I start selling?',
        a: 'Register as a Distributor. After you verify your email, you will be redirected to the setup wizard where you must provide your DTI/SEC registration, business address, and an authorization letter. Once our admins approve your application, your dashboard will be unlocked.',
    },
    {
        q: 'How is my payment protected?',
        a: 'When you pay via GCash, Maya, or card, the platform holds your payment until you mark the order as received. After that, funds are released to the seller according to our payout rules.',
    },
];
</script>
