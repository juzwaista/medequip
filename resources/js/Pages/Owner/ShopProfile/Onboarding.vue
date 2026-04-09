<template>
    <OnboardingLayout title="Set up your shop — MedEquip">
        <div class="w-full max-w-lg">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900">You’re approved — open your shop</h1>
                <p class="text-sm text-gray-600 mt-2 max-w-md mx-auto">
                    A few details so customers can find you. You can add hours, social links, and more later in Business profile.
                </p>
            </div>

            <div v-if="Object.keys(form.errors).length" class="mb-5 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-0.5">
                    <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                </ul>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 sm:p-8 space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-1">Public shop link</label>
                    <div class="flex rounded-xl border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500">
                        <span class="px-3 py-2.5 bg-gray-50 text-gray-500 text-sm border-r border-gray-200 shrink-0">/seller/</span>
                        <input
                            v-model="form.slug"
                            type="text"
                            required
                            autocomplete="off"
                            class="min-w-0 flex-1 px-3 py-2.5 text-sm border-0 focus:ring-0"
                            placeholder="your-shop-name"
                            @input="onSlugInput"
                        />
                    </div>
                    <p v-if="slugHint" class="text-xs mt-1" :class="slugHint.ok ? 'text-emerald-600' : 'text-amber-700'">{{ slugHint.text }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-1">Short description <span class="text-red-500">*</span></label>
                    <textarea
                        v-model="form.description"
                        rows="5"
                        required
                        maxlength="2000"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y min-h-[120px]"
                        placeholder="What do you sell? Who do you serve? Keep it friendly — at least a few sentences."
                    />
                    <p class="text-xs text-gray-500 mt-1">{{ form.description.length }}/2000 · minimum 30 characters</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-1">Shop phone <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input
                        v-model="form.phone"
                        type="tel"
                        inputmode="numeric"
                        maxlength="11"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="09XXXXXXXXX"
                        @input="form.phone = String(form.phone || '').replace(/\D/g, '').slice(0, 11)"
                    />
                    <p class="text-xs text-gray-500 mt-1">Shown on your public shop. You can change this anytime.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-1">Pick-up Instructions <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea
                        v-model="form.pickup_instructions"
                        rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y"
                        placeholder="Directions for customers who pick up orders in person."
                    />
                    <p class="text-xs text-gray-500 mt-1">Provide clear collection details for customers choosing "Store Pick-up."</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-1">Logo <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="file" accept="image/*" class="text-sm w-full" @change="form.logo = $event.target.files[0]" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-1">Cover image <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="file" accept="image/*" class="text-sm w-full" @change="form.cover_photo = $event.target.files[0]" />
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-xl hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <svg v-if="form.processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    {{ form.processing ? 'Saving…' : 'Finish & go to inventory' }}
                </button>
            </form>
        </div>
    </OnboardingLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';

const props = defineProps({
    distributor: { type: Object, required: true },
});

const slugHint = ref(null);
let slugTimer = null;

const form = useForm({
    slug: props.distributor.slug || props.distributor.suggested_slug || '',
    description: props.distributor.description || '',
    phone: props.distributor.phone || '',
    pickup_instructions: '',
    logo: null,
    cover_photo: null,
});

function onSlugInput() {
    slugHint.value = null;
    clearTimeout(slugTimer);
    slugTimer = setTimeout(async () => {
        const raw = String(form.slug || '').trim();
        if (raw.length < 2) {
            slugHint.value = null;
            return;
        }
        try {
            const { data } = await axios.post('/owner/profile/check-slug', { slug: raw });
            slugHint.value = {
                ok: data.available,
                text: data.available ? 'This link is available.' : 'That link is taken — try another.',
            };
            if (data.slug && data.slug !== raw) {
                form.slug = data.slug;
            }
        } catch {
            slugHint.value = null;
        }
    }, 400);
}

watch(
    () => props.distributor,
    (d) => {
        if (d?.slug) form.slug = d.slug;
        if (d?.description) form.description = d.description;
        if (d?.phone) form.phone = d.phone;
    },
    { deep: true }
);

function submit() {
    form.post('/owner/shop/setup', {
        forceFormData: true,
    });
}
</script>
