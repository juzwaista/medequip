<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-ink leading-tight">Global Platform Settings</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Platform Fee Setting -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-control max-w-2xl">
                    <div class="p-6 text-ink border-b border-line">
                        <h3 class="text-lg font-bold mb-4">Financial Settings</h3>
                        <p class="text-sm text-ink-soft mb-6">
                            Adjust the global commission rate that the platform deducts from distributor sales when payouts are released to sellers.
                        </p>
                        
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label for="platform_fee_percent" class="block font-medium text-sm text-ink">
                                    Platform Fee Rate (%)
                                </label>
                                <div class="mt-1 relative rounded-control shadow-sm">
                                    <input
                                        id="platform_fee_percent"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        class="block w-full pr-10 border-line focus:border-brand focus:ring-brand rounded-control shadow-sm"
                                        v-model="form.platform_fee_percent"
                                        required
                                    />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-ink-soft sm:text-sm">%</span>
                                    </div>
                                </div>
                                <p v-if="form.errors.platform_fee_percent" class="text-sm text-red-600 mt-2">
                                    {{ form.errors.platform_fee_percent }}
                                </p>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-ink border border-transparent rounded-control font-semibold text-xs text-white   hover:bg-ink-soft focus:bg-ink-soft active:bg-ink focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 transition ease-in-out duration-150"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="bg-brand-tint p-4 border-t border-brand-soft flex items-start">
                        <svg class="h-6 w-6 text-brand mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs text-brand-dark">
                            <strong>Note:</strong> Changes to the platform fee rate only apply to <em>new payments processed after the change</em>. Existing orders with funds still held by the platform will use the fee rate that was active when they were paid.
                        </p>
                    </div>
                </div>

                <!-- Terms and Conditions Setting -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-control">
                    <div class="p-6 text-ink border-b border-line">
                        <h3 class="text-lg font-bold mb-4">Terms & Conditions</h3>
                        <p class="text-sm text-ink-soft mb-6">
                            This content is displayed in the Terms & Conditions modal for all users. You can use standard HTML formatting (like <code>&lt;strong&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;h3&gt;</code>, etc.) to style the document.
                        </p>
                        
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <textarea
                                    id="terms_and_conditions"
                                    rows="20"
                                    class="block w-full border-line focus:border-brand focus:ring-brand rounded-control shadow-sm font-mono text-xs"
                                    v-model="form.terms_and_conditions"
                                    required
                                ></textarea>
                                <p v-if="form.errors.terms_and_conditions" class="text-sm text-red-600 mt-2">
                                    {{ form.errors.terms_and_conditions }}
                                </p>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-ink border border-transparent rounded-control font-semibold text-xs text-white   hover:bg-ink-soft focus:bg-ink-soft active:bg-ink focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 transition ease-in-out duration-150"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    platformFeePercent: Number,
    termsContent: String,
});

const form = useForm({
    platform_fee_percent: props.platformFeePercent || 5,
    terms_and_conditions: props.termsContent || '',
});

const submit = () => {
    form.post(route('superadmin.settings.update'), {
        preserveScroll: true,
    });
};
</script>
