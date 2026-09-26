<template>
    <MainLayout>
        <div class="max-w-xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
            <nav class="mb-4 text-sm text-ink-soft" aria-label="Breadcrumb">
                <Link :href="`/orders/${order.order_number}`" class="hover:text-brand hover:underline underline-offset-2">Back to order {{ order.order_number }}</Link>
            </nav>

            <div class="mb-6">
                <h1 class="text-2xl sm:text-[28px] font-semibold tracking-tight text-ink">Upload your prescription</h1>
                <p class="mt-2 max-w-prose leading-relaxed text-ink-soft">
                    Order <span class="font-medium tabular-nums text-ink">{{ order.order_number }}</span> includes medicine that requires a valid prescription.
                    Upload a clear photo of your prescription. Your distributor will review it before you can complete payment.
                </p>
            </div>

            <div class="rounded-card border border-line bg-white p-5 sm:p-6">
                <AlertBanner v-if="$page.props.flash?.success" variant="success" class="mb-5">{{ $page.props.flash.success }}</AlertBanner>

                <form @submit.prevent="submit" class="space-y-5">
                    <TextInput
                        v-model="form.prescription_patient_name"
                        label="Patient full name"
                        type="text"
                        required
                        placeholder="Full name as written on the ID"
                        :error="form.errors.prescription_patient_name"
                    />

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">Patient valid ID photo</label>
                        <input
                            type="file"
                            accept="image/*"
                            required
                            @change="e => form.prescription_id_image = e.target.files[0]"
                            class="block w-full text-sm text-ink-soft file:mr-3 file:cursor-pointer file:rounded-control file:border file:border-line file:bg-white file:px-3 file:py-2 file:text-sm file:font-medium file:text-ink hover:file:bg-mist"
                        />
                        <p class="mt-1 text-sm text-ink-soft">A clear photo of the patient's ID, to match against the prescription.</p>
                        <p v-if="form.errors.prescription_id_image" class="mt-1 text-sm text-danger">{{ form.errors.prescription_id_image }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">Prescription photo</label>
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*"
                            required
                            @change="onFile"
                            class="block w-full text-sm text-ink-soft file:mr-3 file:cursor-pointer file:rounded-control file:border file:border-line file:bg-white file:px-3 file:py-2 file:text-sm file:font-medium file:text-ink hover:file:bg-mist"
                        />
                        <p class="mt-1 text-sm text-ink-soft">JPG or PNG, up to 8 MB. Make sure every detail is readable.</p>
                        <p v-if="form.errors.prescription" class="mt-1 text-sm text-danger">{{ form.errors.prescription }}</p>
                    </div>

                    <div v-if="previewUrl" class="overflow-hidden rounded-card border border-line bg-mist">
                        <img :src="previewUrl" alt="Preview of your prescription" class="max-h-64 w-full object-contain" />
                    </div>

                    <div class="flex flex-col gap-3 pt-2 sm:flex-row">
                        <BaseButton
                            type="submit"
                            :disabled="form.processing || !form.prescription || !form.prescription_patient_name || !form.prescription_id_image"
                            class="flex-1"
                        >
                            {{ form.processing ? 'Uploading…' : 'Submit for review' }}
                        </BaseButton>
                        <BaseButton :href="`/orders/${order.order_number}`" variant="secondary">Back to order</BaseButton>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import BaseButton from '@/Components/ui/BaseButton.vue';
import AlertBanner from '@/Components/ui/AlertBanner.vue';
import TextInput from '@/Components/ui/TextInput.vue';
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
    form.post(`/orders/${props.order.order_number}/prescription`, {
        forceFormData: true,
    });
};
</script>
