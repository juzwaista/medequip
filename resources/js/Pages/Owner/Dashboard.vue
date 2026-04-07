<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6 pb-28 sm:pb-24 text-slate-800 min-w-0">
            <Teleport to="body">
                <div
                    v-if="toastMessage"
                    class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 z-[100] max-w-none sm:max-w-sm rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-xl shadow-slate-900/5 flex items-start gap-3 animate-in fade-in slide-in-from-top-2 duration-300"
                    role="status"
                >
                    <span class="mt-0.5 h-2 w-2 rounded-full bg-emerald-500 shrink-0 shadow-[0_0_8px_rgba(16,185,129,0.5)]" />
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Live Update</p>
                        <p class="text-sm font-medium text-slate-800 mt-0.5">{{ toastMessage }}</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600 text-lg leading-none ml-auto" @click="toastMessage = ''" aria-label="Dismiss">&times;</button>
                </div>
            </Teleport>

            <header class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 sm:gap-4">
                <div class="min-w-0">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Overview</h1>
                    <p class="text-slate-500 text-sm mt-1 font-medium">{{ distributor.company_name }}</p>
                </div>
                <div class="flex items-center gap-2 text-xs font-medium text-slate-500 bg-white px-3 py-1.5 rounded-full border border-slate-200 shadow-sm">
                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                    Live updates on
                </div>
            </header>

            <!-- ⚠️ Missing address/location banner -->
            <div 
                v-if="!distributor.latitude || !distributor.address"
                class="mb-6 bg-amber-50 border border-amber-300 rounded-xl px-4 py-4 flex items-start gap-3 shadow-sm"
            >
                <div class="shrink-0 mt-0.5">
                    <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-amber-800">Your store address is not set up yet.</p>
                    <p class="text-xs text-amber-700 mt-0.5">Customers and couriers rely on your address for delivery and pickup. Please set your business address and pin your exact location on the map.</p>
                </div>
                <Link href="/owner/profile/edit" class="shrink-0 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-lg transition">
                    Set Address →
                </Link>
            </div>



            <!-- Today at a Glance -->
            <section class="mb-8">
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-2 sm:gap-3">
                    <Link href="/owner/orders?status=pending" class="group bg-white border border-amber-200 hover:border-amber-400 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm hover:shadow-md transition-all">
                        <p class="text-[10px] sm:text-xs font-bold text-amber-700 uppercase tracking-wider mb-1 leading-tight">Pending Orders</p>
                        <p class="text-2xl sm:text-3xl font-black text-amber-900 tabular-nums">{{ livePending }}</p>
                        <p class="text-xs text-amber-600 mt-1 font-medium">waiting for review</p>
                    </Link>
                    <Link href="/owner/orders?status=approved" class="group bg-white border border-indigo-200 hover:border-indigo-400 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm hover:shadow-md transition-all">
                        <p class="text-[10px] sm:text-xs font-bold text-indigo-700 uppercase tracking-wider mb-1 leading-tight">Packing &amp; Shipping</p>
                        <p class="text-2xl sm:text-3xl font-black text-indigo-900 tabular-nums">{{ liveProcessing }}</p>
                        <p class="text-xs text-indigo-600 mt-1 font-medium">orders in progress</p>
                    </Link>
                    <Link v-if="stats.rxBacklog > 0" href="/owner/orders?prescription=pending" class="group bg-white border border-purple-200 hover:border-purple-400 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm hover:shadow-md transition-all">
                        <p class="text-[10px] sm:text-xs font-bold text-purple-700 uppercase tracking-wider mb-1 leading-tight">Rx Pending</p>
                        <p class="text-2xl sm:text-3xl font-black text-purple-900 tabular-nums">{{ stats.rxBacklog }}</p>
                        <p class="text-xs text-purple-600 mt-1 font-medium">prescriptions to review</p>
                    </Link>
                    <div v-else class="bg-white border border-purple-100 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm">
                        <p class="text-[10px] sm:text-xs font-bold text-purple-400 uppercase tracking-wider mb-1 leading-tight">Rx Pending</p>
                        <p class="text-2xl sm:text-3xl font-black text-purple-300 tabular-nums">0</p>
                        <p class="text-xs text-purple-300 mt-1 font-medium">all clear</p>
                    </div>
                    <Link href="/owner/messages" class="group bg-white border border-sky-200 hover:border-sky-400 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm hover:shadow-md transition-all">
                        <p class="text-[10px] sm:text-xs font-bold text-sky-700 uppercase tracking-wider mb-1 leading-tight">Messages</p>
                        <p class="text-2xl sm:text-3xl font-black text-sky-900 tabular-nums">{{ stats.unreadMessages }}</p>
                        <p class="text-xs text-sky-600 mt-1 font-medium">unread from customers</p>
                    </Link>
                    <div class="bg-white border border-slate-200 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 leading-tight">This Month</p>
                        <p class="text-2xl sm:text-3xl font-black text-slate-900 tabular-nums">{{ stats.ordersMtd ?? 0 }}</p>
                        <p class="text-xs text-slate-400 mt-1 font-medium">orders received</p>
                    </div>
                </div>
            </section>

            <!-- Welcome state for new distributors -->
            <section v-if="stats.totalProducts === 0" class="mb-8">
                <div class="rounded-2xl border-2 border-dashed border-blue-200 bg-blue-50/40 p-8 text-center">
                    <h2 class="text-lg font-bold text-slate-800 mb-1">Welcome to your dashboard!</h2>
                    <p class="text-sm text-slate-500 mb-5 max-w-md mx-auto">Add your first product to start receiving orders. You can manage your inventory, pricing, and stock all from one place.</p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <Link href="/owner/inventory/create" class="inline-flex items-center gap-2 bg-blue-600 text-white font-bold px-5 py-2.5 rounded-xl text-sm hover:bg-blue-700 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Your First Product
                        </Link>
                        <Link href="/owner/inventory" class="inline-flex items-center gap-2 bg-white border border-slate-300 text-slate-700 font-bold px-5 py-2.5 rounded-xl text-sm hover:bg-slate-50 transition">
                            View Inventory
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Critical Alerts -->
            <section v-if="alert_center?.length" id="inventory-alerts" class="mb-10 space-y-3 scroll-mt-24">
                <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] ml-1">Critical Alerts</h2>
                <div class="space-y-3">
                    <Link
                        v-for="(b, idx) in alert_center"
                        :key="idx"
                        :href="b.href"
                        class="group flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl px-6 py-5 border transition-all duration-200 shadow-sm hover:shadow-md"
                        :class="b.level === 'critical'
                            ? 'border-rose-200 bg-gradient-to-r from-rose-50/80 to-white hover:border-rose-300'
                            : 'border-amber-200 bg-gradient-to-r from-amber-50/80 to-white hover:border-amber-300'"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="relative flex h-2.5 w-2.5">
                                    <span v-if="b.level === 'critical'" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="b.level === 'critical' ? 'bg-rose-500' : 'bg-amber-500'"></span>
                                </span>
                                <p class="text-[11px] font-bold uppercase tracking-wider" :class="b.level === 'critical' ? 'text-rose-700' : 'text-amber-800'">
                                    {{ b.level === 'critical' ? 'Immediate Action Required' : 'Warning' }}
                                </p>
                            </div>
                            <p class="text-lg font-bold text-slate-900">{{ b.title }}</p>
                            <p class="text-sm text-slate-600 mt-0.5 leading-snug">{{ b.body }}</p>
                        </div>
                        <span
                            v-if="b.action"
                            class="shrink-0 inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-bold transition-colors"
                            :class="b.level === 'critical'
                                ? 'bg-rose-600 text-white shadow-sm shadow-rose-200 group-hover:bg-rose-700'
                                : 'bg-amber-500 text-white shadow-sm shadow-amber-200 group-hover:bg-amber-600'"
                        >
                            {{ b.action }} &rarr;
                        </span>
                    </Link>
                </div>
            </section>

            <!-- Earnings + Order Status -->
            <section class="mb-10 grid lg:grid-cols-2 gap-6">
                <!-- Earnings Card (toggleable) -->
                <div class="space-y-4">
                    <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] ml-1">Your Earnings</h2>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col gap-4">
                        <template v-if="canViewFinancials">
                            <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white shadow-md relative overflow-hidden">
                                <svg class="absolute -right-4 -bottom-4 h-32 w-32 text-emerald-400 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-xs font-bold uppercase tracking-wider text-emerald-100 relative z-10">{{ earningsLabel }}</p>
                                <p class="text-3xl sm:text-4xl md:text-5xl font-black mt-2 tracking-tight drop-shadow-sm relative z-10 break-all">{{ formattedEarnings }}</p>
                                <p v-if="earningsComparison" class="text-sm font-semibold mt-3 relative z-10" :class="earningsDelta >= 0 ? 'text-emerald-100' : 'text-rose-200'">
                                    <span v-if="earningsDelta >= 0">+</span>{{ earningsDelta.toFixed(1) }}% {{ earnings.comparison_label }}
                                </p>
                                <p class="text-xs font-medium text-emerald-50 mt-2 leading-snug relative z-10 max-w-[80%] opacity-90">Verified payments only, net after platform fee.</p>
                            </div>
                            <!-- Period presets -->
                            <div class="flex flex-wrap items-center gap-2">
                                <button v-for="opt in earningsPresets" :key="opt.value" type="button"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                                    :class="activeEarningsPreset === opt.value ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    @click="setEarningsPreset(opt.value)"
                                >{{ opt.label }}</button>
                            </div>
                            <!-- Custom range -->
                            <div v-if="activeEarningsPreset === 'custom'" class="flex items-center gap-2 flex-wrap">
                                <input v-model="customFrom" type="date" class="rounded-lg border-slate-200 text-xs font-medium py-2 focus:ring-emerald-500" />
                                <span class="text-slate-400 text-xs">to</span>
                                <input v-model="customTo" type="date" class="rounded-lg border-slate-200 text-xs font-medium py-2 focus:ring-emerald-500" />
                                <button type="button" class="rounded-lg bg-emerald-600 text-white text-xs font-bold px-4 py-2 hover:bg-emerald-700 transition" @click="applyCustomEarnings">Apply</button>
                            </div>
                        </template>
                        <template v-else>
                            <div class="flex-1 flex flex-col justify-center">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Orders This Month</p>
                                <p class="text-4xl font-extrabold tabular-nums text-slate-900 mt-1">{{ stats.ordersMtd ?? 0 }}</p>
                                <div class="mt-6 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-center">
                                    <p class="text-[11px] font-medium text-slate-400">Revenue details are restricted to shop owners.</p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Order Status Breakdown -->
                <div class="space-y-4">
                    <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] ml-1">Order Status Breakdown</h2>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-6 shadow-sm overflow-hidden">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6">
                            <div class="rounded-xl bg-gradient-to-br from-amber-500/10 to-amber-50/30 border border-amber-200/60 p-4 sm:p-5 group hover:border-amber-300 transition-colors">
                                <p class="text-[11px] font-bold text-amber-900/80 uppercase tracking-wider">Needs Your Approval</p>
                                <p class="text-3xl sm:text-4xl font-extrabold tabular-nums text-amber-900 mt-2">{{ livePending }}</p>
                                <Link href="/owner/orders?status=pending" class="mt-4 inline-flex text-sm font-semibold text-amber-800 group-hover:text-amber-900 group-hover:underline decoration-amber-300 underline-offset-4">Review &amp; approve &rarr;</Link>
                            </div>
                            <div class="rounded-xl bg-gradient-to-br from-indigo-500/10 to-indigo-50/30 border border-indigo-200/60 p-4 sm:p-5 group hover:border-indigo-300 transition-colors">
                                <p class="text-[11px] font-bold text-indigo-900/80 uppercase tracking-wider">Pack &amp; Ready to Ship</p>
                                <p class="text-3xl sm:text-4xl font-extrabold tabular-nums text-indigo-900 mt-2">{{ liveProcessing }}</p>
                                <Link href="/owner/orders?status=approved" class="mt-4 inline-flex text-sm font-semibold text-indigo-800 group-hover:text-indigo-900 group-hover:underline decoration-indigo-300 underline-offset-4">Continue packing &rarr;</Link>
                            </div>
                        </div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3">All Order Stages</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2 sm:gap-3">
                            <Link
                                v-for="row in order_pipeline"
                                :key="row.key"
                                :href="row.href"
                                class="flex flex-col rounded-xl border px-4 py-3 transition duration-200 hover:shadow-md"
                                :class="pipelineAccent(row.key)"
                            >
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ row.label }}</span>
                                <span class="text-2xl font-extrabold tabular-nums text-slate-900 mt-1">{{ row.count }}</span>
                                <span class="text-[11px] font-medium text-slate-500 mt-1 line-clamp-2 leading-tight">{{ row.description }}</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Inventory & Stock -->
            <section class="mb-12">
                <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Inventory &amp; Stock Health</h2>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Units in Stock</p>
                        <p class="text-3xl font-extrabold tabular-nums text-slate-900 mt-2">{{ inventory_pulse.quantity_total }}</p>
                        <p class="text-xs text-slate-400 mt-1">across all your products</p>
                    </div>
                    <div class="rounded-2xl border border-violet-200/80 bg-gradient-to-br from-violet-50/50 to-white p-6 shadow-sm hover:shadow-md transition-shadow group">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-violet-800/80">Reserved for Orders</p>
                        <p class="text-3xl font-extrabold tabular-nums text-violet-900 mt-2">{{ inventory_pulse.reserved_total }}</p>
                        <Link href="/owner/orders?status=pending" class="text-xs font-semibold text-violet-700 mt-3 inline-block group-hover:underline decoration-violet-300 underline-offset-4">View open orders &rarr;</Link>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col justify-center group hover:shadow-md transition-shadow">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Products Listed</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ stats.totalProducts }} <span class="text-sm font-medium text-slate-400">Active</span></p>
                        <p v-if="stats.lowStockCount > 0" class="text-xs font-bold text-rose-600 mt-2 bg-rose-50 inline-block px-2 py-1 rounded-md self-start">
                            {{ stats.lowStockCount }} below reorder point
                        </p>
                        <Link href="/owner/inventory" class="mt-auto pt-3 text-xs font-semibold text-cyan-700 group-hover:text-cyan-800 group-hover:underline decoration-cyan-300 underline-offset-4">Manage products &rarr;</Link>
                    </div>
                </div>

                <!-- Restock Recommendations -->
                <div class="mt-6 rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-slate-900">Restock Recommendations</h3>
                                <span class="px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-700 text-[10px] font-black uppercase tracking-wider">Recommended Action</span>
                            </div>
                            <p class="text-xs font-medium text-slate-500 mt-1">Products that may need restocking soon.</p>
                        </div>
                    </div>

                    <div v-if="restock_insights?.recommendations?.length" class="overflow-x-auto -mx-2 px-2 sm:mx-0 sm:px-0 touch-pan-x">
                        <table class="w-full text-left border-collapse min-w-[640px]">
                            <thead>
                                <tr class="bg-white border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    <th class="py-3 px-6">Product</th>
                                    <th class="py-3 px-4 text-right">Current Stock</th>
                                    <th class="py-3 px-4 text-right">Run Rate/Day</th>
                                    <th class="py-3 px-4 text-right">Stockout In</th>
                                    <th class="py-3 px-6 text-right">Recommended Restock</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 bg-white">
                                <tr v-for="rec in restock_insights.recommendations" :key="rec.id" class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-2">
                                            <span v-if="rec.priority === 'high'" class="h-2 w-2 rounded-full bg-rose-500 shrink-0" title="High"></span>
                                            <span v-else-if="rec.priority === 'medium'" class="h-2 w-2 rounded-full bg-amber-500 shrink-0" title="Medium"></span>
                                            <span v-else class="h-2 w-2 rounded-full bg-blue-500 shrink-0" title="Low"></span>
                                            <Link :href="`/owner/inventory?search=${rec.product_id}`" class="text-sm font-bold text-slate-800 line-clamp-1 truncate hover:text-cyan-700 group-hover:underline decoration-cyan-300 underline-offset-4" :title="rec.product_name">{{ rec.product_name }}</Link>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-right text-sm font-bold text-slate-700">{{ rec.current_stock }}</td>
                                    <td class="py-4 px-4 text-right text-xs font-medium text-slate-500">{{ Number(rec.avg_daily_sales).toFixed(1) }} units</td>
                                    <td class="py-4 px-4 text-right">
                                        <span class="inline-flex px-2 py-1 rounded-md text-xs font-bold" :class="rec.days_until_stockout <= 5 ? 'bg-rose-50 text-rose-700 border border-rose-200/50' : 'bg-amber-50 text-amber-700 border border-amber-200/50'">
                                            {{ rec.days_until_stockout }} days
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <span class="inline-flex px-3 py-1 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-sm shadow-emerald-200 text-xs font-bold whitespace-nowrap">
                                            +{{ rec.recommended_quantity }} units
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-12 flex flex-col items-center justify-center text-center bg-white">
                        <div class="h-12 w-12 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-800">Your stock levels are healthy</p>
                        <p class="text-xs font-medium text-slate-500 mt-1 max-w-sm">No urgent restock recommendations at this time.</p>
                    </div>
                </div>
            </section>

            <!-- Priority Orders Queue -->
            <section id="operational-queue" class="mb-12 scroll-mt-24">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-4 ml-1">
                    <div>
                        <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Priority Orders</h2>
                        <p class="text-sm font-medium text-slate-500 mt-1">Orders sorted by urgency. Critical-care and delayed orders surface first.</p>
                    </div>
                    <button
                        v-if="queueFilterProductId"
                        type="button"
                        class="text-xs font-bold text-slate-500 hover:text-rose-600 bg-slate-100 hover:bg-rose-50 px-3 py-1.5 rounded-full transition-colors"
                        @click="clearQueueFilter"
                    >
                        &times; Clear Filter
                    </button>
                </div>

                <div v-if="queueFilterProductId" class="mb-4 text-xs font-bold text-cyan-800 rounded-xl bg-cyan-50 border border-cyan-200 px-4 py-3 flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Showing orders containing Product ID #{{ queueFilterProductId }}
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <div class="px-3 sm:px-6 py-3 sm:py-4 border-b border-slate-100 flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center bg-slate-50/80">
                        <span class="text-sm font-bold text-slate-800">Actionable Orders</span>
                        <Link href="/owner/orders" class="text-xs font-bold text-cyan-700 hover:text-cyan-800 hover:underline decoration-cyan-300 underline-offset-4 py-1 touch-manipulation">View All Orders &rarr;</Link>
                    </div>
                    <div v-if="filteredQueue.length" class="divide-y divide-slate-100/80">
                        <Link
                            v-for="order in filteredQueue"
                            :key="order.id"
                            :href="`/owner/orders/${order.id}`"
                            class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 px-3 sm:px-6 py-4 hover:bg-slate-50 transition-colors group touch-manipulation min-h-[56px]"
                        >
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="inline-flex items-center justify-center min-w-[2.5rem] h-10 rounded-xl text-sm font-black tabular-nums shadow-sm" :class="priorityBadgeClass(order.priority_score)">
                                    {{ order.priority_score }}
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md bg-slate-100 text-slate-600 border border-slate-200/50">{{ order.status }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-slate-900 truncate group-hover:text-cyan-700 transition-colors">{{ order.order_number }}</p>
                                <p class="text-xs font-medium text-slate-500 truncate mt-0.5">{{ order.customer?.name || 'Walk-in Customer' }}</p>
                                <p class="text-[11px] font-semibold text-slate-500 mt-1.5 flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="order.priority_score >= 7 ? 'bg-rose-500' : 'bg-slate-300'"></span>
                                    {{ order.priority_label }}
                                </p>
                            </div>
                            <div class="text-right shrink-0 sm:min-w-[120px]">
                                <p v-if="canViewFinancials" class="text-sm font-bold tabular-nums text-slate-900">{{ formatCurrency(order.total_amount) }}</p>
                                <p class="text-[11px] font-medium text-slate-400 mt-1">{{ formatWhen(order.created_at) }}</p>
                            </div>
                        </Link>
                    </div>
                    <div v-else class="py-16 flex flex-col items-center justify-center text-center">
                        <div class="h-12 w-12 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-600">No orders need attention right now.</p>
                        <p class="text-xs font-medium text-slate-400 mt-1">New orders will appear here automatically.</p>
                    </div>
                </div>
            </section>

            <!-- Link to Insights -->
            <section v-if="canViewFinancials" class="mb-12">
                <Link href="/owner/insights" class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md hover:border-slate-300 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="h-10 w-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <p class="font-bold text-slate-900">Insights</p>
                            <p class="text-xs font-medium text-slate-500">View sales trends, product performance, demand forecasts, and inventory analytics.</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-slate-600 shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </Link>
            </section>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

