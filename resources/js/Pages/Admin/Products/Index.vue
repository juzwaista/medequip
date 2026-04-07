<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Product listings</h1>
                    <p class="text-gray-500 mt-1">Moderate catalog listings: hide from buyers or soft-delete serious cases.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-72">
                        <input
                            v-model="searchInput"
                            type="text"
                            placeholder="Search name, SKU, or ID…"
                            class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            @keydown.enter="applySearch"
                        />
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <button
                        type="button"
                        class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm font-semibold hover:bg-slate-900"
                        @click="applySearch"
                    >
                        Search
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 mb-6">
                <button
                    v-for="opt in filterOptions"
                    :key="opt.value"
                    type="button"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition"
                    :class="filters.filter === opt.value
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                    @click="setFilter(opt.value)"
                >
                    {{ opt.label }}
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm min-w-[720px]">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">Shop</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="p in products.data" :key="p.id" class="hover:bg-gray-50/80">
                                <td class="px-4 py-3 text-gray-500 tabular-nums">{{ p.id }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900">{{ p.name }}</p>
                                    <p class="text-xs text-gray-500">{{ p.category?.name || '—' }} · ₱{{ Number(p.base_price).toLocaleString() }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="p.distributor?.slug" class="text-blue-600">{{ p.distributor.company_name }}</span>
                                    <span v-else class="text-gray-700">{{ p.distributor?.company_name || '—' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="p.deleted_at"
                                        class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-100 text-rose-800"
                                    >Removed</span>
                                    <span
                                        v-else-if="!p.is_active"
                                        class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 text-amber-900"
                                    >Inactive</span>
                                    <span
                                        v-else
                                        class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800"
                                    >Active</span>
                                </td>
                                <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                    <a
                                        :href="`/products/${p.id}`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-xs font-semibold text-blue-600 hover:text-blue-800"
                                    >View</a>
                                    <template v-if="!p.deleted_at">
                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-amber-800 hover:text-amber-950"
                                            :disabled="!p.is_active"
                                            @click="deactivate(p)"
                                        >
                                            Hide
                                        </button>
                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-rose-700 hover:text-rose-900"
                                            @click="softDelete(p)"
                                        >
                                            Remove
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="products.links?.length > 3" class="mt-8 flex justify-center gap-2 flex-wrap">
                <Link
                    v-for="link in products.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="px-3 py-1 rounded-lg text-sm border"
                    :class="[
                        link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 text-gray-700 hover:bg-gray-50',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                    preserve-scroll
                    v-html="link.label"
                />
            </div>

            <p v-if="!products.data?.length" class="text-center py-12 text-gray-500 text-sm">No products match these filters.</p>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    products: Object,
    filters: Object,
    filterOptions: Array,
});

const searchInput = ref(props.filters?.search || '');

watch(
    () => props.filters?.search,
    (s) => {
        searchInput.value = s || '';
    }
);

function applySearch() {
    router.get(
        route('admin.products.index'),
        { search: searchInput.value || undefined, filter: props.filters.filter || 'all' },
        { preserveState: true, replace: true }
    );
}

function setFilter(value) {
    router.get(
        route('admin.products.index'),
        { search: searchInput.value || undefined, filter: value },
        { preserveState: true, replace: true }
    );
}

function deactivate(p) {
    if (!p.is_active) {
        return;
    }
    if (!window.confirm(`Hide “${p.name}” from the public catalog? The seller keeps the listing in their inventory.`)) {
        return;
    }
    router.post(route('admin.products.deactivate', p.id), {}, { preserveScroll: true });
}

function softDelete(p) {
    if (!window.confirm(`Soft-delete “${p.name}”? This removes it from the catalog. Use for serious violations.`)) {
        return;
    }
    router.post(route('admin.products.soft-delete', p.id), {}, { preserveScroll: true });
}
</script>
