<template>
    <MainLayout>
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Upload prescription</h1>
                <p class="text-gray-600 mt-2 text-sm leading-relaxed">
                    Order <span class="font-mono font-semibold">{{ order.order_number }}</span> includes medicine that requires a valid prescription.
                    Please upload a clear photo of your prescription. Your distributor will review it before you can complete payment.
                </p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <div v-if="$page.props.flash?.success" class="mb-4 text-sm text-green-800 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                    {{ $page.props.flash.success }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">Patient Full Name <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.prescription_patient_name"
                            type="text"
                            required
                            placeholder="Full name as written on ID"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.prescription_patient_name" class="text-red-600 text-sm mt-2">{{ form.errors.prescription_patient_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">Patient Valid ID Photo <span class="text-red-500">*</span></label>
                        <input
                            type="file"
                            accept="image/*"
                            required
                            @change="e => form.prescription_id_image = e.target.files[0]"
                            class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-semibold file:bg-blue-50 file:text-blue-700"
                        />
                        <p class="text-xs text-gray-500 mt-1">Provide a clear photo of the patient's ID to match with the prescription.</p>
                        <p v-if="form.errors.prescription_id_image" class="text-red-600 text-sm mt-2">{{ form.errors.prescription_id_image }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">Prescription photo <span class="text-red-500">*</span></label>
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*"
                            required
                            @change="onFile"
                            class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-semibold file:bg-blue-50 file:text-blue-700"
                        />
                        <p class="text-xs text-gray-500 mt-1">JPG or PNG, max 8MB. Ensure all details are readable.</p>
                        <p v-if="form.errors.prescription" class="text-red-600 text-sm mt-2">{{ form.errors.prescription }}</p>
                    </div>

                    <div v-if="previewUrl" class="rounded-xl border border-gray-200 overflow-hidden bg-gray-50">
                        <img :src="previewUrl" alt="Preview" class="w-full max-h-64 object-contain" />
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing || !form.prescription || !form.prescription_patient_name || !form.prescription_id_image"
                            class="flex-1 bg-blue-600 text-white px-4 py-3 rounded-xl font-semibold hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Uploading…' : 'Submit for review' }}
                        </button>
                        <Link
                            :href="`/orders/${order.id}`"
                            class="px-4 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 text-center"
                        >
                            Back to order
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    order: Object,
});

const previewUrl = ref('');

const form = useForm({
    prescription: null,
    prescription_patient_name: '',
    prescription_id_image: null,
});

const onFile = (e) => {
    const file = e.target.files?.[0];
    form.prescription = file || null;
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = file ? URL.createObjectURL(file) : '';
};

const submit = () => {
    form.post(`/orders/${props.order.id}/prescription`, {
        forceFormData: true,
    });
};
</script>
