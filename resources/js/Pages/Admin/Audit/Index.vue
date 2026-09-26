<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-ink leading-tight">Administrative Audit Logs</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white p-6 rounded-card shadow-sm mb-6 flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-semibold text-ink-faint   mb-1">Search Action</label>
                        <input 
                            v-model="filterForm.action"
                            @input="debouncedSearch"
                            type="text" 
                            placeholder="e.g. dispute_resolved, user_banned..."
                            class="w-full rounded-card border-line text-sm focus:ring-2 focus:ring-brand"
                        />
                    </div>
                </div>

                <!-- Audit Logs Table -->
                <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-mist border-b border-line">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-semibold text-ink-faint  ">Timestamp</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-ink-faint  ">Admin</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-ink-faint  ">Action</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-ink-faint  ">Target</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-ink-faint  ">Details</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-ink-faint  ">IP Address</th>
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
                                                {{ log.user.name.charAt(0) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-ink leading-tight">{{ log.user.name }}</p>
                                                <p class="text-xs text-ink-faint font-bold  ">{{ log.user.role }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold  "
                                            :class="{
                                                'bg-rose-100 text-rose-800': log.action.includes('ban') || log.action.includes('reject'),
                                                'bg-brand-tint text-brand-dark': log.action.includes('resolve') || log.action.includes('approve'),
                                                'bg-brand-tint text-brand-dark': !log.action.includes('ban') && !log.action.includes('resolve'),
                                            }"
                                        >
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
                                            <div v-if="log.metadata" class="text-xs font-mono text-ink-soft leading-normal line-clamp-3 space-y-1">
                                                <div v-for="(value, key) in log.metadata" :key="key">
                                                    <span class="font-bold text-ink capitalize">{{ key.replace(/_/g, ' ') }}:</span> 
                                                    <span>{{ value }}</span>
                                                </div>
                                            </div>
                                            <span v-else class="text-ink-faint text-xs font-medium italic">—</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-ink-faint">
                                        {{ log.ip_address }}
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
                                    class="px-3 py-1.5 rounded-control text-xs font-bold bg-white text-ink-faint border border-line cursor-not-allowed"
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
