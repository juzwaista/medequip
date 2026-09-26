<template>
    <Head title="About us · MedEquip" />
    <MainLayout>
        <div class="max-w-4xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 pb-24 md:pb-16">
            <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-ink">About MedEquip</h1>
            <p class="mt-5 max-w-[60ch] text-lg leading-relaxed text-ink-soft">
                MedEquip is an online marketplace that connects medical professionals, institutions and individual buyers with verified distributors in Cavite. We make it simpler to find, buy and receive medical equipment, surgical instruments and personal protective equipment.
            </p>

            <section class="mt-12" aria-labelledby="count-on">
                <h2 id="count-on" class="text-xl font-semibold tracking-tight text-ink">What you can count on</h2>
                <dl class="mt-5 grid gap-px overflow-hidden rounded-card border border-line bg-line sm:grid-cols-2">
                    <div v-for="point in points" :key="point.title" class="bg-white p-5 sm:p-6">
                        <dt class="font-semibold text-ink">{{ point.title }}</dt>
                        <dd class="mt-1 leading-relaxed text-ink-soft">{{ point.text }}</dd>
                    </div>
                </dl>
            </section>

            <section class="mt-12 rounded-card bg-ink p-6 text-white sm:p-8" aria-labelledby="vision">
                <h2 id="vision" class="text-xl font-semibold tracking-tight">Our vision</h2>
                <p class="mt-2 max-w-[60ch] leading-relaxed text-white/75">
                    To be the leading digital healthcare supply chain solution in the Philippines, so that every clinic, hospital and individual has uninterrupted access to the tools and essentials they need.
                </p>
            </section>

            <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                <BaseButton href="/products">Browse products</BaseButton>
                <BaseButton href="/register" variant="secondary">Start selling as a distributor</BaseButton>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import BaseButton from '@/Components/ui/BaseButton.vue';
import { Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const points = [
    { title: 'Verified sellers', text: 'Every distributor completes a business verification before they can sell on the platform.' },
    { title: 'Secure payments', text: 'Online payments are held by the platform until delivery is confirmed, which helps protect buyers and sellers.' },
    { title: 'Prescription support', text: 'A regulated checkout process for prescription-only medical supplies.' },
    { title: 'Order tracking', text: 'Follow each order from the warehouse to your doorstep.' },
];
</script>
