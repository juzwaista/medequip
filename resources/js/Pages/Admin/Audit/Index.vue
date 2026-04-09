<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administrative Audit Logs</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white p-6 rounded-2xl shadow-sm mb-6 flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Search Action</label>
                        <input 
                            v-model="filterForm.action"
                            @input="debouncedSearch"
                            type="text" 
                            placeholder="e.g. dispute_resolved, user_banned..."
                            class="w-full rounded-xl border-gray-200 text-sm focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <!-- Audit Logs Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Timestamp</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Admin</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Action</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Target</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Details</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">IP Address</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-blue-50/30 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-medium">
                                        {{ formatDate(log.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs uppercase">
                                                {{ log.user.name.charAt(0) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900 leading-tight">{{ log.user.name }}</p>
                                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">{{ log.user.role }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter"
                                            :class="{
                                                'bg-rose-100 text-rose-800': log.action.includes('ban') || log.action.includes('reject'),
                                                'bg-green-100 text-green-800': log.action.includes('resolve') || log.action.includes('approve'),
                                                'bg-blue-100 text-blue-800': !log.action.includes('ban') && !log.action.includes('resolve'),
                                            }"
                                        >
                                            {{ log.action.replace(/_/g, ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="log.target_type" class="text-xs text-gray-600">
                                            <span class="font-bold text-gray-900 uppercase tracking-tighter text-[10px]">{{ log.target_type.split('\\').pop() }}</span>
                                            <span class="text-gray-400 mx-1">#</span>
                                            <span class="font-mono">{{ log.target_id }}</span>
                                        </div>
                                        <div v-else class="text-gray-300 text-[10px] font-black uppercase">None</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="max-w-xs overflow-hidden">
                                            <p v-if="log.metadata" class="text-[10px] font-mono text-gray-500 leading-normal line-clamp-2">
                                                {{ JSON.stringify(log.metadata) }}
                                            </p>
                                            <span v-else class="text-gray-300 text-[10px] font-medium italic">—</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-[10px] font-mono text-gray-400">
                                        {{ log.ip_address }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.links.length > 3" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-center">
                        <nav class="flex gap-1">
                            <template v-for="(link, k) in logs.links" :key="k">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                                    :class="link.active ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/10' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                                    v-html="link.label"
                                />
                                <span 
                                    v-else 
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-gray-300 border border-gray-100 cursor-not-allowed"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const filterForm = reactive({
    action: props.filters.action || '',
    user_id: props.filters.user_id || '',
});

const debouncedSearch = debounce(() => {
    router.get(route('admin.audit-logs.index'), filterForm, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 500);

const formatDate = (date) => {
    return new Date(date).toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};
</script>
