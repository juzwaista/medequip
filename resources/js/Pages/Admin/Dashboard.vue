<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-ink">Admin Dashboard</h1>
                    <p class="text-ink-soft mt-2 text-sm">Overview of platform activity and key metrics.</p>
                </div>
                <!-- System Announcement Trigger -->
                <button 
                    @click="showAnnouncementForm = !showAnnouncementForm"
                    class="bg-brand text-white px-5 py-2.5 rounded-card hover:bg-brand-dark transition flex items-center gap-2 font-bold shadow-lg shadow-blue-100 text-sm"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    Post System Announcement
                </button>
            </div>

            <!-- ANNOUNCEMENT BROADCAST FORM (Slides in) -->
            <transition
                enter-active-class="transform transition ease-out duration-300"
                enter-from-class="-translate-y-4 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transform transition ease-in duration-200"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="-translate-y-4 opacity-0"
            >
                <div v-if="showAnnouncementForm" class="bg-brand-dark rounded-card shadow-2xl p-8 text-white relative overflow-hidden">
                    <div class="relative z-10 max-w-2xl">
                        <h2 class="text-2xl font-semibold mb-1">Broadcast System Announcement</h2>
                        <p class="text-white/80 text-sm mb-8 font-medium">This message will be sent to all users via their in-app notifications and email.</p>
                        
                        <form @submit.prevent="submitAnnouncement" class="space-y-5">
                            <div>
                                <label class="block text-xs font-semibold  text-white/80 mb-2 ">Announcement Title</label>
                                <input 
                                    v-model="announcementForm.title"
                                    type="text" 
                                    required
                                    placeholder="e.g., Scheduled Maintenance or New Feature Launch"
                                    class="w-full bg-brand-dark/50 border-2 border-brand/50 rounded-card px-5 py-3.5 focus:border-white focus:ring-0 placeholder:text-brand transition-all font-bold"
                                />
                                <span v-if="announcementForm.errors.title" class="text-xs text-red-300 mt-2 block">{{ announcementForm.errors.title }}</span>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold  text-white/80 mb-2 ">Detailed Message</label>
                                <textarea 
                                    v-model="announcementForm.message"
                                    rows="4" 
                                    required
                                    placeholder="Tell your users what's happening..."
                                    class="w-full bg-brand-dark/50 border-2 border-brand/50 rounded-card px-5 py-4 focus:border-white focus:ring-0 placeholder:text-brand transition-all font-medium leading-relaxed"
                                ></textarea>
                                <div class="flex justify-between items-center mt-2">
                                    <span v-if="announcementForm.errors.message" class="text-xs text-red-300">{{ announcementForm.errors.message }}</span>
                                    <span class="text-xs text-white/60 ml-auto font-bold  ">{{ announcementForm.message.length }} / 2000 chars</span>
                                </div>
                            </div>
                            <div class="flex gap-4 pt-2">
                                <button 
                                    type="submit" 
                                    :disabled="announcementForm.processing"
                                    class="px-8 py-4 bg-white text-brand-dark rounded-card font-semibold hover:bg-brand-tint transition-all shadow-xl active:scale-95 disabled:opacity-50"
                                >
                                    {{ announcementForm.processing ? 'Broadcasting...' : 'Broadcast Now' }}
                                </button>
                                <button 
                                    type="button" 
                                    @click="showAnnouncementForm = false"
                                    class="px-6 py-4 text-white/80 font-bold hover:text-white transition-colors"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                    <!-- Decorative Circle -->
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-60 h-60 bg-white/10 rounded-full blur-3xl"></div>
                </div>
            </transition>

            <!-- Platform Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="bg-white rounded-card shadow-sm border border-line p-5">
                    <p class="text-xs font-semibold text-ink-soft  tracking-wide">Users</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <p class="text-2xl font-bold text-ink">{{ stats.totalUsers }}</p>
                        <span v-if="financial.newUsersThisMonth" class="text-xs text-brand font-semibold">+{{ financial.newUsersThisMonth }}</span>
                    </div>
                </div>
                <div class="bg-white rounded-card shadow-sm border border-line p-5">
                    <p class="text-xs font-semibold text-ink-soft  tracking-wide">Active Shops</p>
                    <p class="text-2xl font-bold text-brand mt-1">{{ stats.totalDistributors }}</p>
                </div>
                <Link href="/admin/users?shop_status=pending" class="bg-white rounded-card shadow-sm border border-line p-5 hover:border-amber-200 hover:shadow transition group">
                    <p class="text-xs font-semibold text-ink-soft  tracking-wide group-hover:text-amber-600">Pending Verification</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.pendingVerifications }}</p>
                    <p class="text-xs text-brand font-medium mt-1">View &rarr;</p>
                </Link>
                <div class="bg-white rounded-card shadow-sm border border-line p-5">
                    <p class="text-xs font-semibold text-ink-soft  tracking-wide">Products</p>
                    <p class="text-2xl font-bold text-brand mt-1">{{ stats.totalProducts }}</p>
                </div>
                <div class="bg-white rounded-card shadow-sm border border-line p-5">
                    <p class="text-xs font-semibold text-ink-soft  tracking-wide">Total Orders</p>
                    <p class="text-2xl font-bold text-brand mt-1">{{ stats.totalOrders }}</p>
                </div>
            </div>

            <!-- Financial + Order Trend Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Financial Summary -->
                <div class="bg-white rounded-card shadow-sm border border-line p-6">
                    <h2 class="text-sm font-bold text-ink mb-4   text-[11px]">Financial Summary</h2>
                    <dl class="space-y-4">
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-ink-soft">Total Revenue</dt>
                            <dd class="text-sm font-semibold text-ink">{{ currency(financial.totalRevenue) }}</dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-ink-soft">Platform Fees</dt>
                            <dd class="text-sm font-semibold text-brand px-2 py-0.5 bg-brand-tint rounded-control">{{ currency(financial.platformFees) }}</dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-ink-soft">Payments held</dt>
                            <dd class="text-sm font-semibold text-amber-600">{{ currency(financial.escrowHeld) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Order Trend (7 days) -->
                <div class="lg:col-span-2 bg-white rounded-card shadow-sm border border-line p-6">
                    <h2 class="text-sm font-bold text-ink mb-4   text-[11px]">Orders — Last 7 Days</h2>
                    <div v-if="orderTrend.length" class="flex items-end gap-2 h-32">
                        <div
                            v-for="day in orderTrend"
                            :key="day.label"
                            class="flex-1 flex flex-col items-center gap-1 group"
                        >
                            <span class="text-xs font-bold text-ink opacity-0 group-hover:opacity-100 transition-opacity">{{ day.count }}</span>
                            <div
                                class="w-full rounded-t bg-brand transition-all duration-500 hover:bg-brand"
                                :style="{ height: barHeight(day.count) + '%', minHeight: '4px' }"
                            ></div>
                            <span class="text-xs text-ink-faint font-semibold  tracking-tight">{{ day.label.split(' ')[1] }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-ink-faint text-center py-8">No data yet.</p>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Link href="/admin/users" class="bg-white rounded-card shadow-sm border border-line p-5 hover:border-brand-soft hover:shadow-lg transition flex items-center gap-4">
                    <div class="bg-brand-tint text-brand p-2.5 rounded-card">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <span class="text-sm font-bold text-ink">Users & Shops</span>
                </Link>
                <Link href="/admin/orders" class="bg-white rounded-card shadow-sm border border-line p-5 hover:border-brand-soft hover:shadow-lg transition flex items-center gap-4">
                    <div class="bg-brand-tint text-brand p-2.5 rounded-card">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <span class="text-sm font-bold text-ink">All Orders</span>
                </Link>
                <Link href="/admin/reports" class="bg-white rounded-card shadow-sm border border-line p-5 hover:border-rose-200 hover:shadow-lg transition flex items-center gap-4">
                    <div class="bg-rose-50 text-rose-600 p-2.5 rounded-card text-rose-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <span class="text-sm font-bold text-ink">Reports Hub</span>
                </Link>
                <Link href="/admin/couriers" class="bg-white rounded-card shadow-sm border border-line p-5 hover:border-brand-soft hover:shadow-lg transition flex items-center gap-4">
                    <div class="bg-brand-tint text-brand p-2.5 rounded-card">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <span class="text-sm font-bold text-ink">Courier Fleet</span>
                </Link>
            </div>

            <!-- Recent Activity + Orders Hub -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Orders -->
                <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden flex flex-col">
                    <div class="px-8 py-5 border-b border-line flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-ink  ">Recent Orders</h2>
                        <Link href="/admin/orders" class="text-xs font-bold text-brand hover:underline">Full Report &rarr;</Link>
                    </div>
                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-mist text-xs text-ink-soft   font-semibold">
                                <tr>
                                    <th class="px-8 py-4">ID</th>
                                    <th class="px-8 py-4">Status</th>
                                    <th class="px-8 py-4 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                <tr v-for="o in recentOrders" :key="o.id" class="hover:bg-mist transition-colors group">
                                    <td class="px-8 py-4">
                                        <div class="font-semibold text-ink">{{ o.order_number }}</div>
                                        <div class="text-xs text-ink-faint font-bold mt-1 ">{{ o.created_at }}</div>
                                    </td>
                                    <td class="px-8 py-4">
                                        <span :class="statusClasses(o.status)" class="px-3 py-1 rounded-full text-xs font-semibold  ">{{ o.status }}</span>
                                    </td>
                                    <td class="px-8 py-4 text-right">
                                        <span class="font-semibold text-ink">{{ currency(o.total) }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Activity (Shops) -->
                <div class="bg-white rounded-card shadow-sm border border-line overflow-hidden">
                    <div class="px-8 py-5 border-b border-line flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-ink  ">Shop Activity</h2>
                        <Link href="/admin/users" class="text-xs font-bold text-brand hover:underline">Directory &rarr;</Link>
                    </div>
                    <div class="divide-y divide-line">
                        <div v-for="a in recentActivity" :key="a.id" class="px-8 py-4 flex items-center justify-between hover:bg-mist transition-colors">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-ink truncate">{{ a.company_name }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <p class="text-xs text-ink-faint font-bold  ">{{ a.created_at }}</p>
                                    <span v-if="a.rejection_count > 0" class="text-xs font-semibold text-red-500 bg-red-50 px-1.5 py-0.5 rounded  border border-red-100">
                                        Attempts: {{ a.rejection_count }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right ml-4">
                                <span :class="statusClasses(a.status)" class="px-3 py-1 rounded-full text-xs font-semibold  ">{{ a.status }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: Object,
    financial: Object,
    orderTrend: Array,
    recentOrders: Array,
    recentActivity: Array,
    atRiskDistributors: Array,
});

const showAnnouncementForm = ref(false);
const announcementForm = useForm({
    title: '',
    message: '',
});

const submitAnnouncement = () => {
    announcementForm.post('/admin/broadcast-announcement', {
        onSuccess: () => {
            showAnnouncementForm.value = false;
            announcementForm.reset();
        }
    });
};

const maxTrend = Math.max(...(props.orderTrend?.map(d => d.count) || [1]), 1);
const barHeight = (count) => Math.round((count / maxTrend) * 100);

const currency = (val) => {
    const n = Number(val || 0);
    return '₱' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const statusClasses = (status) => ({
    'bg-yellow-50 text-yellow-600 border border-yellow-100': status === 'pending',
    'bg-brand-tint text-brand border border-brand-soft': status === 'accepted' || status === 'packed',
    'bg-brand-tint text-brand border border-brand-soft': status === 'shipped',
    'bg-brand-tint text-brand border border-brand-soft': status === 'delivered' || status === 'completed' || status === 'approved',
    'bg-red-50 text-red-600 border border-red-100': status === 'cancelled' || status === 'rejected' || status === 'banned',
});
</script>
