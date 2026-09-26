<template>
    <div class="min-h-screen min-h-dvh flex min-w-0 bg-mist">
        <!-- Brand panel (large screens). Decorative: the page title lives in the form card. -->
        <aside class="relative hidden lg:flex lg:w-[42%] xl:w-[40%] flex-col justify-between overflow-hidden bg-ink p-12 text-white">
            <BrandMark :size="720" gap="#12262B" class="pointer-events-none absolute -bottom-56 -right-64 text-brand opacity-30" />

            <Link href="/products" class="relative z-10 inline-flex w-fit rounded-control focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white" aria-label="MedEquip home">
                <BrandLogo light :size="38" />
            </Link>

            <div class="relative z-10 max-w-md">
                <p class="text-4xl xl:text-[42px] font-semibold leading-[1.1] tracking-tight">{{ headline }}</p>
                <p class="mt-4 text-lg leading-relaxed text-white/70">{{ blurb }}</p>

                <dl v-if="points.length" class="mt-10 divide-y divide-white/10 border-y border-white/10">
                    <div v-for="point in points" :key="point.title" class="py-4">
                        <dt class="font-semibold">{{ point.title }}</dt>
                        <dd class="mt-0.5 text-white/60">{{ point.text }}</dd>
                    </div>
                </dl>
            </div>

            <p class="relative z-10 text-sm text-white/50">&copy; 2026 MedEquip Platform. Cavite, Philippines.</p>
        </aside>

        <main class="flex min-w-0 flex-1 flex-col">
            <div class="px-4 pt-6 sm:px-6 lg:hidden">
                <Link href="/products" class="inline-flex rounded-control" aria-label="MedEquip home">
                    <BrandLogo :size="34" />
                </Link>
            </div>

            <div class="flex flex-1 items-center justify-center px-4 py-8 sm:px-6 sm:py-12">
                <div class="w-full" :class="wide ? 'max-w-2xl' : 'max-w-md'">
                    <div v-if="bare"><slot /></div>
                    <div v-else class="rounded-card border border-line bg-white p-6 sm:p-8">
                        <slot />
                    </div>
                    <slot name="below" />
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import BrandMark from '@/Components/ui/BrandMark.vue';
import BrandLogo from '@/Components/ui/BrandLogo.vue';

defineProps({
    headline: { type: String, default: 'Stock your clinic from licensed distributors.' },
    blurb: { type: String, default: 'Medical equipment and pharmaceuticals from sellers in Cavite, all in one marketplace.' },
    // Plain statements of how the platform works today. Pass `:points="[]"` on pages where the
    // visitor already has an account and doesn't need the pitch (e.g. sign in).
    points: {
        type: Array,
        default: () => [
            { title: 'Sellers are reviewed', text: 'Distributors submit their FDA and business documents, and our team approves them before they can list.' },
            { title: 'Payment held until you confirm', text: 'For online payments, your money stays with the platform until you confirm you received your order.' },
            { title: 'Follow every order', text: 'See each order move from packing to courier pickup to delivery.' },
        ],
    },
    // Wider column for longer forms (e.g. registration).
    wide: { type: Boolean, default: false },
    // Skip the white card and render the slot directly on the page.
    bare: { type: Boolean, default: false },
});
</script>
