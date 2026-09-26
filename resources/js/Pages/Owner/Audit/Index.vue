<template>
    <OwnerLayout title="Audit Logs">
        <template #header>
            <h2 class="font-semibold text-xl text-ink leading-tight">Distributor Audit Logs</h2>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-ink">Audit Logs</h1>
                        <p class="text-sm text-ink-soft mt-1">Track actions performed by you and your staff members.</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white p-6 rounded-card shadow-sm mb-6 flex flex-wrap items-center gap-4 border border-line">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-semibold text-ink-faint   mb-1">Search Action</label>
                        <input 
                            v-model="filterForm.action"
                            @input="debouncedSearch"
                            type="text" 
                            placeholder="e.g. create, update, delete..."
                            class="w-full rounded-card border-line text-sm focus:ring-2 focus:ring-brand"
                        />
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-semibold text-ink-faint   mb-1">Filter by Staff</label>
                        <select 
                            v-model="filterForm.user_id"
                            @change="search"
                            class="w-full rounded-card border-line text-sm focus:ring-2 focus:ring-brand"
                        >
                            <option value="">All Staff</option>
                            <option v-for="staff in staffMembers" :key="staff.id" :value="staff.id">
                                {{ staff.name }} ({{ staff.role }})
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Audit Logs Table -->
                <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-mist border-b border-line">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-semibold text-ink-faint  ">Timestamp</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-ink-faint  ">User</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-ink-faint  ">Action</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-ink-faint  ">Target</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-ink-faint  ">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-brand-tint/30 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-ink-soft font-medium">
                                        {{ formatDate(log.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-brand-tint flex items-center justify-center text-brand-dark font-bold text-xs ">
                                                {{ log.user?.name.charAt(0) || '?' }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-ink leading-tight">{{ log.user?.name || 'Unknown User' }}</p>
                                                <p class="text-xs text-ink-faint font-bold  ">{{ log.user?.role || 'Unknown' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold   bg-brand-tint text-brand-dark">
                                            {{ log.action.replace(/_/g, ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="log.target_type" class="text-xs text-ink-soft">
                                            <span class="font-bold text-ink   text-xs">{{ log.target_type.split('\\').pop() }}</span>
                                            <span class="text-ink-faint mx-1">#</span>
                                            <span class="font-mono">{{ log.target_id }}</span>
                                        </div>
                                        <div v-else class="text-ink-faint text-xs font-semibold ">None</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="max-w-xs overflow-hidden">
                                            <p v-if="log.metadata" class="text-xs font-mono text-ink-soft leading-normal line-clamp-2">
                                                {{ JSON.stringify(log.metadata) }}
                                            </p>
                                            <span v-else class="text-ink-faint text-xs font-medium italic">—</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!logs.data.length">
                                    <td colspan="5" class="px-6 py-8 text-center text-ink-soft">
                                        No audit logs found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.links.length > 3" class="px-6 py-4 bg-mist border-t border-line flex justify-center">
                        <nav class="flex gap-1">
                            <template v-for="(link, k) in logs.links" :key="k">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-3 py-1.5 rounded-control text-xs font-bold transition"
                                    :class="link.active ? 'bg-brand text-white shadow-lg shadow-blue-900/10' : 'bg-white text-ink-soft hover:bg-mist border border-line'"
                                    v-html="link.label"
                                />
                                <span 
                                    v-else 
                                    class="px-3 py-1.5 rounded-control text-xs font-bold bg-mist text-ink-faint border border-line"
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
