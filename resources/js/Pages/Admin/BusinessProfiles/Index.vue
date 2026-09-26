<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-ink">B2B Account Applications</h1>
                    <p class="text-ink-soft mt-2 text-sm">Review and manage business account applications.</p>
                </div>
            </div>

            <div class="bg-white shadow-sm border border-line rounded-card overflow-hidden">
                <!-- Status Tabs -->
                <div class="border-b border-line px-6 py-4 flex gap-4">
                    <Link :href="route('admin.business-profiles.index', { status: 'pending' })"
                        :class="['px-4 py-2 text-sm font-semibold rounded-control transition', filters.status === 'pending' ? 'bg-brand-tint text-brand-dark' : 'text-ink-soft hover:bg-mist']">
                        Pending Review
                        <span v-if="filters.status !== 'pending' && pendingCount > 0" class="ml-1.5 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-amber-100 text-amber-800 rounded-full">{{ pendingCount }}</span>
                    </Link>
                    <Link :href="route('admin.business-profiles.index', { status: 'approved' })"
                        :class="['px-4 py-2 text-sm font-semibold rounded-control transition', filters.status === 'approved' ? 'bg-brand-tint text-brand-dark' : 'text-ink-soft hover:bg-mist']">
                        Approved
                    </Link>
                    <Link :href="route('admin.business-profiles.index', { status: 'rejected' })"
                        :class="['px-4 py-2 text-sm font-semibold rounded-control transition', filters.status === 'rejected' ? 'bg-brand-tint text-brand-dark' : 'text-ink-soft hover:bg-mist']">
                        Rejected
                    </Link>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-mist text-xs text-ink-soft   font-semibold">
                            <tr>
                                <th class="px-6 py-4">Company Name</th>
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Business Type</th>
                                <th class="px-6 py-4">Date Applied</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <tr v-if="profiles.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-ink-soft">No applications found.</td>
                            </tr>
                            <tr v-for="profile in profiles.data" :key="profile.id" class="hover:bg-mist transition-colors group">
                                <td class="px-6 py-4 font-bold text-ink">{{ profile.company_name }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-ink font-medium">{{ profile.user?.name }}</div>
                                    <div class="text-xs text-ink-soft">{{ profile.user?.email }}</div>
                                </td>
                                <td class="px-6 py-4 capitalize text-ink">{{ profile.business_type }}</td>
                                <td class="px-6 py-4 text-ink-soft">{{ new Date(profile.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('admin.business-profiles.show', profile.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-line rounded-control text-sm font-semibold text-ink hover:bg-mist hover:text-brand transition shadow-sm">
                                        Review
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="profiles.links.length > 3" class="px-6 py-4 border-t border-line">
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-for="(link, index) in profiles.links" :key="index">
                            <Link v-if="link.url" :href="link.url"
                                :class="['px-3 py-1 text-sm rounded-control transition', link.active ? 'bg-brand text-white font-bold shadow-sm' : 'text-ink-soft hover:bg-mist border border-transparent hover:border-line']"
                                v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-ink-faint cursor-not-allowed" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    profiles: Object,
    filters: Object,
});

const pendingCount = computed(() => {
    // This is a simple count — in a real app you'd pass this from the server
    return props.filters.status === 'pending' ? props.profiles.total : 0;
});
</script>
