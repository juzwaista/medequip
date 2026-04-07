<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Reports hub</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Flagged content and user reports awaiting review.
                </p>
            </div>

            <div class="flex flex-wrap gap-2 mb-4 border-b border-gray-200 pb-4">
                <Link
                    v-for="t in tabDefs"
                    :key="t.key"
                    :href="tabHref(t.key)"
                    preserve-state
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs sm:text-sm font-semibold border transition"
                    :class="tab === t.key
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                >
                    {{ t.label }}
                    <span
                        v-if="t.badge != null && t.badge > 0"
                        class="text-[10px] font-black rounded-full min-w-[1.25rem] px-1.5 py-0.5 text-center leading-none"
                        :class="tab === t.key ? 'bg-white/20 text-white' : 'bg-rose-500 text-white'"
                    >
                        {{ t.badge > 99 ? '99+' : t.badge }}
                    </span>
                </Link>
            </div>

            <div class="flex flex-wrap gap-2 mb-6">
                <Link
                    v-for="s in statusOptions"
                    :key="s"
                    :href="statusHref(s)"
                    preserve-state
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition"
                    :class="status === s
                        ? 'bg-slate-800 text-white border-slate-800'
                        : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                >
                    {{ s === 'all' ? 'All statuses' : s }}
                </Link>
            </div>

            <!-- Message-family tabs -->
            <template v-if="['messages', 'shops', 'customers'].includes(tab) && messageReports">
                <div class="space-y-3 sm:hidden">
                    <Link
                        v-for="r in messageReports.data"
                        :key="'m-' + r.id"
                        :href="showHref('message', r.id)"
                        class="block bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-2 active:bg-gray-50"
                    >
                        <div class="flex justify-between gap-2 text-xs text-gray-500">
                            <span>#{{ r.id }}</span>
                            <span>{{ formatWhen(r.created_at) }}</span>
                        </div>
                        <p class="text-sm font-medium text-gray-900">{{ r.reporter?.name || '—' }}</p>
                        <p class="text-xs text-gray-600 line-clamp-3">{{ r.summary?.preview || '—' }}</p>
                        <div class="flex flex-wrap gap-2 text-[10px]">
                            <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-bold uppercase">{{ r.status }}</span>
                            <span v-if="r.summary?.shop" class="text-gray-500">{{ r.summary.shop }}</span>
                        </div>
                    </Link>
                </div>

                <div class="hidden sm:block bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="px-4 py-3">Reporter</th>
                                    <th class="px-4 py-3">Preview</th>
                                    <th class="px-4 py-3">Context</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="r in messageReports.data"
                                    :key="r.id"
                                    class="hover:bg-gray-50/80 cursor-pointer"
                                    @click="goShow('message', r.id)"
                                >
                                    <td class="px-4 py-3 text-gray-500 tabular-nums">#{{ r.id }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ r.reporter?.name || '—' }}</p>
                                        <p class="text-xs text-gray-500 break-all">{{ r.reporter?.email }}</p>
                                    </td>
                                    <td class="px-4 py-3 max-w-sm">
                                        <p class="text-gray-800 line-clamp-2">{{ r.summary?.preview || '—' }}</p>
                                        <p class="text-[10px] text-gray-400 mt-1">{{ r.reason }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        <p v-if="r.summary?.shop" class="font-medium text-gray-800">{{ r.summary.shop }}</p>
                                        <p>Role: {{ r.summary?.author_role || '—' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-800">{{ r.status }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="messageReports.links?.length > 3" class="mt-8 flex justify-center gap-2 flex-wrap">
                    <Link
                        v-for="link in messageReports.links"
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
                <p v-if="!messageReports.data?.length" class="text-center py-12 text-gray-500 text-sm">Nothing in this queue for the current filters.</p>
            </template>

            <!-- User reports -->
            <template v-else-if="tab === 'users' && userReports">
                <div class="space-y-3 sm:hidden">
                    <Link
                        v-for="r in userReports.data"
                        :key="'u-' + r.id"
                        :href="showHref('user', r.id)"
                        class="block bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-2"
                    >
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>#{{ r.id }}</span>
                            <span>{{ formatWhen(r.created_at) }}</span>
                        </div>
                        <p class="text-sm text-gray-900"><span class="font-medium">Subject:</span> {{ r.summary?.subject_name || '—' }}</p>
                        <p class="text-xs text-gray-600">{{ r.reason }}</p>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-gray-100">{{ r.status }}</span>
                    </Link>
                </div>

                <div class="hidden sm:block bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="px-4 py-3">Reporter</th>
                                    <th class="px-4 py-3">Subject</th>
                                    <th class="px-4 py-3">Reason</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="r in userReports.data"
                                    :key="r.id"
                                    class="hover:bg-gray-50/80 cursor-pointer"
                                    @click="goShow('user', r.id)"
                                >
                                    <td class="px-4 py-3 text-gray-500">#{{ r.id }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ r.reporter?.name }}</p>
                                        <p class="text-xs text-gray-500 break-all">{{ r.reporter?.email }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ r.summary?.subject_name }}</p>
                                        <p class="text-xs text-gray-500">{{ r.summary?.subject_email }}</p>
                                        <p class="text-[10px] text-gray-400">{{ r.summary?.subject_role }} · banned: {{ r.summary?.banned ? 'yes' : 'no' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 max-w-xs">{{ r.reason }}</td>
                                    <td class="px-4 py-3"><span class="text-xs font-bold bg-gray-100 px-2 py-1 rounded-lg">{{ r.status }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="userReports.links?.length > 3" class="mt-8 flex justify-center gap-2 flex-wrap">
                    <Link
                        v-for="link in userReports.links"
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
                <p v-if="!userReports.data?.length" class="text-center py-12 text-gray-500 text-sm">Nothing in this queue for the current filters.</p>
            </template>

            <!-- Product listing reports -->
            <template v-else-if="tab === 'products' && productReports">
                <div class="space-y-3 sm:hidden">
                    <Link
                        v-for="r in productReports.data"
                        :key="'p-' + r.id"
                        :href="showHref('product', r.id)"
                        class="block bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-2"
                    >
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>#{{ r.id }}</span>
                            <span>{{ formatWhen(r.created_at) }}</span>
                        </div>
                        <p class="text-sm font-medium text-gray-900">{{ r.summary?.product_name || 'Product' }}</p>
                        <p class="text-xs text-gray-600">{{ r.reason }}</p>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-gray-100">{{ r.status }}</span>
                    </Link>
                </div>

                <div class="hidden sm:block bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="px-4 py-3">Reporter</th>
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-4 py-3">Reason</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="r in productReports.data"
                                    :key="r.id"
                                    class="hover:bg-gray-50/80 cursor-pointer"
                                    @click="goShow('product', r.id)"
                                >
                                    <td class="px-4 py-3 text-gray-500">#{{ r.id }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ r.reporter?.name }}</p>
                                        <p class="text-xs text-gray-500 break-all">{{ r.reporter?.email }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ r.summary?.product_name || '—' }}</p>
                                        <p class="text-[10px] text-gray-400">#{{ r.summary?.product_id }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 max-w-xs">{{ r.reason }}</td>
                                    <td class="px-4 py-3"><span class="text-xs font-bold bg-gray-100 px-2 py-1 rounded-lg">{{ r.status }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="productReports.links?.length > 3" class="mt-8 flex justify-center gap-2 flex-wrap">
                    <Link
                        v-for="link in productReports.links"
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
                <p v-if="!productReports.data?.length" class="text-center py-12 text-gray-500 text-sm">Nothing in this queue for the current filters.</p>
            </template>

            <!-- Couriers + delivery flags -->
            <template v-else-if="tab === 'couriers' && courierReports && deliveryFlags">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Courier reports</h2>
                <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden mb-10">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="px-4 py-3">Reporter</th>
                                    <th class="px-4 py-3">Courier</th>
                                    <th class="px-4 py-3">Order</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="r in courierReports.data"
                                    :key="'c-' + r.id"
                                    class="hover:bg-gray-50/80 cursor-pointer"
                                    @click="goShow('courier', r.id)"
                                >
                                    <td class="px-4 py-3 text-gray-500">#{{ r.id }}</td>
                                    <td class="px-4 py-3">{{ r.reporter?.name }}</td>
                                    <td class="px-4 py-3">{{ r.summary?.courier_name || '—' }}</td>
                                    <td class="px-4 py-3 tabular-nums">{{ r.summary?.order_number || '—' }}</td>
                                    <td class="px-4 py-3"><span class="text-xs font-bold bg-gray-100 px-2 py-1 rounded-lg">{{ r.status }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="courierReports.links?.length > 3" class="mt-8 flex justify-center gap-2 flex-wrap mb-8">
                    <Link
                        v-for="link in courierReports.links"
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
                <p v-if="!courierReports.data?.length" class="text-sm text-gray-500 mb-8">No courier reports for this filter.</p>

                <h2 class="text-lg font-semibold text-gray-900 mb-1 flex items-center gap-1">
                    Low delivery ratings (≤2
                    <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    )
                </h2>
                <p class="text-sm text-gray-600 mb-3">Queued until cleared or acted on.</p>
                <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3">Stars</th>
                                    <th class="px-4 py-3">Courier</th>
                                    <th class="px-4 py-3">Order</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="r in deliveryFlags.data"
                                    :key="'d-' + r.id"
                                    class="hover:bg-gray-50/80 cursor-pointer"
                                    @click="goShow('delivery', r.id)"
                                >
                                    <td class="px-4 py-3 text-gray-500">#{{ r.id }}</td>
                                    <td class="px-4 py-3">{{ r.reporter?.name }}</td>
                                    <td class="px-4 py-3 font-bold text-amber-700 flex items-center gap-0.5">
                                        {{ r.summary?.stars }}
                                        <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    </td>
                                    <td class="px-4 py-3">{{ r.summary?.courier_name || '—' }}</td>
                                    <td class="px-4 py-3 tabular-nums">{{ r.summary?.order_number || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="deliveryFlags.links?.length > 3" class="mt-8 flex justify-center gap-2 flex-wrap">
                    <Link
                        v-for="link in deliveryFlags.links"
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
                <p
                    v-if="!deliveryFlags.data?.length"
                    class="text-center py-8 text-gray-500 text-sm"
                >
                    No low ratings in queue.
                </p>
            </template>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    tab: String,
    status: String,
    counts: Object,
    messageReports: Object,
    userReports: Object,
    courierReports: Object,
    deliveryFlags: Object,
    productReports: Object,
    statusOptions: Array,
});

const tabDefs = computed(() => {
    const c = props.counts || {};
    return [
        { key: 'messages', label: 'All chat', badge: c.open_messages },
        { key: 'shops', label: 'Shop messages', badge: null },
        { key: 'customers', label: 'Customer messages', badge: null },
        { key: 'users', label: 'User reports', badge: c.open_users },
        { key: 'products', label: 'Product listings', badge: c.open_products },
        {
            key: 'couriers',
            label: 'Couriers & ratings',
            badge: (c.open_couriers || 0) + (c.delivery_flags || 0),
        },
    ];
});

function tabHref(key) {
    return route('admin.reports.index', { tab: key, status: props.status || 'open' });
}

function statusHref(s) {
    return route('admin.reports.index', { tab: props.tab, status: s });
}

function showHref(bucket, id) {
    return route('admin.reports.show', { bucket, id });
}

function goShow(bucket, id) {
    router.visit(showHref(bucket, id));
}

function formatWhen(iso) {
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return '';
    }
}
</script>
