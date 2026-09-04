<template>
    <OwnerLayout title="Audit Logs">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Distributor Audit Logs</h2>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Audit Logs</h1>
                        <p class="text-sm text-gray-500 mt-1">Track actions performed by you and your staff members.</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white p-6 rounded-2xl shadow-sm mb-6 flex flex-wrap items-center gap-4 border border-gray-100">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Search Action</label>
                        <input 
                            v-model="filterForm.action"
                            @input="debouncedSearch"
                            type="text" 
                            placeholder="e.g. create, update, delete..."
                            class="w-full rounded-xl border-gray-200 text-sm focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Filter by Staff</label>
                        <select 
                            v-model="filterForm.user_id"
                            @change="search"
                            class="w-full rounded-xl border-gray-200 text-sm focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">All Staff</option>
                            <option v-for="staff in staffMembers" :key="staff.id" :value="staff.id">
                                {{ staff.name }} ({{ staff.role }})
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Audit Logs Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Timestamp</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">User</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Action</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Target</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Details</th>
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
                                                {{ log.user?.name.charAt(0) || '?' }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900 leading-tight">{{ log.user?.name || 'Unknown User' }}</p>
                                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">{{ log.user?.role || 'Unknown' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter bg-blue-100 text-blue-800">
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
                                </tr>
                                <tr v-if="!logs.data.length">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        No audit logs found.
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
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-gray-50 text-gray-400 border border-gray-100"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    staffMembers: Array,
});

const filterForm = reactive({
    action: props.filters.action || '',
    user_id: props.filters.user_id || '',
});

const search = () => {
    router.get('/owner/audit-logs', filterForm, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const debouncedSearch = debounce(search, 300);

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};
</script>
