<template>
    <OwnerLayout>
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Upload License / Accreditation</h1>
                <p class="text-gray-600 mt-2">Add a license or accreditation document to your profile</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">License Type *</label>
                        <select
                            v-model="form.type"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Select type</option>
                            <option value="fda">FDA License</option>
                            <option value="business_permit">Business Permit</option>
                            <option value="phic">PhilHealth Accreditation</option>
                            <option value="doh">DOH Accreditation</option>
                            <option value="other">Other</option>
                        </select>
                        <p v-if="form.errors.type" class="text-red-500 text-sm mt-1">{{ form.errors.type }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Document File *</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition">
                            <input
                                type="file"
                                @change="e => form.file = e.target.files[0]"
                                accept=".pdf,.jpg,.jpeg,.png"
                                required
                                class="w-full"
                            />
                            <p class="text-xs text-gray-500 mt-2">PDF, JPG, PNG (Max 5MB)</p>
                        </div>
                        <p v-if="form.errors.file" class="text-red-500 text-sm mt-1">{{ form.errors.file }}</p>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg disabled:opacity-50"
                        >
                            {{ form.processing ? 'Uploading...' : 'Upload License' }}
                        </button>
                        <Link
                            :href="`/owner/distributors/${distributor.id}/licenses`"
                            class="px-6 py-3 border-2 border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    distributor: Object,
});

const form = useForm({
    type: '',
    file: null,
});

const submit = () => {
    form.post(`/owner/distributors/${props.distributor.id}/licenses`, {
        forceFormData: true,
    });
};
</script>
