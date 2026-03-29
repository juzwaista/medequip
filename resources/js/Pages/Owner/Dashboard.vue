<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto pb-20 text-slate-800">
            <Teleport to="body">
                <div
                    v-if="toastMessage"
                    class="fixed top-4 right-4 z-[100] max-w-sm rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-xl shadow-slate-900/5 flex items-start gap-3 animate-in fade-in slide-in-from-top-2 duration-300"
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

            <header class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Overview</h1>
                    <p class="text-slate-500 text-sm mt-1 font-medium">{{ distributor.company_name }}</p>
                </div>
                <div class="flex items-center gap-2 text-xs font-medium text-slate-500 bg-white px-3 py-1.5 rounded-full border border-slate-200 shadow-sm">
                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                    Live updates on
                </div>
            </header>

            <!-- Automated DSS Risk Warning -->
            <div v-if="dssWarning" class="mb-8 border rounded-2xl p-5 shadow-sm" :class="dssWarning.level === 'Critical' ? 'bg-rose-50 border-rose-200' : 'bg-orange-50 border-orange-200'">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 mt-0.5" :class="dssWarning.level === 'Critical' ? 'text-rose-600' : 'text-orange-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider mb-1" :class="dssWarning.level === 'Critical' ? 'text-rose-800' : 'text-orange-800'">
                             AUTOMATED ACCOUNT WARNING: {{ dssWarning.level }} RISK
                        </h3>
                        <p class="text-xs mb-3 font-medium" :class="dssWarning.level === 'Critical' ? 'text-rose-700' : 'text-orange-700'">
                            Your account has been flagged by the system for the following reasons. Please resolve these issues immediately to avoid account suspension:
                        </p>
                        <ul class="list-disc list-inside text-sm space-y-1" :class="dssWarning.level === 'Critical' ? 'text-rose-800' : 'text-orange-800'">
                            <li v-for="reason in dssWarning.reasons" :key="reason">{{ reason }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Today at a Glance Strip -->
            <section class="mb-8">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <Link href="/owner/orders?status=pending" class="group bg-white border border-amber-200 hover:border-amber-400 rounded-2xl p-4 shadow-sm hover:shadow-md transition-all">
                        <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">⏳ Waiting Your Approval</p>
                        <p class="text-3xl font-black text-amber-900 tabular-nums">{{ livePending }}</p>
                        <p class="text-xs text-amber-600 mt-1 font-medium">orders pending review</p>
                    </Link>
                    <Link href="/owner/orders?status=approved" class="group bg-white border border-indigo-200 hover:border-indigo-400 rounded-2xl p-4 shadow-sm hover:shadow-md transition-all">
                        <p class="text-xs font-bold text-indigo-700 uppercase tracking-wider mb-1">📦 Being Packed</p>
                        <p class="text-3xl font-black text-indigo-900 tabular-nums">{{ liveProcessing }}</p>
                        <p class="text-xs text-indigo-600 mt-1 font-medium">orders to pack & ship</p>
                    </Link>
                    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">🏪 Active Products</p>
                        <p class="text-3xl font-black text-slate-900 tabular-nums">{{ stats.totalProducts }}</p>
                        <p class="text-xs text-slate-400 mt-1 font-medium">listed in the marketplace</p>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">📊 This Month</p>
                        <p class="text-3xl font-black text-slate-900 tabular-nums">{{ stats.ordersMtd ?? 0 }}</p>
                        <p class="text-xs text-slate-400 mt-1 font-medium">orders received</p>
                    </div>
                </div>
            </section>

            <!-- Empty state for brand new distributors -->
            <section v-if="stats.totalProducts === 0 && (stats.ordersMtd ?? 0) === 0" class="mb-8">
                <div class="rounded-2xl border-2 border-dashed border-blue-200 bg-blue-50/40 p-8 text-center">
                    <div class="text-4xl mb-3">👋</div>
                    <h2 class="text-lg font-bold text-slate-800 mb-1">Welcome to your distributor dashboard!</h2>
                    <p class="text-sm text-slate-500 mb-5 max-w-md mx-auto">You're all set. Here's how to get started — add your first product, set your inventory, and you'll start receiving orders.</p>
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

            <section v-if="alert_center?.length" class="mb-10 space-y-3">
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

            <section class="mb-10 grid lg:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-2 ml-1">
                        <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Order Status Breakdown</h2>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                        <div class="grid sm:grid-cols-2 gap-4 mb-6">
                            <div class="rounded-xl bg-gradient-to-br from-amber-500/10 to-amber-50/30 border border-amber-200/60 p-5 group hover:border-amber-300 transition-colors">
                                <p class="text-[11px] font-bold text-amber-900/80 uppercase tracking-wider">Needs Your Approval</p>
                                <p class="text-4xl font-extrabold tabular-nums text-amber-900 mt-2">{{ livePending }}</p>
                                <Link href="/owner/orders?status=pending" class="mt-4 inline-flex text-sm font-semibold text-amber-800 group-hover:text-amber-900 group-hover:underline decoration-amber-300 underline-offset-4">Review &amp; approve &rarr;</Link>
                            </div>
                            <div class="rounded-xl bg-gradient-to-br from-indigo-500/10 to-indigo-50/30 border border-indigo-200/60 p-5 group hover:border-indigo-300 transition-colors">
                                <p class="text-[11px] font-bold text-indigo-900/80 uppercase tracking-wider">Pack &amp; Ready to Ship</p>
                                <p class="text-4xl font-extrabold tabular-nums text-indigo-900 mt-2">{{ liveProcessing }}</p>
                                <Link href="/owner/orders?status=approved" class="mt-4 inline-flex text-sm font-semibold text-indigo-800 group-hover:text-indigo-900 group-hover:underline decoration-indigo-300 underline-offset-4">Continue packing &rarr;</Link>
                            </div>
                        </div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3">All Order Stages</p>
                        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3">
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

                <div class="space-y-4">
                    <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] ml-1">Your Earnings</h2>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm h-full flex flex-col gap-4">
                        <template v-if="canViewFinancials">
                            <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 flex flex-col justify-center h-full text-white shadow-md relative overflow-hidden">
                                <svg class="absolute -right-4 -bottom-4 h-32 w-32 text-emerald-400 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                
                                <p class="text-xs font-bold uppercase tracking-wider text-emerald-100 relative z-10">Money Earned This Month</p>
                                <p class="text-5xl font-black mt-2 tracking-tight drop-shadow-sm relative z-10">₱{{ Number(stats.revenueMtd ?? 0).toLocaleString() }}</p>
                                <p class="text-xs font-medium text-emerald-50 mt-4 leading-snug relative z-10 max-w-[80%] opacity-90">Verified payments only, net after platform fee.</p>
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
            </section>

            <section class="mb-12">
                <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">📦 Inventory &amp; Stock Health</h2>
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
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ stats.totalProducts }} <span class="text-sm font-medium text-slate-400">Active Products</span></p>
                        <p v-if="stats.lowStockCount > 0" class="text-xs font-bold text-rose-600 mt-2 bg-rose-50 inline-block px-2 py-1 rounded-md self-start">
                            {{ stats.lowStockCount }} below reorder point
                        </p>
                        <Link href="/owner/inventory" class="mt-auto pt-3 text-xs font-semibold text-cyan-700 group-hover:text-cyan-800 group-hover:underline decoration-cyan-300 underline-offset-4">Manage products &rarr;</Link>
                    </div>
                </div>

                <!-- NEW ACTIONABLE RECOMMENDATIONS BLOCK -->
                <div class="mt-6 rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-slate-900">Restock Recommendations</h3>
                                <span class="px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-700 text-[10px] font-black uppercase tracking-wider">Recommended Action</span>
                            </div>
                            <p class="text-xs font-medium text-slate-500 mt-1">Smart replenishment alerts based on your sales velocity and remaining stock.</p>
                        </div>
                    </div>
                    
                    <div v-if="restock_insights?.recommendations?.length" class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[600px]">
                            <thead>
                                <tr class="bg-white border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    <th class="py-3 px-6">Product</th>
                                    <th class="py-3 px-4 text-right">Current Stock</th>
                                    <th class="py-3 px-4 text-right">Run Rate/Day</th>
                                    <th class="py-3 px-4 text-right">Stockout target</th>
                                    <th class="py-3 px-6 text-right">Recommended Restock</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 bg-white">
                                <tr v-for="rec in restock_insights.recommendations" :key="rec.id" class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-2">
                                            <span v-if="rec.priority === 'urgent'" class="h-2 w-2 rounded-full bg-rose-500 shrink-0" title="Urgent"></span>
                                            <span v-else-if="rec.priority === 'high'" class="h-2 w-2 rounded-full bg-amber-500 shrink-0" title="High"></span>
                                            <span v-else class="h-2 w-2 rounded-full bg-blue-500 shrink-0" title="Normal"></span>
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
                            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-800">Your stock levels are healthy</p>
                        <p class="text-xs font-medium text-slate-500 mt-1 max-w-sm">There are no urgent restock recommendations at this time. We'll notify you when items are running low.</p>
                    </div>
                </div>
            </section>

            <section class="mb-12">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6 ml-1">
                    <div>
                        <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Performance Trends</h2>
                        <p class="text-sm font-medium text-slate-500 mt-1">Track your sales, product popularity, and stock health.</p>
                    </div>
                    <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 shadow-inner">
                        <button
                            type="button"
                            class="px-4 py-2 text-xs font-bold rounded-lg transition-all"
                            :class="trendScope === 'mtd' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100'"
                            @click="setTrendScope('mtd')"
                        >
                            This Month
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 text-xs font-bold rounded-lg transition-all"
                            :class="trendScope === 'yoy' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/50' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100'"
                            @click="setTrendScope('yoy')"
                        >
                            vs. Last Year
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm mb-6">
                    <h3 class="font-bold text-slate-900">{{ analytics.demand_forecast?.mode === 'yoy' ? 'Sales History · Past 12 Months' : 'Expected Sales · This Month' }}</h3>
                    <p class="text-xs font-medium text-slate-500 mt-1">{{ analytics.demand_forecast?.value_label }}</p>
                    
                    <div v-show="analytics.demand_forecast?.labels?.length" class="h-72 mt-6">
                        <canvas ref="demandForecastRef" />
                    </div>
                    
                    <div v-show="!analytics.demand_forecast?.labels?.length" class="h-72 mt-6 flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-xl bg-slate-50/50">
                        <svg class="w-8 h-8 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <p class="text-sm font-medium text-slate-500">Not enough data to predict sales yet.</p>
                    </div>
                    <p class="text-[11px] font-medium text-slate-400 mt-4 text-center">Solid Line: Actual Sales · Dashed Line: Expected Sales</p>
                </div>

                <div class="grid xl:grid-cols-3 gap-6 mb-6">
                    <div class="xl:col-span-2 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm overflow-hidden flex flex-col">
                        <div class="flex items-start justify-between gap-3 mb-6">
                            <div>
                                <h3 class="font-bold text-slate-900">Fastest Moving Products</h3>
                                <p class="text-xs font-medium text-slate-500 mt-1">How fast your top products are selling. Click a product to filter the Priority Orders below.</p>
                            </div>
                        </div>
                        
                        <div v-if="heatmap.rows?.length" class="overflow-x-auto flex-1">
                            <div class="min-w-[520px]">
                                <div class="grid gap-1.5" :style="{ gridTemplateColumns: `minmax(140px,1.2fr) repeat(${heatmap.week_labels.length}, minmax(36px,1fr))` }">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider py-2">Product Name</div>
                                    <div v-for="(wl, wi) in heatmap.week_labels" :key="wi" class="text-[10px] font-bold text-slate-400 uppercase tracking-wider py-2 text-center">{{ wl }}</div>
                                    <template v-for="row in heatmap.rows" :key="row.product_id">
                                        <button type="button" class="text-left text-xs font-semibold text-slate-700 hover:text-cyan-700 py-2 truncate pr-2 transition-colors group" @click="setQueueFilter(row.product_id)">
                                            <span class="group-hover:underline decoration-cyan-300 underline-offset-4">{{ row.name }}</span>
                                        </button>
                                        <button v-for="(cell, ci) in row.cells" :key="ci" type="button" class="h-8 rounded-md border border-slate-900/5 hover:border-slate-400 hover:scale-105 hover:shadow-sm transition-all duration-200" :style="{ background: heatColor(cell) }" :title="`${row.name}: ${row.units[ci]} units`" @click="setQueueFilter(row.product_id)" />
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex-1 flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-xl bg-slate-50/50 py-10">
                            <p class="text-sm font-medium text-slate-500">No recent sales to display.</p>
                        </div>

                        <div v-if="heatmap.rows?.length" class="flex items-center justify-end gap-3 mt-6 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <span class="inline-block h-2 w-4 rounded-sm" :style="{ background: heatColor(0.15) }" /> Cold
                            <span class="inline-block h-2 w-4 rounded-sm" :style="{ background: heatColor(0.5) }" /> Mid
                            <span class="inline-block h-2 w-4 rounded-sm" :style="{ background: heatColor(1) }" /> Hot
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col items-center justify-center relative overflow-hidden">
                        <div class="absolute inset-0 opacity-[0.03] pointer-events-none" 
                             :class="{ 'bg-emerald-500': gauge.zone === 'balanced', 'bg-amber-500': gauge.zone === 'overstock', 'bg-rose-500': gauge.zone === 'understock' }">
                        </div>
                        <h3 class="font-bold text-slate-900 self-start w-full relative z-10">Stock Health</h3>
                        <p class="text-xs font-medium text-slate-500 mt-1 self-start w-full relative z-10">Estimated days until your current stock runs out.</p>
                        
                        <div class="relative w-56 h-32 mt-8 z-10">
                            <svg viewBox="0 0 200 120" class="w-full h-full drop-shadow-sm">
                                <defs>
                                    <linearGradient id="gaugeTrack" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" stop-color="#fda4af" />
                                        <stop offset="45%" stop-color="#6ee7b7" />
                                        <stop offset="100%" stop-color="#fcd34d" />
                                    </linearGradient>
                                </defs>
                                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="url(#gaugeTrack)" stroke-width="14" stroke-linecap="round" />
                                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#e2e8f0" stroke-width="14" stroke-linecap="round" opacity="0.3" />
                                <g :transform="`rotate(${gaugeRotation} 100 100)`" style="transition: transform 1s cubic-bezier(0.34, 1.56, 0.64, 1);">
                                    <line x1="100" y1="100" x2="100" y2="38" stroke="#0f172a" stroke-width="4" stroke-linecap="round" />
                                    <circle cx="100" cy="100" r="6" fill="#0f172a" />
                                    <circle cx="100" cy="100" r="2" fill="#ffffff" />
                                </g>
                            </svg>
                        </div>
                        <div class="text-center mt-2 relative z-10">
                            <p class="text-sm font-bold text-slate-800">{{ gauge.headline }}</p>
                            <p v-if="gauge.days_cover != null" class="text-xs font-medium text-slate-500 mt-1 bg-slate-50 px-2 py-1 rounded-md inline-block">
                                ~{{ gauge.days_cover }} days remaining
                            </p>
                            <p v-else class="text-[10px] font-medium text-slate-400 mt-1">We need more sales history to calculate this.</p>
                        </div>
                    </div>
                </div>

                <div class="grid xl:grid-cols-2 gap-6 mb-6">
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col h-full">
                        <div class="flex flex-col sm:flex-row justify-between gap-3 mb-6">
                            <div>
                                <h3 class="font-bold text-slate-900">Total Orders Over Time</h3>
                                <p class="text-xs font-medium text-slate-500">{{ charts.orders_period_label }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <input v-model="ordFrom" type="date" class="rounded-xl border-slate-200 text-xs font-medium text-slate-600 focus:ring-cyan-500" />
                                <span class="text-slate-400 text-xs">–</span>
                                <input v-model="ordTo" type="date" class="rounded-xl border-slate-200 text-xs font-medium text-slate-600 focus:ring-cyan-500" />
                                <button type="button" class="rounded-xl bg-slate-100 text-slate-700 text-xs font-bold px-3 py-2.5 hover:bg-slate-200 transition-colors" @click="applyOrders">Apply</button>
                            </div>
                        </div>
                        
                        <div v-show="charts.orders_series?.length" class="h-[280px]">
                            <canvas ref="dailyChartRef" />
                        </div>
                        <div v-show="!charts.orders_series?.length" class="h-[280px] flex items-center justify-center border-2 border-dashed border-slate-100 rounded-xl bg-slate-50/50">
                            <p class="text-sm font-medium text-slate-400">No order data for this period.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col h-full">
                        <div class="flex flex-col lg:flex-row justify-between gap-4 mb-6">
                            <div class="min-w-0 pr-4">
                                <h3 class="font-bold text-slate-900 truncate">Top Performers</h3>
                                <p class="text-xs font-medium text-slate-500 mt-1 line-clamp-1 truncate" :title="charts.top_period_label + ' · ' + topMetricDescription">{{ charts.top_period_label }} · {{ topMetricDescription }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <select v-if="canViewFinancials" v-model="topMetric" class="rounded-xl border-slate-200 text-xs font-medium text-slate-600 py-2 focus:ring-cyan-500">
                                    <option value="revenue">By Revenue</option>
                                    <option value="units">By Units Sold</option>
                                    <option value="orders">By Total Orders</option>
                                </select>
                                <select v-else v-model="topMetric" class="rounded-xl border-slate-200 text-xs font-medium text-slate-600 py-2 focus:ring-cyan-500">
                                    <option value="units">By Units Sold</option>
                                    <option value="orders">By Total Orders</option>
                                </select>
                                <button type="button" class="rounded-xl bg-slate-100 text-slate-700 text-xs font-bold px-4 py-2 flex items-center gap-1 hover:bg-slate-200 transition-colors" @click="applyTop">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    Sort
                                </button>
                            </div>
                        </div>
                        
                        <div v-show="charts.top_products?.length" class="h-[280px]">
                            <canvas ref="topProductsChartRef" />
                        </div>
                        <div v-show="!charts.top_products?.length" class="h-[280px] flex items-center justify-center border-2 border-dashed border-slate-100 rounded-xl bg-slate-50/50">
                            <p class="text-sm font-medium text-slate-400">No performance data available.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="operational-queue" class="mb-12 scroll-mt-24">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-4 ml-1">
                    <div>
                        <h2 class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Priority Orders</h2>
                        <p class="text-sm font-medium text-slate-500 mt-1">Sorted by urgency and how long the customer has been waiting.</p>
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
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Showing only orders containing Product ID #{{ queueFilterProductId }}
                </div>
                
                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/80">
                        <span class="text-sm font-bold text-slate-800">Actionable Orders</span>
                        <Link href="/owner/orders" class="text-xs font-bold text-cyan-700 hover:text-cyan-800 hover:underline decoration-cyan-300 underline-offset-4">View All Orders &rarr;</Link>
                    </div>
                    <div v-if="filteredQueue.length" class="divide-y divide-slate-100/80">
                        <Link
                            v-for="order in filteredQueue"
                            :key="order.id"
                            :href="`/owner/orders/${order.id}`"
                            class="flex flex-col sm:flex-row sm:items-center gap-4 px-6 py-4 hover:bg-slate-50 transition-colors group"
                        >
                            <div class="flex items-center gap-3 shrink-0">
                                <span
                                    class="inline-flex items-center justify-center min-w-[2.5rem] h-10 rounded-xl text-sm font-black tabular-nums shadow-sm"
                                    :class="priorityBadgeClass(order.priority_score)"
                                >
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
                                <p v-if="canViewFinancials" class="text-sm font-bold tabular-nums text-slate-900">₱{{ Number(order.total_amount).toLocaleString() }}</p>
                                <p class="text-[11px] font-medium text-slate-400 mt-1">{{ formatWhen(order.created_at) }}</p>
                            </div>
                        </Link>
                    </div>
                    <div v-else class="py-16 flex flex-col items-center justify-center text-center">
                        <div class="h-12 w-12 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-600">No matching orders found.</p>
                        <p class="text-xs font-medium text-slate-400 mt-1">Try clearing your filters or checking a different date range.</p>
                    </div>
                </div>
            </section>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';

// MUST BE HERE: Register Chart.js components
Chart.register(...registerables);

// Setup standard chart defaults
Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";
Chart.defaults.color = '#64748b'; 

const props = defineProps({
    distributor: Object,
    canViewFinancials: { type: Boolean, default: true },
    filters: { type: Object, required: true },
    stats: Object,
    recentOrders: Array,
    dssWarning: { type: Object, default: null },
    inventory_alerts: { type: Object, default: () => ({ expiring_batches: [], low_stock_rows: [] }) },
    unreadAlerts: Array,
    charts: { type: Object, default: () => ({}) },
    order_pipeline: { type: Array, default: () => [] },
    inventory_pulse: { type: Object, default: () => ({ quantity_total: 0, reserved_total: 0 }) },
    restock_insights: { type: Object, default: () => ({}) },
    financial_kpis: { type: Object, default: () => ({}) },
    alert_center: { type: Array, default: () => [] },
    analytics: {
        type: Object,
        default: () => ({
            velocity_heatmap: { week_labels: [], rows: [] },
            demand_forecast: { labels: [], actual: [], projected: [], comparison: null },
            inventory_gauge: { needle: 50, zone: 'neutral', headline: '', days_cover: null },
        }),
    },
    pulse_baseline: { type: Object, default: () => ({ newest_order_id: 0 }) },
});

// State
const sumFrom = ref(props.filters.summary.from);
const sumTo = ref(props.filters.summary.to);
const revFrom = ref(props.filters.revenue.from);
const revTo = ref(props.filters.revenue.to);
const ordFrom = ref(props.filters.new_orders.from);
const ordTo = ref(props.filters.new_orders.to);
const topFrom = ref(props.filters.top_products.from);
const topTo = ref(props.filters.top_products.to);
const topMetric = ref(props.filters.top_products.metric);
const trendScope = ref(props.filters.trend_scope || 'mtd');

const livePending = ref(props.stats.pendingOrders);
const liveProcessing = ref(props.stats.processingOrders);
const toastMessage = ref('');
const lastSeenOrderId = ref(props.pulse_baseline?.newest_order_id ?? 0);
const queueFilterProductId = ref(null);

// Watchers
watch(() => props.stats, (s) => {
    livePending.value = s.pendingOrders;
    liveProcessing.value = s.processingOrders;
}, { deep: true });

watch(() => props.pulse_baseline, (p) => {
    if (p?.newest_order_id != null) lastSeenOrderId.value = p.newest_order_id;
}, { deep: true });

watch(() => props.filters, (f) => {
    sumFrom.value = f.summary.from;
    sumTo.value = f.summary.to;
    revFrom.value = f.revenue.from;
    revTo.value = f.revenue.to;
    ordFrom.value = f.new_orders.from;
    ordTo.value = f.new_orders.to;
    topFrom.value = f.top_products.from;
    topTo.value = f.top_products.to;
    topMetric.value = f.top_products.metric;
    trendScope.value = f.trend_scope || 'mtd';
}, { deep: true });

// Computeds
const heatmap = computed(() => props.analytics?.velocity_heatmap || { week_labels: [], rows: [] });
const gauge = computed(() => props.analytics?.inventory_gauge || { needle: 50, zone: 'neutral', headline: '', days_cover: null });

const gaugeRotation = computed(() => -90 + (Number(gauge.value.needle) || 0) / 100 * 180);

const filteredQueue = computed(() => {
    const list = props.recentOrders || [];
    const pid = queueFilterProductId.value;
    if (!pid) return list;
    return list.filter((o) => (o.product_ids || []).includes(pid));
});

const topMetricDescription = computed(() => {
    const m = props.charts?.top_metric ?? topMetric.value;
    if (m === 'revenue') return 'Measured by Sales Revenue';
    if (m === 'units') return 'Measured by Units Sold';
    return 'Measured by Total Orders';
});

// Helpers
function heatColor(t) {
    const x = Math.max(0, Math.min(1, t));
    return `hsl(${220 - x * 200} 85% ${45 + x * 15}%)`;
}

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

function setQueueFilter(id) {
    queueFilterProductId.value = id;
    document.getElementById('operational-queue')?.scrollIntoView({ behavior: 'smooth' });
}
function clearQueueFilter() { queueFilterProductId.value = null; }

function dashboardQuery(overrides = {}) {
    return {
        rev_from: overrides.rev_from ?? revFrom.value, rev_to: overrides.rev_to ?? revTo.value,
        ord_from: overrides.ord_from ?? ordFrom.value, ord_to: overrides.ord_to ?? ordTo.value,
        top_from: overrides.top_from ?? topFrom.value, top_to: overrides.top_to ?? topTo.value,
        top_metric: overrides.top_metric ?? topMetric.value, sum_from: overrides.sum_from ?? sumFrom.value,
        sum_to: overrides.sum_to ?? sumTo.value, trend_scope: overrides.trend_scope ?? trendScope.value,
    };
}

function navigate(params) { router.get('/owner/dashboard', params, { preserveState: true, preserveScroll: true, replace: true }); }
function setTrendScope(scope) { trendScope.value = scope; navigate(dashboardQuery({ trend_scope: scope })); }
function applyRevenue() { navigate(dashboardQuery({ rev_from: revFrom.value, rev_to: revTo.value })); }
function applyOrders() { navigate(dashboardQuery({ ord_from: ordFrom.value, ord_to: ordTo.value })); }
function applyTop() { navigate(dashboardQuery({ top_from: topFrom.value, top_to: topTo.value, top_metric: topMetric.value })); }

// ----------------------------------------------------------------------
// CHART LOGIC (WITH SETTIMEOUT & SUGGESTEDMAX SAFETIES)
// ----------------------------------------------------------------------
const dailyChartRef = ref(null);
const topProductsChartRef = ref(null);
const demandForecastRef = ref(null);

let dailyChart, topProductsChart, demandForecastChart;

function buildDemandChart() {
    const el = demandForecastRef.value;
    const df = props.analytics?.demand_forecast;
    if (!el || !df?.labels?.length) return;

    const datasets = [
        { label: df.mode === 'yoy' ? 'Selected window' : 'Actual Sales', data: df.actual, borderColor: '#0891b2', backgroundColor: 'rgba(8, 145, 178, 0.1)', fill: true, tension: 0.4, pointRadius: 2, pointHoverRadius: 5 },
    ];
    if (df.comparison && df.comparison.length) {
        datasets.push({ label: df.comparison_label || 'Comparison', data: df.comparison, borderColor: '#94a3b8', fill: false, tension: 0.4, pointRadius: 0, pointHoverRadius: 4, borderDash: [5, 5] });
    }
    datasets.push({ label: 'Expected Sales', data: df.projected, borderColor: '#8b5cf6', fill: false, tension: 0.3, pointRadius: 0, pointHoverRadius: 4, borderDash: [6, 4], spanGaps: true });

    if (demandForecastChart) {
        demandForecastChart.data.labels = df.labels;
        demandForecastChart.data.datasets = datasets;
        demandForecastChart.update();
    } else {
        demandForecastChart = new Chart(el, {
            type: 'line',
            data: { labels: df.labels, datasets },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, font: { weight: '600', size: 11 } } }, tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 10, cornerRadius: 8 } },
                scales: { y: { beginAtZero: true, suggestedMax: 10, grid: { color: '#f1f5f9', drawBorder: false } }, x: { grid: { display: false }, ticks: { maxRotation: 45, autoSkip: true, maxTicksLimit: 12, font: { size: 10 } } } },
            },
        });
    }
}

