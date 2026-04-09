<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Message reports</h1>
                    <div class="mt-2 bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-900 flex items-start gap-2 shadow-sm mb-1">
                        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <p><strong>Strictly Confidential:</strong> You are about to view sensitive data. Unauthorized disclosure of this information is a violation of the Data Privacy Act.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="s in statusOptions"
                        :key="s"
                        :href="filterHref(s)"
                        preserve-state
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition"
                        :class="filterActive(s)
                            ? 'bg-blue-600 text-white border-blue-600'
                            : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                    >
                        {{ s === 'all' ? 'All' : s }}
                    </Link>
                </div>
            </div>

            <div class="sm:hidden space-y-3 mb-6">
                <div
                    v-for="r in reports.data"
                    :key="'m-' + r.id"
                    class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-3"
                >
                    <div class="flex justify-between gap-2 text-xs text-gray-500">
                        <span>#{{ r.id }}</span>
                        <span>{{ formatWhen(r.created_at) }}</span>
                    </div>
                    <p class="text-sm text-gray-900 font-medium">{{ r.reporter?.name || '—' }}</p>
                    <p class="text-xs text-gray-600 line-clamp-3">{{ r.message?.body_preview }}</p>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-bold text-gray-500 uppercase">Status</label>
                        <select
                            v-model="rowState[r.id].status"
                            class="w-full rounded-lg border-gray-300 text-sm min-h-[44px]"
                            @change="saveRow(r.id)"
                        >
                            <option v-for="opt in statusValues" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                        <label class="text-[10px] font-bold text-gray-500 uppercase">Admin notes</label>
                        <textarea
                            v-model="rowState[r.id].admin_notes"
                            rows="2"
                            class="w-full rounded-lg border-gray-300 text-sm"
                            placeholder="Internal notes…"
                            @blur="saveRow(r.id)"
                        />
                    </div>
                </div>
            </div>

            <div class="hidden sm:block bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">Reporter</th>
                                <th class="px-4 py-3">Message</th>
                                <th class="px-4 py-3">Context</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 min-w-[12rem]">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="r in reports.data" :key="r.id" class="hover:bg-gray-50/80 align-top">
                                <td class="px-4 py-3 text-gray-500 tabular-nums">#{{ r.id }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900">{{ r.reporter?.name || '—' }}</p>
                                    <p class="text-xs text-gray-500 break-all">{{ r.reporter?.email }}</p>
                                </td>
                                <td class="px-4 py-3 max-w-xs">
                                    <p class="text-gray-800 line-clamp-3">{{ r.message?.body_preview }}</p>
                                    <p class="text-[10px] text-gray-400 mt-1">From {{ r.message?.author }} · {{ r.reason }}</p>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    <p>Conv {{ r.message?.conversation_id ?? '—' }}</p>
                                    <p v-if="r.message?.order_id">Order {{ r.message.order_id }}</p>
                                    <p v-if="r.message?.shop" class="mt-1 font-medium text-gray-800">{{ r.message.shop }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <select
                                        v-model="rowState[r.id].status"
                                        class="rounded-lg border-gray-300 text-sm min-w-[8rem]"
                                        @change="saveRow(r.id)"
                                    >
                                        <option v-for="opt in statusValues" :key="opt" :value="opt">{{ opt }}</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <textarea
                                        v-model="rowState[r.id].admin_notes"
                                        rows="2"
                                        class="w-full rounded-lg border-gray-300 text-sm"
                                        placeholder="Internal notes…"
                                        @blur="saveRow(r.id)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="!reports.data?.length" class="text-center py-16 text-gray-500 text-sm">
                No reports for this filter.
            </div>

            <div v-if="reports.links?.length > 3" class="mt-8 flex justify-center gap-2 flex-wrap">
                <Link
                    v-for="link in reports.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="px-3 py-1 rounded-lg text-sm border"
                    :class="[
                        link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 text-gray-700 hover:bg-gray-50',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                    preserve-state
                    v-html="link.label"
                />
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    reports: Object,
    filters: Object,
    statusOptions: Array,
});

const statusValues = ['open', 'reviewing', 'resolved', 'dismissed'];

const rowState = reactive({});

watch(
    () => props.reports?.data,
    (rows) => {
        for (const r of rows || []) {
            rowState[r.id] = {
                status: r.status,
                admin_notes: r.admin_notes || '',
            };
        }
    },
    { immediate: true }
);

function filterHref(s) {
    if (s === 'all') {
        return '/admin/message-reports';
    }
    return `/admin/message-reports?status=${encodeURIComponent(s)}`;
}

function filterActive(s) {
    const cur = props.filters?.status || 'all';
    return s === cur;
}

function formatWhen(iso) {
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return '';
    }
}

function saveRow(id) {
    const row = rowState[id];
    if (!row) {
        return;
    }
    router.patch(`/admin/message-reports/${id}`, {
        status: row.status,
        admin_notes: row.admin_notes,
    }, { preserveScroll: true });
}
</script>
