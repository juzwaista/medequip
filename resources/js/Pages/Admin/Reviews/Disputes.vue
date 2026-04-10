<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Review Disputes</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex items-center justify-between">
                    <div class="flex gap-4">
                        <Link 
                            :href="route('admin.reviews.disputes.index')" 
                            class="px-3 py-1.5 rounded-lg text-sm font-bold transition"
                            :class="!filters.status ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            All
                        </Link>
                        <Link 
                            :href="route('admin.reviews.disputes.index', { status: 'pending' })" 
                            class="px-3 py-1.5 rounded-lg text-sm font-bold transition"
                            :class="filters.status === 'pending' ? 'bg-amber-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            Pending
                        </Link>
                        <Link 
                            :href="route('admin.reviews.disputes.index', { status: 'resolved' })" 
                            class="px-3 py-1.5 rounded-lg text-sm font-bold transition"
                            :class="filters.status === 'resolved' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            Resolved
                        </Link>
                    </div>
                </div>

                <!-- Disputes List -->
                <div v-if="disputes.data.length > 0" class="space-y-6">
                    <div v-for="dispute in disputes.data" :key="dispute.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <!-- Dispute Info Header -->
                            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-4 mb-6">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-black uppercase">Order {{ dispute.order.order_number }}</span>
                                        <span 
                                            class="px-2 py-0.5 rounded text-[10px] font-black uppercase"
                                            :class="{
                                                'bg-amber-100 text-amber-800': dispute.dispute_status === 'pending',
                                                'bg-green-100 text-green-800': dispute.dispute_status === 'resolved',
                                                'bg-gray-100 text-gray-800': dispute.dispute_status === 'rejected',
                                            }"
                                        >
                                            {{ dispute.dispute_status }}
                                        </span>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-900 leading-tight">
                                        Dispute for <span class="text-blue-600">{{ dispute.product.name }}</span>
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">Shop: <span class="font-bold text-gray-700">{{ dispute.order.distributor.company_name }}</span></p>
                                </div>
                                <div class="flex items-center gap-4 text-xs font-bold text-gray-400 uppercase tracking-widest">
                                    <span>Submitted {{ formatDate(dispute.updated_at) }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- The Review & Dispute Reason -->
                                <div class="space-y-6">
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="flex">
                                                <svg v-for="n in 5" :key="n" class="w-4 h-4" :class="n <= dispute.stars ? 'text-amber-400' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            </div>
                                            <span class="text-xs font-bold text-gray-900">{{ dispute.user.name }} (Customer)</span>
                                        </div>
                                        <p class="text-gray-700 italic text-sm">"{{ dispute.body || 'No comment provided.' }}"</p>
                                    </div>

                                    <div class="p-4 bg-rose-50 rounded-xl border border-rose-100">
                                        <p class="text-[10px] font-black text-rose-800 uppercase tracking-widest mb-1.5">Distributor's Dispute Reason</p>
                                        <p class="text-gray-800 text-sm leading-relaxed">{{ dispute.dispute_reason }}</p>
                                    </div>

                                    <!-- Resolution Action -->
                                    <div v-if="dispute.dispute_status === 'pending'" class="pt-4 border-t">
                                        <form @submit.prevent="resolveDispute(dispute)" class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Resolution Action</label>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <button 
                                                        type="button"
                                                        @click="resolutions[dispute.id].resolution = 'upheld'; resolutions[dispute.id].should_hide = true"
                                                        class="px-4 py-2 rounded-lg text-sm font-bold border-2 transition"
                                                        :class="resolutions[dispute.id].resolution === 'upheld' ? 'bg-green-600 border-green-600 text-white' : 'border-gray-100 bg-gray-50 text-gray-600'"
                                                    >
                                                        Upheld (Hide Review)
                                                    </button>
                                                    <button 
                                                        type="button"
                                                        @click="resolutions[dispute.id].resolution = 'rejected'; resolutions[dispute.id].should_hide = false"
                                                        class="px-4 py-2 rounded-lg text-sm font-bold border-2 transition"
                                                        :class="resolutions[dispute.id].resolution === 'rejected' ? 'bg-gray-800 border-gray-800 text-white' : 'border-gray-100 bg-gray-50 text-gray-600'"
                                                    >
                                                        Reject Dispute
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div v-if="resolutions[dispute.id].resolution">
                                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Admin Note (Internal)</label>
                                                <textarea 
                                                    v-model="resolutions[dispute.id].admin_note"
                                                    class="w-full rounded-xl border-gray-200 text-sm focus:ring-2 focus:ring-blue-500"
                                                    placeholder="Reason for this resolution decision..."
                                                    rows="2"
                                                ></textarea>
                                            </div>

                                            <button 
                                                v-if="resolutions[dispute.id].resolution"
                                                type="submit"
                                                class="w-full py-3 bg-blue-600 text-white rounded-xl font-black text-sm uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-900/10"
                                            >
                                                Apply Resolution
                                            </button>
                                        </form>
                                    </div>
                                    <div v-else class="p-4 bg-emerald-50 rounded-xl border border-emerald-100 flex items-center justify-between">
                                        <div>
                                            <p class="text-[10px] font-black text-emerald-800 uppercase tracking-widest">Resolution Result</p>
                                            <p class="text-sm font-bold text-emerald-900 capitalize">{{ dispute.dispute_status }}</p>
                                        </div>
                                        <div v-if="dispute.is_hidden" class="bg-emerald-600 text-white text-[10px] font-black px-2 py-1 rounded-full uppercase">Review Hidden</div>
                                        <div v-else class="bg-gray-400 text-white text-[10px] font-black px-2 py-1 rounded-full uppercase">Review Visible</div>
                                    </div>
                                </div>

                                <!-- Packaging Evidence (The Proof) -->
                                <div class="space-y-4">
                                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Mandatory Packaging Evidence</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <p class="text-[9px] font-bold text-gray-500 uppercase">Before Packing</p>
                                            <div v-if="dispute.order.packaging_before_image_path" class="relative group">
                                                <img 
                                                    :src="`/storage/${dispute.order.packaging_before_image_path}`" 
                                                    class="w-full aspect-[4/3] object-cover rounded-xl border-2 border-gray-200 shadow-sm transition group-hover:opacity-90"
                                                />
                                                <a 
                                                    :href="`/storage/${dispute.order.packaging_before_image_path}`" 
                                                    target="_blank"
                                                    class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
                                                >
                                                    <span class="bg-black/60 text-white px-3 py-1 rounded-full text-[10px] font-bold">View Full</span>
                                                </a>
                                            </div>
                                            <div v-else class="w-full aspect-[4/3] bg-gray-100 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center text-[10px] text-gray-400 font-bold uppercase">No Photo</div>
                                        </div>

                                        <div class="space-y-2">
                                            <p class="text-[9px] font-bold text-gray-500 uppercase">After Packed</p>
                                            <div v-if="dispute.order.packaging_after_image_path" class="relative group">
                                                <img 
                                                    :src="`/storage/${dispute.order.packaging_after_image_path}`" 
                                                    class="w-full aspect-[4/3] object-cover rounded-xl border-2 border-gray-200 shadow-sm transition group-hover:opacity-90"
                                                />
                                                <a 
                                                    :href="`/storage/${dispute.order.packaging_after_image_path}`" 
                                                    target="_blank"
                                                    class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
                                                >
                                                    <span class="bg-black/60 text-white px-3 py-1 rounded-full text-[10px] font-bold">View Full</span>
                                                </a>
                                            </div>
                                            <div v-else class="w-full aspect-[4/3] bg-gray-100 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center text-[10px] text-gray-400 font-bold uppercase">No Photo</div>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-blue-50 rounded-xl border border-blue-100 text-[10px] text-blue-700 leading-relaxed italic">
                                        Distributors are required to upload these photos before marking an order as "Packed". Use these to verify the condition of items reported in the review.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-2xl shadow-sm p-12 text-center border-2 border-dashed border-gray-100">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">No disputes found</h3>
                    <p class="text-gray-500 mt-1">There are no review disputes matching your filters.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    disputes: Object,
    filters: Object,
});

const resolutions = reactive(
    props.disputes.data.reduce((acc, d) => {
        acc[d.id] = {
            resolution: '',
            should_hide: false,
            admin_note: '',
        };
        return acc;
    }, {})
);

const resolveDispute = (dispute) => {
    if (!confirm('Are you sure you want to apply this resolution? This action will be logged for transparency.')) return;

    router.patch(route('admin.reviews.disputes.resolve', dispute.id), resolutions[dispute.id], {
        preserveScroll: true,
        onSuccess: () => {
            alert('Resolution applied successfully.');
        }
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