function buildCharts() {
    nextTick(() => {
        // Small delay guarantees Vue has rendered the <canvas> nodes before Chart.js targets them
        setTimeout(() => {
            buildDemandChart();


            // 2. Daily Orders Chart
            const daily = props.charts?.orders_series || [];
            if (dailyChartRef.value && daily.length) {
                if (dailyChart) {
                    dailyChart.data.labels = daily.map((d) => d.label);
                    dailyChart.data.datasets[0].data = daily.map((d) => d.count);
                    dailyChart.update();
                } else {
                    dailyChart = new Chart(dailyChartRef.value, {
                        type: 'line',
                        data: {
                            labels: daily.map((d) => d.label),
                            datasets: [{ label: 'Total Orders', data: daily.map((d) => d.count), borderColor: '#0f172a', backgroundColor: 'rgba(15, 23, 42, 0.05)', fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 5 }],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                            plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 10, cornerRadius: 8 } },
                            scales: {
                                y: { beginAtZero: true, suggestedMax: 5, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9', drawBorder: false } },
                                x: { grid: { display: false }, ticks: { maxRotation: 45, autoSkip: true, maxTicksLimit: 8, font: { size: 10 } } },
                            },
                        },
                    });
                }
            }

            // 3. Top Products Chart
            const top = props.charts?.top_products || [];
            if (topProductsChartRef.value && top.length) {
                const labels = top.map((p) => (p.name.length > 22 ? `${p.name.slice(0, 20)}…` : p.name));
                const data = top.map((p) => p.value);
                
                if (topProductsChart) {
                    topProductsChart.data.labels = labels;
                    topProductsChart.data.datasets[0].data = data;
                    topProductsChart.update();
                } else {
                    topProductsChart = new Chart(topProductsChartRef.value, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{ data: data, backgroundColor: 'rgba(6, 182, 212, 0.8)', hoverBackgroundColor: 'rgba(8, 145, 178, 1)', borderRadius: 4, barPercentage: 0.6 }],
                        },
                        options: {
                            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                            onClick: (_evt, elements) => {
                                if (!elements.length) return;
                                const row = top[elements[0].index];
                                if (row?.id) setQueueFilter(row.id);
                            },
                            plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 10, cornerRadius: 8, callbacks: { label(ctx) { return top[ctx.dataIndex]?.value_label || String(ctx.raw); } } } },
                            scales: {
                                x: { beginAtZero: true, suggestedMax: 10, grid: { color: '#f1f5f9', drawBorder: false }, ticks: { font: { size: 10 } } },
                                y: { grid: { display: false }, ticks: { font: { weight: '600', size: 11, color: '#334155' } } },
                            },
                        },
                    });
                }
            }
        }, 50); // Small 50ms buffer ensures smooth render
    });
}

// ----------------------------------------------------------------------
// POLLING (WITH VISIBILITY CHECK)
// ----------------------------------------------------------------------
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
    buildCharts();
    pollPulse();
    pulseTimer = setInterval(pollPulse, 30000);
    document.addEventListener("visibilitychange", handleVisibilityChange);
});

watch(() => [props.charts, props.canViewFinancials, props.analytics], buildCharts, { deep: true });

onBeforeUnmount(() => {
    if (dailyChart) dailyChart.destroy();
    if (topProductsChart) topProductsChart.destroy();
    if (demandForecastChart) demandForecastChart.destroy();
    if (pulseTimer) clearInterval(pulseTimer);
    document.removeEventListener("visibilitychange", handleVisibilityChange);
});
</script>