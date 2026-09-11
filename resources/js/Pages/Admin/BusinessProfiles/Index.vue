<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Business Account Applications</h1>
                    <p class="text-gray-600 mt-2 text-sm">Review applications for B2B accounts.</p>
                </div>
            </div>

            <div class="bg-white shadow-sm border border-gray-200 rounded-2xl overflow-hidden">
                <!-- Tabs / Filters -->
                <div class="border-b border-gray-200 px-6 py-4 flex gap-4">
                    <Link 
                        :href="route('admin.business-profiles.index', { status: 'pending' })"
                        class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                        :class="filters.status === 'pending' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50'"
                    >
                        Pending Review
                    </Link>
                    <Link 
                        :href="route('admin.business-profiles.index', { status: 'approved' })"
                        class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                        :class="filters.status === 'approved' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50'"
                    >
                        Approved
                    </Link>
                    <Link 
                        :href="route('admin.business-profiles.index', { status: 'rejected' })"
                        class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                        :class="filters.status === 'rejected' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50'"
                    >
                        Rejected
                    </Link>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-[10px] text-gray-500 uppercase tracking-widest font-black">
                            <tr>
                                <th class="px-6 py-4">Company Name</th>
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Business Type</th>
                                <th class="px-6 py-4">Date Applied</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="profiles.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">No applications found.</td>
                            </tr>
                            <tr v-for="profile in profiles.data" :key="profile.id" class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ profile.company_name }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-900 font-medium">{{ profile.user?.name }}</div>
                                    <div class="text-xs text-gray-500">{{ profile.user?.email }}</div>
                                </td>
                                <td class="px-6 py-4 capitalize text-gray-700">{{ profile.business_type }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ new Date(profile.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-right">
                                    <Link 
                                        :href="route('admin.business-profiles.show', profile.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition shadow-sm"
                                    >
                                        Review
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="profiles.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-for="(link, k) in profiles.links" :key="k">
                            <Link 
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1 text-sm rounded-md transition"
                                :class="link.active ? 'bg-indigo-600 text-white font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-100 border border-transparent hover:border-gray-200'"
                                v-html="link.label"
                            />
                            <span 
                                v-else 
                                class="px-3 py-1 text-sm text-gray-400 cursor-not-allowed" 
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    profiles: Object,
    filters: Object,
});
</script>
