<template>
    <Head title="Suppliers" />

    <OwnerLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-xl font-bold text-gray-900">Manage Suppliers</h2>
                <button
                    @click="openModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors"
                >
                    Add Supplier
                </button>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Phone</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="supplier in suppliers.data" :key="supplier.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ supplier.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ supplier.contact_person || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ supplier.email || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ supplier.phone || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="openModal(supplier)" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button @click="deleteSupplier(supplier)" class="text-rose-600 hover:text-rose-900">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="suppliers.data.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">
                                No suppliers found. Click "Add Supplier" to create one.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="suppliers?.data?.length > 0" class="mt-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 px-6 py-4 border-t border-gray-100">
                <div class="text-sm text-gray-600 text-center sm:text-left">
                    Showing {{ suppliers.from }} to {{ suppliers.to }} of {{ suppliers.total }} suppliers
                </div>
                <div class="flex flex-wrap justify-center sm:justify-end gap-2">
                    <template v-for="link in suppliers.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="{
                                'bg-blue-600 text-white border-blue-600': link.active,
                                'bg-white text-gray-700 hover:bg-gray-50 border-gray-300': !link.active,
                            }"
                            class="px-3 py-2.5 sm:py-2 border rounded-lg text-sm font-medium min-w-[40px] text-center touch-manipulation inline-flex items-center justify-center"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-2.5 sm:py-2 border border-gray-200 rounded-lg text-sm font-medium min-w-[40px] text-center opacity-50 cursor-not-allowed bg-gray-50 text-gray-400 inline-flex items-center justify-center"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden relative">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ editingSupplier ? 'Edit Supplier' : 'Add New Supplier' }}</h3>
                
                <form @submit.prevent="submitForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Company / Factory Name <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <div v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contact Person</label>
                                <input v-model="form.contact_person" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <div v-if="form.errors.contact_person" class="mt-1 text-xs text-rose-600">{{ form.errors.contact_person }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <div v-if="form.errors.phone" class="mt-1 text-xs text-rose-600">{{ form.errors.phone }}</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email Address (For Automated POs)</label>
                            <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">If provided, Purchase Orders will be sent here automatically.</p>
                            <div v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea v-model="form.address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            <div v-if="form.errors.address" class="mt-1 text-xs text-rose-600">{{ form.errors.address }}</div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Internal Notes</label>
                            <textarea v-model="form.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            <div v-if="form.errors.notes" class="mt-1 text-xs text-rose-600">{{ form.errors.notes }}</div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 text-sm font-medium">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-white hover:bg-blue-700 text-sm font-medium disabled:opacity-50">
                            {{ editingSupplier ? 'Update Supplier' : 'Save Supplier' }}
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    suppliers: Object,
});

const showModal = ref(false);
const editingSupplier = ref(null);

const form = useForm({
    name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    notes: '',
});

function openModal(supplier = null) {
    if (supplier) {
        editingSupplier.value = supplier;
        form.name = supplier.name;
        form.contact_person = supplier.contact_person || '';
        form.email = supplier.email || '';
        form.phone = supplier.phone || '';
        form.address = supplier.address || '';
        form.notes = supplier.notes || '';
    } else {
        editingSupplier.value = null;
        form.reset();
    }
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (editingSupplier.value) {
        form.put(route('owner.suppliers.update', editingSupplier.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('owner.suppliers.store'), {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteSupplier(supplier) {
    if (confirm(`Are you sure you want to delete ${supplier.name}?`)) {
        router.delete(route('owner.suppliers.destroy', supplier.id));
    }
}
</script>
