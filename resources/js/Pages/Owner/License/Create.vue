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
                        <div 
                            class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition"
                            :class="{'border-blue-400 bg-blue-50': isScanning}"
                        >
                            <input
                                type="file"
                                @change="handleFileChange"
                                accept=".jpg,.jpeg,.png"
                                required
                                class="w-full"
                            />
                            <p v-if="!isScanning" class="text-xs text-gray-500 mt-2">JPG, PNG (Max 5MB)</p>
                            <div v-else class="mt-2 flex items-center justify-center text-blue-600 font-medium">
                                <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Scanning document details...
                            </div>
                        </div>
                        <p v-if="form.errors.file" class="text-red-500 text-sm mt-1">{{ form.errors.file }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Expiration Date *</label>
                        <input
                            type="date"
                            v-model="form.expires_at"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        <p class="text-xs text-gray-500 mt-2">OCR will attempt to pre-fill this based on your document.</p>
                        <p v-if="form.errors.expires_at" class="text-red-500 text-sm mt-1">{{ form.errors.expires_at }}</p>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing || isScanning"
                            class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg disabled:opacity-50"
                        >
                            {{ form.processing ? 'Uploading...' : 'Upload License' }}
                        </button>
                        <Link
                            href="/owner/distributors"
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
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import { useOCR } from '@/Composables/useOCR';

const props = defineProps({
    distributor: Object,
});

const { scanImage, extractExpirationDate } = useOCR();
const isScanning = ref(false);

const form = useForm({
    type: '',
    file: null,
    expires_at: '',
});

const handleFileChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    form.file = file;

    // Start OCR for images
    if (file.type.match('image.*')) {
        isScanning.value = true;
        try {
            const text = await scanImage(file);
            const date = extractExpirationDate(text);
            if (date) {
                form.expires_at = date;
            }
        } catch (error) {
            console.error('OCR failed:', error);
        } finally {
            isScanning.value = false;
        }
    }
};

const submit = () => {
    form.post(`/owner/distributor/${props.distributor.id}/license/store`, {
        forceFormData: true,
    });
};
</script>