const props = defineProps({
    distributor: Object,
    canViewFinancials: { type: Boolean, default: true },
    stats: Object,
    earnings: { type: Object, default: () => ({}) },
    dssWarning: { type: Object, default: null },
    alert_center: { type: Array, default: () => [] },
    recentOrders: Array,
    order_pipeline: { type: Array, default: () => [] },
    inventory_pulse: { type: Object, default: () => ({ quantity_total: 0, reserved_total: 0 }) },
    restock_insights: { type: Object, default: () => ({}) },
    pulse_baseline: { type: Object, default: () => ({ newest_order_id: 0 }) },
});

const livePending = ref(props.stats.pendingOrders);
const liveProcessing = ref(props.stats.processingOrders);
const toastMessage = ref('');
const lastSeenOrderId = ref(props.pulse_baseline?.newest_order_id ?? 0);
const queueFilterProductId = ref(null);

const activeEarningsPreset = ref(props.earnings?.preset || 'this_month');
const customFrom = ref(props.earnings?.from || '');
const customTo = ref(props.earnings?.to || '');

const earningsPresets = [
    { value: 'this_month', label: 'This Month' },
    { value: 'last_month', label: 'Last Month' },
    { value: 'this_year', label: 'This Year' },
    { value: 'custom', label: 'Custom Range' },
];

