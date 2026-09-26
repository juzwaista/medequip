<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6 pb-28 sm:pb-24 text-ink min-w-0">
            <!-- Live order toast -->
            <Teleport to="body">
                <div
                    v-if="toastMessage"
                    class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 z-[100] max-w-none sm:max-w-sm rounded-card border border-line bg-white px-4 py-3 shadow-xl flex items-start gap-3"
                    role="status"
                >
                    <span class="mt-1.5 h-2 w-2 rounded-full bg-brand shrink-0"></span>
                    <div>
                        <p class="text-sm text-ink-soft">New order</p>
                        <p class="text-sm font-medium text-ink mt-0.5">{{ toastMessage }}</p>
                    </div>
                    <button type="button" class="text-ink-faint hover:text-ink text-lg leading-none ml-auto" @click="toastMessage = ''" aria-label="Dismiss">&times;</button>
                </div>
            </Teleport>

            <OnboardingWizardModal type="distributor" />

            <!-- Page header -->
            <header class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-ink">Overview</h1>
                    <p class="mt-1 text-ink-soft">{{ distributor.company_name }}</p>
                </div>
                <p class="inline-flex items-center gap-2 self-start rounded-control border border-line bg-white px-3 py-1.5 text-sm text-ink-soft sm:self-auto">
                    <span class="h-2 w-2 rounded-full bg-brand" aria-hidden="true"></span>
                    Updating live
                </p>
            </header>

            <!-- Missing address / location -->
            <div
                v-if="!distributor.latitude || !distributor.address"
                class="mb-6 flex items-start gap-3 rounded-card border border-amber-200 bg-amber-50 px-4 py-4"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-amber-900">Your store address is not set up yet</p>
                    <p class="mt-0.5 text-sm text-amber-900/80">Customers and couriers rely on your address for delivery and pickup. Set your business address and pin your exact location on the map.</p>
                </div>
                <Link href="/owner/profile/edit" class="shrink-0 inline-flex h-9 items-center rounded-control bg-amber-700 px-3.5 text-sm font-medium text-white transition-colors hover:bg-amber-800">
                    Set address
                </Link>
            </div>

            <!-- Getting started -->
            <section v-if="setupProgress < 100" class="mb-8 overflow-hidden rounded-card border border-line bg-white" aria-labelledby="getting-started">
                <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-4 sm:px-6">
                    <div>
                        <h2 id="getting-started" class="font-semibold text-ink">Finish setting up your shop</h2>
                        <p class="mt-0.5 text-sm text-ink-soft">Complete these steps to fully activate your shop.</p>
                    </div>
                    <p class="text-2xl font-semibold tabular-nums text-brand">{{ setupProgress }}%</p>
                </div>
                <div class="px-5 py-5 sm:px-6">
                    <div class="mb-5 h-1.5 w-full rounded-full bg-line" role="progressbar" :aria-valuenow="setupProgress" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-1.5 rounded-full bg-brand transition-all duration-500" :style="{ width: setupProgress + '%' }"></div>
                    </div>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <svg v-if="distributor.address && distributor.latitude" class="mt-0.5 h-5 w-5 flex-none text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span v-else class="mt-0.5 h-5 w-5 flex-none rounded-full border-2 border-line" aria-hidden="true"></span>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium" :class="distributor.address && distributor.latitude ? 'text-ink-faint line-through' : 'text-ink'">Set your store address</p>
                                <p v-if="!distributor.address || !distributor.latitude" class="text-sm text-ink-soft">Help couriers find your warehouse.</p>
                            </div>
                            <Link v-if="!distributor.address || !distributor.latitude" href="/owner/profile/edit" class="text-sm font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">Complete</Link>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg v-if="distributor.logo_path && distributor.cover_photo_path" class="mt-0.5 h-5 w-5 flex-none text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span v-else class="mt-0.5 h-5 w-5 flex-none rounded-full border-2 border-line" aria-hidden="true"></span>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium" :class="distributor.logo_path && distributor.cover_photo_path ? 'text-ink-faint line-through' : 'text-ink'">Upload your shop branding</p>
                                <p v-if="!distributor.logo_path || !distributor.cover_photo_path" class="text-sm text-ink-soft">Add a logo and cover photo to build trust.</p>
                            </div>
                            <Link v-if="!distributor.logo_path || !distributor.cover_photo_path" href="/owner/profile/edit" class="text-sm font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">Complete</Link>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg v-if="stats.totalProducts > 0" class="mt-0.5 h-5 w-5 flex-none text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span v-else class="mt-0.5 h-5 w-5 flex-none rounded-full border-2 border-line" aria-hidden="true"></span>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium" :class="stats.totalProducts > 0 ? 'text-ink-faint line-through' : 'text-ink'">Add your first product</p>
                                <p v-if="stats.totalProducts === 0" class="text-sm text-ink-soft">List your inventory to start selling.</p>
                            </div>
                            <Link v-if="stats.totalProducts === 0" href="/owner/inventory/create" class="text-sm font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">Complete</Link>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Today at a glance: one strip, not five cards -->
            <section class="mb-8" aria-label="Today at a glance">
                <div class="grid grid-cols-2 gap-px overflow-hidden rounded-card border border-line bg-line lg:grid-cols-5">
                    <Link href="/owner/orders?status=pending" class="group bg-white p-4 transition-colors hover:bg-mist sm:p-5">
                        <p class="text-sm text-ink-soft">Pending orders</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums" :class="livePending > 0 ? 'text-amber-800' : 'text-ink'">{{ livePending }}</p>
                        <p class="mt-1 text-sm text-ink-soft">waiting for review</p>
                    </Link>
                    <Link href="/owner/orders?status=approved" class="group bg-white p-4 transition-colors hover:bg-mist sm:p-5">
                        <p class="text-sm text-ink-soft">Packing and shipping</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-ink">{{ liveProcessing }}</p>
                        <p class="mt-1 text-sm text-ink-soft">orders in progress</p>
                    </Link>
                    <Link v-if="stats.rxBacklog > 0" href="/owner/orders?prescription=pending" class="group bg-white p-4 transition-colors hover:bg-mist sm:p-5">
                        <p class="text-sm text-ink-soft">Prescriptions</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-amber-800">{{ stats.rxBacklog }}</p>
                        <p class="mt-1 text-sm text-ink-soft">to review</p>
                    </Link>
                    <div v-else class="bg-white p-4 sm:p-5">
                        <p class="text-sm text-ink-soft">Prescriptions</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-ink">0</p>
                        <p class="mt-1 text-sm text-ink-soft">all clear</p>
                    </div>
                    <Link href="/owner/messages" class="group bg-white p-4 transition-colors hover:bg-mist sm:p-5">
                        <p class="text-sm text-ink-soft">Messages</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums" :class="stats.unreadMessages > 0 ? 'text-brand' : 'text-ink'">{{ stats.unreadMessages }}</p>
                        <p class="mt-1 text-sm text-ink-soft">unread from customers</p>
                    </Link>
                    <div class="col-span-2 bg-white p-4 sm:p-5 lg:col-span-1">
                        <p class="text-sm text-ink-soft">This month</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-ink">{{ stats.ordersMtd ?? 0 }}</p>
                        <p class="mt-1 text-sm text-ink-soft">orders received</p>
                    </div>
                </div>
            </section>

            <!-- Welcome state for new distributors -->
            <section v-if="stats.totalProducts === 0" class="mb-8">
                <div class="rounded-card border border-dashed border-brand-soft bg-brand-tint/40 p-8 text-center">
                    <h2 class="text-lg font-semibold text-ink">Welcome to your dashboard</h2>
                    <p class="mx-auto mb-5 mt-1 max-w-md text-ink-soft">Add your first product to start receiving orders. You can manage your inventory, pricing and stock from one place.</p>
                    <div class="flex flex-col justify-center gap-3 sm:flex-row">
                        <BaseButton href="/owner/inventory/create">Add your first product</BaseButton>
                        <BaseButton href="/owner/inventory" variant="secondary">View inventory</BaseButton>
                    </div>
                </div>
            </section>

            <!-- Alerts -->
            <section v-if="alert_center?.length" id="inventory-alerts" class="mb-8 scroll-mt-24" aria-labelledby="alerts-title">
                <h2 id="alerts-title" class="mb-3 text-lg font-semibold tracking-tight text-ink">Needs your attention</h2>
                <div class="space-y-3">
                    <Link
                        v-for="(b, idx) in alert_center"
                        :key="idx"
                        :href="b.href"
                        class="group flex flex-col gap-3 rounded-card border-l-4 border-y border-r bg-white px-5 py-4 transition-colors hover:bg-mist sm:flex-row sm:items-center sm:justify-between"
                        :class="b.level === 'critical' ? 'border-l-danger border-y-line border-r-line' : 'border-l-amber-500 border-y-line border-r-line'"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium" :class="b.level === 'critical' ? 'text-danger' : 'text-amber-800'">
                                {{ b.level === 'critical' ? 'Immediate action required' : 'Warning' }}
                            </p>
                            <p class="mt-0.5 font-semibold text-ink">{{ b.title }}</p>
                            <p class="mt-0.5 text-sm leading-snug text-ink-soft">{{ b.body }}</p>
                        </div>
                        <span
                            v-if="b.action"
                            class="inline-flex h-9 shrink-0 items-center justify-center rounded-control px-4 text-sm font-medium text-white transition-colors"
                            :class="b.level === 'critical' ? 'bg-danger group-hover:bg-red-800' : 'bg-amber-700 group-hover:bg-amber-800'"
                        >
                            {{ b.action }}
                        </span>
                    </Link>
                </div>
            </section>

            <!-- Earnings and order stages -->
            <section class="mb-10 grid gap-6 lg:grid-cols-5">
                <!-- Earnings -->
                <div class="lg:col-span-2" aria-labelledby="earnings-title">
                    <h2 id="earnings-title" class="mb-3 text-lg font-semibold tracking-tight text-ink">Your earnings</h2>
                    <div class="flex flex-col gap-4 rounded-card border border-line bg-white p-5 sm:p-6">
                        <template v-if="canViewFinancials">
                            <div class="rounded-card bg-ink p-5 text-white sm:p-6">
                                <p class="text-sm text-white/70">{{ earningsLabel }}</p>
                                <p class="mt-2 break-all text-4xl font-semibold tabular-nums tracking-tight">{{ formattedEarnings }}</p>
                                <p v-if="earningsComparison" class="mt-2 text-sm font-medium tabular-nums" :class="earningsDelta >= 0 ? 'text-brand-soft' : 'text-red-300'">
                                    <span v-if="earningsDelta >= 0">+</span>{{ earningsDelta.toFixed(1) }}% {{ earnings.comparison_label }}
                                </p>
                                <p class="mt-2 max-w-[90%] text-sm leading-snug text-white/60">Verified payments only, net after platform fee.</p>

                                <dl class="mt-5 flex items-start justify-between gap-4 border-t border-white/15 pt-4">
                                    <div>
                                        <dt class="text-sm text-white/70">Pending</dt>
                                        <dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ formatCurrency(earnings?.pending_earnings || 0) }}</dd>
                                    </div>
                                    <div class="text-right">
                                        <dt class="text-sm text-white/70">Transferred</dt>
                                        <dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ formatCurrency(earnings?.transferred_earnings || 0) }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Earnings period">
                                <button
                                    v-for="opt in earningsPresets"
                                    :key="opt.value"
                                    type="button"
                                    class="rounded-control px-3 py-1.5 text-sm font-medium transition-colors"
                                    :class="activeEarningsPreset === opt.value ? 'bg-brand text-white' : 'bg-mist text-ink-soft hover:bg-line'"
                                    :aria-pressed="activeEarningsPreset === opt.value"
                                    @click="setEarningsPreset(opt.value)"
                                >{{ opt.label }}</button>
                            </div>

                            <div v-if="activeEarningsPreset === 'custom'" class="flex flex-wrap items-center gap-2">
                                <input v-model="customFrom" type="date" aria-label="From date" class="h-9 rounded-control border border-line px-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-tint" />
                                <span class="text-sm text-ink-soft">to</span>
                                <input v-model="customTo" type="date" aria-label="To date" class="h-9 rounded-control border border-line px-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-tint" />
                                <BaseButton size="sm" @click="applyCustomEarnings">Apply</BaseButton>
                            </div>
                        </template>
                        <template v-else>
                            <div>
                                <p class="text-sm text-ink-soft">Orders this month</p>
                                <p class="mt-1 text-4xl font-semibold tabular-nums text-ink">{{ stats.ordersMtd ?? 0 }}</p>
                                <p class="mt-5 rounded-card border border-dashed border-line bg-mist p-4 text-center text-sm text-ink-soft">Revenue details are restricted to shop owners.</p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Order stages -->
                <div class="lg:col-span-3" aria-labelledby="stages-title">
                    <h2 id="stages-title" class="mb-3 text-lg font-semibold tracking-tight text-ink">Orders by stage</h2>
                    <div class="rounded-card border border-line bg-white p-5 sm:p-6">
                        <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <Link href="/owner/orders?status=pending" class="group rounded-card border border-amber-200 bg-amber-50 p-4 transition-colors hover:bg-amber-100/70">
                                <p class="text-sm text-amber-900">Needs your approval</p>
                                <p class="mt-1 text-3xl font-semibold tabular-nums text-amber-900">{{ livePending }}</p>
                                <p class="mt-2 text-sm font-medium text-amber-900 group-hover:underline underline-offset-2">Review and approve</p>
                            </Link>
                            <Link href="/owner/orders?status=approved" class="group rounded-card border border-brand-soft bg-brand-tint p-4 transition-colors hover:bg-brand-soft/40">
                                <p class="text-sm text-brand-dark">Pack and ready to ship</p>
                                <p class="mt-1 text-3xl font-semibold tabular-nums text-brand-dark">{{ liveProcessing }}</p>
                                <p class="mt-2 text-sm font-medium text-brand-dark group-hover:underline underline-offset-2">Continue packing</p>
                            </Link>
                        </div>

                        <p class="mb-2 text-sm font-medium text-ink">All stages</p>
                        <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
                            <li v-for="row in order_pipeline" :key="row.key">
                                <Link
                                    :href="row.href"
                                    class="flex h-full flex-col rounded-card border px-4 py-3 transition-colors"
                                    :class="pipelineAccent(row.key)"
                                >
                                    <span class="text-sm text-ink-soft">{{ row.label }}</span>
                                    <span class="mt-0.5 text-2xl font-semibold tabular-nums text-ink">{{ row.count }}</span>
                                    <span class="mt-1 line-clamp-2 text-sm leading-tight text-ink-soft">{{ row.description }}</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Inventory and stock health -->
            <section class="mb-10" aria-labelledby="stock-title">
                <h2 id="stock-title" class="mb-3 text-lg font-semibold tracking-tight text-ink">Inventory and stock health</h2>
                <div class="grid gap-px overflow-hidden rounded-card border border-line bg-line md:grid-cols-3">
                    <div class="bg-white p-5 sm:p-6">
                        <p class="text-sm text-ink-soft">Units in stock</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-ink">{{ inventory_pulse.quantity_total }}</p>
                        <p class="mt-1 text-sm text-ink-soft">across all your products</p>
                    </div>
                    <div class="bg-white p-5 sm:p-6">
                        <p class="text-sm text-ink-soft">Reserved for orders</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-ink">{{ inventory_pulse.reserved_total }}</p>
                        <Link href="/owner/orders?status=pending" class="mt-2 inline-block text-sm font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">View open orders</Link>
                    </div>
                    <div class="flex flex-col bg-white p-5 sm:p-6">
                        <p class="text-sm text-ink-soft">Products listed</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-ink">{{ stats.totalProducts }}</p>
                        <p v-if="stats.lowStockCount > 0" class="mt-2 self-start rounded-control border border-red-200 bg-red-50 px-2 py-0.5 text-sm font-medium text-red-900">
                            {{ stats.lowStockCount }} below reorder point
                        </p>
                        <Link href="/owner/inventory" class="mt-auto pt-2 text-sm font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2">Manage products</Link>
                    </div>
                </div>

                <!-- Restock recommendations -->
                <div class="mt-6 overflow-hidden rounded-card border border-line bg-white">
                    <div class="border-b border-line px-5 py-4 sm:px-6">
                        <h3 class="font-semibold text-ink">Restock recommendations</h3>
                        <p class="mt-0.5 text-sm text-ink-soft">Products that may need restocking soon.</p>
                    </div>

                    <div v-if="restock_insights?.recommendations?.length" class="overflow-x-auto touch-pan-x">
                        <table class="w-full min-w-[640px] border-collapse text-left">
                            <thead>
                                <tr class="border-b border-line bg-mist text-sm text-ink-soft">
                                    <th scope="col" class="px-6 py-3 font-medium">Product</th>
                                    <th scope="col" class="px-4 py-3 text-right font-medium">In stock</th>
                                    <th scope="col" class="px-4 py-3 text-right font-medium">Sold per day</th>
                                    <th scope="col" class="px-4 py-3 text-right font-medium">Runs out in</th>
                                    <th scope="col" class="px-6 py-3 text-right font-medium">Suggested restock</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line bg-white">
                                <tr v-for="rec in restock_insights.recommendations" :key="rec.id" class="group transition-colors hover:bg-mist/60">
                                    <td class="px-6 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <span v-if="rec.priority === 'high'" class="h-2 w-2 shrink-0 rounded-full bg-danger" title="High priority"></span>
                                            <span v-else-if="rec.priority === 'medium'" class="h-2 w-2 shrink-0 rounded-full bg-amber-500" title="Medium priority"></span>
                                            <span v-else class="h-2 w-2 shrink-0 rounded-full bg-brand" title="Low priority"></span>
                                            <Link :href="`/owner/inventory?search=${rec.product_id}`" class="truncate font-medium text-ink hover:text-brand hover:underline underline-offset-2" :title="rec.product_name">{{ rec.product_name }}</Link>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-medium tabular-nums text-ink">{{ rec.current_stock }}</td>
                                    <td class="px-4 py-3.5 text-right tabular-nums text-ink-soft">{{ Number(rec.avg_daily_sales).toFixed(1) }} units</td>
                                    <td class="px-4 py-3.5 text-right">
                                        <span class="inline-flex rounded-control border px-2 py-0.5 text-sm font-medium tabular-nums" :class="rec.days_until_stockout <= 5 ? 'border-red-200 bg-red-50 text-red-900' : 'border-amber-200 bg-amber-50 text-amber-900'">
                                            {{ rec.days_until_stockout }} days
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-right">
                                        <span class="inline-flex whitespace-nowrap rounded-control border border-brand-soft bg-brand-tint px-2.5 py-0.5 text-sm font-medium tabular-nums text-brand-dark">
                                            +{{ rec.recommended_quantity }} units
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center bg-white px-6 py-12 text-center">
                        <p class="font-semibold text-ink">Your stock levels look healthy</p>
                        <p class="mt-1 max-w-sm text-sm text-ink-soft">There are no urgent restock recommendations right now.</p>
                    </div>
                </div>
            </section>

            <!-- Priority orders -->
            <section id="operational-queue" class="mb-10 scroll-mt-24" aria-labelledby="queue-title">
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 id="queue-title" class="text-lg font-semibold tracking-tight text-ink">Priority orders</h2>
                        <p class="mt-0.5 text-sm text-ink-soft">Sorted by urgency. Critical-care and delayed orders come first.</p>
                    </div>
                    <button
                        v-if="queueFilterProductId"
                        type="button"
                        class="self-start rounded-control border border-line bg-white px-3 py-1.5 text-sm font-medium text-ink-soft transition-colors hover:text-danger"
                        @click="clearQueueFilter"
                    >
                        &times; Clear filter
                    </button>
                </div>

                <p v-if="queueFilterProductId" class="mb-3 flex items-center gap-2 rounded-card border border-brand-soft bg-brand-tint px-4 py-3 text-sm font-medium text-brand-dark">
                    Showing orders containing product ID #{{ queueFilterProductId }}
                </p>

                <div class="overflow-hidden rounded-card border border-line bg-white">
                    <div class="flex flex-col gap-1 border-b border-line bg-mist px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <span class="font-medium text-ink">Actionable orders</span>
                        <Link href="/owner/orders" class="py-1 text-sm font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2 touch-manipulation">View all orders</Link>
                    </div>
                    <ul v-if="filteredQueue.length" class="divide-y divide-line">
                        <li v-for="order in filteredQueue" :key="order.id">
                            <Link
                                :href="`/owner/orders/${order.order_number}`"
                                class="group flex min-h-[56px] flex-col gap-3 px-4 py-4 transition-colors hover:bg-mist touch-manipulation sm:flex-row sm:items-center sm:gap-4 sm:px-6"
                            >
                                <div class="flex shrink-0 items-center gap-3">
                                    <span class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-control text-sm font-semibold tabular-nums" :class="priorityBadgeClass(order.priority_score)">
                                        {{ order.priority_score }}
                                    </span>
                                    <span class="rounded-control border border-line bg-mist px-2 py-0.5 text-sm capitalize text-ink-soft">{{ order.status }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-ink group-hover:text-brand">{{ order.order_number }}</p>
                                    <p class="mt-0.5 truncate text-sm text-ink-soft">{{ order.customer?.name || 'Walk-in customer' }}</p>
                                    <p class="mt-1 flex items-center gap-1.5 text-sm text-ink-soft">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="order.priority_score >= 7 ? 'bg-danger' : 'bg-line'"></span>
                                        {{ order.priority_label }}
                                    </p>
                                </div>
                                <div class="shrink-0 text-left sm:min-w-[120px] sm:text-right">
                                    <p v-if="canViewFinancials" class="font-semibold tabular-nums text-ink">{{ formatCurrency(order.total_amount) }}</p>
                                    <p class="mt-0.5 text-sm text-ink-soft">{{ formatWhen(order.created_at) }}</p>
                                </div>
                            </Link>
                        </li>
                    </ul>
                    <div v-else class="flex flex-col items-center justify-center px-6 py-14 text-center">
                        <p class="font-semibold text-ink">No orders need attention right now</p>
                        <p class="mt-1 text-sm text-ink-soft">New orders will appear here automatically.</p>
                    </div>
                </div>
            </section>

            <!-- Insights -->
            <section v-if="canViewFinancials" class="mb-12">
                <Link href="/owner/insights" class="group flex items-center justify-between gap-4 rounded-card border border-line bg-white p-5 transition-colors hover:border-brand">
                    <div>
                        <p class="font-semibold text-ink">Insights</p>
                        <p class="mt-0.5 text-sm text-ink-soft">View sales trends, product performance, demand forecasts and inventory analytics.</p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-ink-faint transition-colors group-hover:text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </Link>
            </section>
        </div>
    </OwnerLayout>
</template>

<script setup>
import BaseButton from '@/Components/ui/BaseButton.vue';
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import OnboardingWizardModal from '@/Components/OnboardingWizardModal.vue';

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

const setupProgress = computed(() => {
    let completed = 0;
    const total = 3;
    
    if (props.distributor.address && props.distributor.latitude) completed++;
    if (props.distributor.logo_path && props.distributor.cover_photo_path) completed++;
    if (props.stats.totalProducts > 0) completed++;
    
    return Math.round((completed / total) * 100);
});

function pipelineAccent(key) {
    if (key === 'pending') return 'border-amber-200 bg-amber-50/40 text-amber-900 hover:border-amber-300 hover:bg-amber-50';
    if (key === 'approved' || key === 'packed') return 'border-brand-soft bg-brand-tint/40 text-brand-dark hover:border-brand-soft hover:bg-brand-tint';
    if (key === 'shipped') return 'border-brand-soft bg-brand-tint/40 text-brand-dark hover:border-brand-soft hover:bg-brand-tint';
    return 'border-line bg-white hover:border-line hover:bg-mist';
}

function priorityBadgeClass(score) {
    if (score >= 10) return 'bg-rose-600 text-white shadow-rose-200/50';
    if (score >= 7) return 'bg-orange-500 text-white shadow-orange-200/50';
    if (score >= 5) return 'bg-amber-400 text-ink shadow-amber-200/50';
    if (score >= 3) return 'bg-brand text-white shadow-sky-200/50';
    return 'bg-mist text-ink-soft border border-line';
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
