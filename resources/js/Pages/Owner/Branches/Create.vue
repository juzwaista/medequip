<template>
    <OwnerLayout>
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Add New Branch</h1>
                <p class="text-gray-600 mt-2">Add a new branch location for {{ distributor.company_name }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Branch Name *</label>
                        <input
                            v-model="form.branch_name"
                            type="text"
                            required
                            placeholder="e.g. Main Branch, Makati Branch"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        <p v-if="form.errors.branch_name" class="text-red-500 text-sm mt-1">{{ form.errors.branch_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Address *</label>
                        <textarea
                            v-model="form.address"
                            rows="3"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        ></textarea>
                        <p v-if="form.errors.address" class="text-red-500 text-sm mt-1">{{ form.errors.address }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Contact Number</label>
                            <input
                                v-model="form.contact_number"
                                @input="sanitizeContactNumber"
                                type="tel"
                                inputmode="numeric"
                                pattern="09[0-9]{9}"
                                maxlength="11"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.contact_number" class="text-red-500 text-sm mt-1">{{ form.errors.contact_number }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Add Branch' }}
                        </button>
                        <Link
                            :href="`/owner/distributor/${distributor.id}/branches`"
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
    branch_name: '',
    address: '',
    contact_number: '',
    email: '',
});

const sanitizeContactNumber = () => {
    form.contact_number = String(form.contact_number || '').replace(/\D/g, '').slice(0, 11);
};

const submit = () => {
    form.post(`/owner/distributor/${props.distributor.id}/branches`);
};
</script>