watch(() => props.stats, (s) => {
    livePending.value = s.pendingOrders;
    liveProcessing.value = s.processingOrders;
}, { deep: true });

watch(() => props.pulse_baseline, (p) => {
    if (p?.newest_order_id != null) lastSeenOrderId.value = p.newest_order_id;
}, { deep: true });

watch(() => props.earnings, (e) => {
    if (e?.preset) activeEarningsPreset.value = e.preset;
    if (e?.from) customFrom.value = e.from;
    if (e?.to) customTo.value = e.to;
}, { deep: true });

const earningsLabel = computed(() => {
    const e = props.earnings;
    if (!e?.label) return 'Earnings';
    return `Earnings \u2014 ${e.label}`;
});

const formattedEarnings = computed(() => {
    return '\u20B1' + Number(props.earnings?.amount ?? 0).toLocaleString();
});

const earningsComparison = computed(() => {
    const prev = props.earnings?.previous_amount;
    return prev != null && prev !== 0;
});

const earningsDelta = computed(() => {
    const current = props.earnings?.amount ?? 0;
    const prev = props.earnings?.previous_amount ?? 0;
    if (prev === 0) return current > 0 ? 100 : 0;
    return ((current - prev) / prev) * 100;
});

const filteredQueue = computed(() => {
    const list = props.recentOrders || [];
    const pid = queueFilterProductId.value;
    if (!pid) return list;
    return list.filter((o) => (o.product_ids || []).includes(pid));
});

function pipelineAccent(key) {
    if (key === 'pending') return 'border-amber-200 bg-amber-50/40 text-amber-900 hover:border-amber-300 hover:bg-amber-50';
    if (key === 'approved' || key === 'packed') return 'border-indigo-200 bg-indigo-50/40 text-indigo-900 hover:border-indigo-300 hover:bg-indigo-50';
    if (key === 'shipped') return 'border-cyan-200 bg-cyan-50/40 text-cyan-900 hover:border-cyan-300 hover:bg-cyan-50';
    return 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50';
}

function priorityBadgeClass(score) {
    if (score >= 10) return 'bg-rose-600 text-white shadow-rose-200/50';
    if (score >= 7) return 'bg-orange-500 text-white shadow-orange-200/50';
    if (score >= 5) return 'bg-amber-400 text-slate-900 shadow-amber-200/50';
    if (score >= 3) return 'bg-sky-500 text-white shadow-sky-200/50';
    return 'bg-slate-100 text-slate-600 border border-slate-200';
}

function formatWhen(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatCurrency(val) {
    return '\u20B1' + Number(val).toLocaleString();
}

function clearQueueFilter() { queueFilterProductId.value = null; }

function setEarningsPreset(preset) {
    activeEarningsPreset.value = preset;
    if (preset !== 'custom') {
        router.get('/owner/dashboard', { earnings_preset: preset }, { preserveState: true, preserveScroll: true, replace: true });
    }
}

function applyCustomEarnings() {
    router.get('/owner/dashboard', {
        earnings_preset: 'custom',
        earnings_from: customFrom.value,
        earnings_to: customTo.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

let pulseTimer;
async function pollPulse() {
    if (document.hidden) return;
    try {
        const { data } = await window.axios.get(route('owner.dashboard.pulse'));
        if (data.pending_orders != null) livePending.value = data.pending_orders;
        if (data.processing_orders != null) liveProcessing.value = data.processing_orders;

        const nid = Number(data.newest_order_id || 0);
        if (nid > lastSeenOrderId.value) {
            lastSeenOrderId.value = nid;
            toastMessage.value = `Order ${data.newest_order_number || '#' + nid} needs processing.`;
            setTimeout(() => { toastMessage.value = ''; }, 6000);
        }
    } catch { /* ignore */ }
}

function handleVisibilityChange() {
    if (document.hidden) {
        clearInterval(pulseTimer);
    } else {
        pollPulse();
        pulseTimer = setInterval(pollPulse, 30000);
    }
}

onMounted(() => {
    pollPulse();
    pulseTimer = setInterval(pollPulse, 30000);
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onBeforeUnmount(() => {
    if (pulseTimer) clearInterval(pulseTimer);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>
