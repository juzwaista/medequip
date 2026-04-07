<template>
    <MainLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 w-full min-h-[calc(100dvh-6.5rem)] sm:min-h-[calc(100dvh-5.5rem)] pb-36 sm:pb-10 lg:pb-8">
            <!-- Breadcrumb -->
            <nav class="flex flex-wrap items-center gap-1.5 mb-6 text-xs sm:text-sm text-gray-600">
                <Link href="/products" class="hover:text-blue-600 transition">Products</Link>
                <span class="text-gray-400">/</span>
                <Link :href="`/category/${product.category.id}`" class="hover:text-blue-600 transition truncate max-w-[10rem] sm:max-w-none">
                    {{ product.category.name }}
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium truncate">{{ product.name }}</span>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 lg:items-stretch lg:min-h-[min(40rem,calc(100dvh-11rem))]">
                <!-- Gallery -->
                <div class="lg:col-span-6 space-y-3">
                    <div class="aspect-square w-full min-h-[18rem] sm:min-h-[20rem] rounded-xl bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center">
                        <img
                            v-if="activeImageUrl"
                            :src="activeImageUrl"
                            :alt="product.name"
                            class="w-full h-full object-contain"
                        />
                        <svg v-else class="h-20 w-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div v-if="galleryUrls.length > 1" class="flex gap-2 overflow-x-auto pb-1">
                        <button
                            v-for="(url, idx) in galleryUrls"
                            :key="idx"
                            type="button"
                            @click="activeImageIndex = idx"
                            class="flex-shrink-0 w-16 h-16 rounded-lg border-2 overflow-hidden transition"
                            :class="activeImageIndex === idx ? 'border-blue-500 ring-2 ring-blue-100' : 'border-gray-200 hover:border-gray-300'"
                        >
                            <img :src="url" alt="" class="w-full h-full object-cover" />
                        </button>
                    </div>
                </div>

                <!-- Buy box -->
                <div class="lg:col-span-6">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span
                            v-if="product.requires_prescription"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-900 border border-amber-200"
                        >
                            Prescription required
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-800 border border-blue-100">
                            {{ product.product_type === 'equipment' ? 'Equipment' : 'Consumable' }}
                        </span>
                        <span v-if="product.has_warranty" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-100">
                            {{ product.warranty_months }} mo warranty
                        </span>
                    </div>

                    <div class="flex flex-wrap items-start justify-between gap-3 gap-y-2">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight flex-1 min-w-0">{{ product.name }}</h1>
                        <button
                            v-if="canReportProduct"
                            type="button"
                            class="shrink-0 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-full p-2 transition-colors flex items-center justify-center -mt-1 -mr-2"
                            @click="reportModalOpen = true"
                            title="Report listing"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ product.brand }} · {{ product.model }}
                    </p>
                    <p v-if="product_review_summary?.avg" class="text-sm mt-2 flex flex-wrap items-center gap-2">
                        <span class="flex items-center gap-0.5" aria-hidden="true">
                            <template v-for="n in 5" :key="n">
                                <svg class="w-4 h-4" :class="n <= Math.round(product_review_summary.avg) ? 'text-amber-500' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </template>
                        </span>
                        <span class="text-gray-700 font-semibold">{{ product_review_summary.avg.toFixed(1) }}</span>
                        <span class="text-gray-500">({{ product_review_summary.count }} review{{ product_review_summary.count === 1 ? '' : 's' }})</span>
                    </p>

                    <!-- Seller inline info -->
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-gray-600 bg-gray-50/80 px-3 py-2 rounded-lg border border-gray-100">
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-500">Sold by</span>
                            <Link
                                v-if="product.distributor.slug"
                                :href="`/seller/${product.distributor.slug}`"
                                class="font-semibold text-blue-600 hover:text-blue-800 hover:underline"
                            >
                                {{ product.distributor.company_name }}
                            </Link>
                            <span v-else class="font-semibold text-gray-900">{{ product.distributor.company_name }}</span>
                            <span v-if="product.distributor.is_suspended" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 uppercase tracking-widest">
                                Suspended
                            </span>
                        </div>
                        <div v-if="!hide_seller_message_cta" class="flex items-center pl-3 border-l border-gray-200">
                            <Link
                                v-if="messaging?.start_url"
                                :href="messaging.start_url"
                                class="inline-flex items-center gap-1.5 font-medium text-blue-600 hover:text-blue-800 hover:underline"
                            >
                                <svg class="h-4 w-4 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                Message
                            </Link>
                            <p
                                v-else-if="page.props.auth?.user && !page.props.auth.user.email_verified_at"
                                class="text-xs text-gray-600"
                            >
                                <span class="text-amber-800">Verify your email</span> to message
                            </p>
                            <Link
                                v-else-if="!page.props.auth?.user"
                                href="/login"
                                class="inline-flex items-center gap-1.5 font-medium text-blue-600 hover:text-blue-800 hover:underline"
                            >
                                Log in to message
                            </Link>
                        </div>
                    </div>

                    <!-- Price block (compact) -->
                    <div class="mt-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-baseline gap-2">
                            <span class="text-2xl font-bold text-gray-900">₱{{ Number(effectiveRetail).toLocaleString() }}</span>
                            <span class="text-sm text-gray-500">Retail</span>
                        </div>
                        <div v-if="product.wholesale_price" class="mt-3 pt-3 border-t border-gray-100">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-semibold text-emerald-700">₱{{ Number(effectiveWholesale).toLocaleString() }}</span>
                                <span class="text-gray-600">wholesale</span>
                                <span class="text-xs text-gray-500">· min {{ product.wholesale_min_qty }} pcs</span>
                            </div>
                        </div>
                    </div>

                    <!-- Variations — Multi-group combinatorial -->
                    <div v-if="hasVariations && variationGroups.length > 0" class="mt-5 space-y-4">
                        <div v-for="(group, gi) in variationGroups" :key="gi">
                            <p class="text-sm font-medium text-gray-800 mb-2">{{ group.name }}</p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="val in group.values"
                                    :key="val"
                                    type="button"
                                    @click="selectGroupValue(gi, val)"
                                    :disabled="!isGroupValueAvailable(gi, val)"
                                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition"
                                    :class="groupSelections[gi] === val
                                        ? 'border-blue-600 bg-blue-50 text-blue-900'
                                        : !isGroupValueAvailable(gi, val)
                                            ? 'border-gray-200 text-gray-400 cursor-not-allowed bg-gray-50'
                                            : 'border-gray-300 text-gray-800 hover:border-blue-400'"
                                >
                                    {{ val }}
                                </button>
                            </div>
                        </div>
                        <p v-if="!allGroupsSelected" class="text-xs text-amber-700">Select all options to add to cart.</p>
                        <p v-else-if="matchedVariation" class="text-xs text-gray-500">{{ matchedVariation.available }} available</p>
                    </div>

                    <!-- Variations — Legacy flat buttons (single group, no variationGroups) -->
                    <div v-else-if="hasVariations" class="mt-5">
                        <p class="text-sm font-medium text-gray-800 mb-2">{{ variationOptionLabel }}</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="v in variationStocks"
                                :key="v.id"
                                type="button"
                                @click="selectedVariationId = v.id"
                                :disabled="v.available <= 0"
                                class="px-3 py-1.5 rounded-lg border text-sm font-medium transition"
                                :class="selectedVariationId === v.id
                                    ? 'border-blue-600 bg-blue-50 text-blue-900'
                                    : v.available <= 0
                                        ? 'border-gray-200 text-gray-400 cursor-not-allowed bg-gray-50'
                                        : 'border-gray-300 text-gray-800 hover:border-blue-400'"
                            >
                                {{ v.option_value }}
                                <span class="text-xs font-normal text-gray-500">({{ v.available }})</span>
                            </button>
                        </div>
                        <p v-if="!selectedVariationId" class="text-xs text-amber-700 mt-2">Select an option to add to cart.</p>
                    </div>

                    <!-- Stock -->
                    <p class="mt-4 text-sm">
                        <span class="font-medium text-gray-800">{{ hasVariations ? 'Total Stock:' : 'Stock:' }}</span>
                        <span :class="totalStock > 0 ? 'text-emerald-700' : 'text-red-600'" class="ml-1 font-semibold">
                            {{ totalStock > 0 ? `${totalStock} available` : 'Out of stock' }}
                        </span>
                    </p>



                    <!-- Suspension Banner -->
                    <div v-if="product.distributor.is_suspended" class="mt-4 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 flex items-start gap-3 shadow-sm">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div>
                            <span class="font-bold">Distributor is currently suspended.</span><br>
                            This product cannot be purchased at this time.
                        </div>
                    </div>

                        <!-- Unified CTA row: all screens -->
                        <div class="flex gap-2 items-center">
                            <!-- Qty stepper -->
                            <div class="inline-flex items-center border-2 border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm flex-shrink-0">
                                <button
                                    type="button"
                                    @click="bumpQty(-1)"
                                    :disabled="quantity <= 1 || lineAvailable <= 0"
                                    class="w-8 h-9 sm:w-10 sm:h-10 flex items-center justify-center text-gray-600 hover:bg-gray-50 disabled:opacity-40 text-base font-bold"
                                >−</button>
                                <input
                                    type="number"
                                    v-model.number="quantity"
                                    @input="sanitizeQuantityInput"
                                    min="1"
                                    :max="lineAvailable"
                                    class="w-10 sm:w-12 text-center text-sm font-bold py-1.5 border-x-2 border-gray-200 focus:outline-none focus:ring-0"
                                />
                                <button
                                    type="button"
                                    @click="bumpQty(1)"
                                    :disabled="quantity >= lineAvailable || lineAvailable <= 0"
                                    class="w-8 h-9 sm:w-10 sm:h-10 flex items-center justify-center text-gray-600 hover:bg-gray-50 disabled:opacity-40 text-base font-bold"
                                >+</button>
                            </div>

                            <!-- Add to Cart: icon-only on mobile, icon+text on sm+ -->
                            <button
                                type="button"
                                @click="addToCart"
                                :disabled="adding || lineAvailable <= 0 || cartDisabled || product.distributor.is_suspended"
                                class="flex items-center justify-center gap-1.5 rounded-xl border-2 border-blue-600 text-blue-600 font-bold hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all h-10 px-3 sm:px-4"
                                :title="adding ? 'Adding…' : 'Add to Cart'"
                            >
                                <svg v-if="adding" class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <svg v-else class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span class="hidden sm:inline text-sm">{{ adding ? 'Adding…' : 'Cart' }}</span>
                            </button>

                            <!-- Buy Now: always shows text -->
                            <button
                                type="button"
                                @click="buyNow"
                                :disabled="buyingNow || lineAvailable <= 0 || cartDisabled || product.distributor.is_suspended"
                                class="flex-1 flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed transition-all active:scale-95 h-10 px-3 sm:px-5"
                            >
                                <svg v-if="buyingNow" class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <svg v-else class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Buy Now
                            </button>
                        </div>

                </div>
            </div>

            <!-- Description -->

            <div class="mt-10 rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-3">About this item</h2>
                <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ product.description }}</p>

                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 text-sm">
                    <div class="rounded-lg bg-gray-50 p-3 border border-gray-100">
                        <dt class="text-gray-500">Brand</dt>
                        <dd class="font-semibold text-gray-900 mt-0.5">{{ product.brand }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 border border-gray-100">
                        <dt class="text-gray-500">Model</dt>
                        <dd class="font-semibold text-gray-900 mt-0.5">{{ product.model }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 border border-gray-100">
                        <dt class="text-gray-500">Category</dt>
                        <dd class="font-semibold text-gray-900 mt-0.5">{{ product.category.name }}</dd>
                    </div>
                    <div v-if="product.has_expiry" class="rounded-lg bg-gray-50 p-3 border border-gray-100">
                        <dt class="text-gray-500">Expiry</dt>
                        <dd class="font-semibold text-orange-700 mt-0.5">
                            {{ nearestExpiryDate ? new Date(nearestExpiryDate).toLocaleDateString() : 'Tracked per batch' }}
                        </dd>
                        <dd v-if="nearestBatchNumber" class="text-xs text-gray-500 mt-1">Batch {{ nearestBatchNumber }}</dd>
                    </div>
                    <div v-if="product.has_warranty" class="rounded-lg bg-gray-50 p-3 border border-gray-100">
                        <dt class="text-gray-500">Warranty</dt>
                        <dd class="font-semibold text-emerald-700 mt-0.5">{{ product.warranty_months }} months</dd>
                    </div>
                </dl>
            </div>

            <!-- Ratings and reviews -->
            <div class="mt-10 rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Ratings and reviews</h2>
                <div v-if="!product_reviews?.length" class="text-sm text-gray-600 py-4">
                    No ratings and reviews yet.
                </div>
                <ul v-else class="space-y-5 divide-y divide-gray-100">
                    <li
                        v-for="r in product_reviews"
                        :key="r.id"
                        class="pt-5 first:pt-0"
                    >
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="flex items-center gap-0.5" aria-hidden="true">
                                <template v-for="n in 5" :key="n">
                                    <svg class="w-3.5 h-3.5" :class="n <= r.stars ? 'text-amber-500' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </template>
                            </span>
                            <span class="text-sm font-semibold text-gray-900">{{ r.reviewer_name }}</span>
                            <span class="text-xs text-gray-400">{{ formatReviewDate(r.created_at) }}</span>
                        </div>
                        <p v-if="r.body" class="text-sm text-gray-700 whitespace-pre-line">{{ r.body }}</p>
                    </li>
                </ul>
            </div>

            <!-- Related / FBT -->
            <div v-if="relatedProducts.length" class="mt-10">
                <div class="mb-4">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        Frequently bought together
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Customers who bought this also purchased these items.</p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Link
                        v-for="related in relatedProducts"
                        :key="related.id"
                        :href="`/products/${related.id}`"
                        class="group relative rounded-xl border border-gray-200 bg-white overflow-hidden hover:shadow-md transition flex flex-col"
                    >
                        <div v-if="related.is_dss_recommendation" class="absolute top-2 left-2 z-10 bg-indigo-600 shadow-sm text-white text-[10px] uppercase tracking-wider font-bold px-2 py-1 rounded-md flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            System Pick
                        </div>
                        <div class="aspect-square bg-gray-50 flex items-center justify-center p-4">
                            <img
                                v-if="related.image_url"
                                :src="related.image_url"
                                :alt="related.name"
                                class="w-full h-full object-contain p-2"
                            />
                            <svg v-else class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="p-3">
                            <p class="text-sm font-medium text-gray-900 line-clamp-2 group-hover:text-blue-600">{{ related.name }}</p>
                            <p class="text-sm font-bold text-gray-900 mt-1">₱{{ Number(related.base_price).toLocaleString() }}</p>
                        </div>
                    </Link>
                </div>
            </div>
            <!-- Report listing modal -->
            <div
                v-if="reportModalOpen"
                class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 bg-black/50"
                role="dialog"
                aria-modal="true"
                aria-labelledby="report-listing-title"
                @click.self="reportModalOpen = false"
            >
                <div class="bg-white rounded-t-2xl sm:rounded-2xl shadow-xl w-full max-w-md p-5 sm:p-6 max-h-[90vh] overflow-y-auto" @click.stop>
                    <h2 id="report-listing-title" class="text-lg font-bold text-gray-900">Report this listing</h2>
                    <p class="text-sm text-gray-600 mt-1">Tell us what is wrong. Our moderation team will review it.</p>
                    <form class="mt-4 space-y-4" @submit.prevent="submitProductReport">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                            <select
                                v-model="reportForm.reason"
                                class="w-full rounded-lg border-gray-300 text-sm min-h-[44px]"
                                required
                            >
                                <option value="misleading">Misleading or inaccurate</option>
                                <option value="prohibited">Prohibited item</option>
                                <option value="counterfeit">Counterfeit or unsafe</option>
                                <option value="spam">Spam or duplicate</option>
                                <option value="wrong_category">Wrong category</option>
                                <option value="other">Other</option>
                            </select>
                            <p v-if="reportForm.errors.reason" class="text-xs text-red-600 mt-1">{{ reportForm.errors.reason }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Details (optional)</label>
                            <textarea
                                v-model="reportForm.details"
                                rows="3"
                                maxlength="2000"
                                class="w-full rounded-lg border-gray-300 text-sm"
                                placeholder="What should we know?"
                            />
                            <p v-if="reportForm.errors.details" class="text-xs text-red-600 mt-1">{{ reportForm.errors.details }}</p>
                        </div>
                        <div class="flex flex-col-reverse sm:flex-row gap-2 sm:justify-end pt-2">
                            <button
                                type="button"
                                class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                @click="reportModalOpen = false"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2.5 rounded-xl bg-rose-600 text-white text-sm font-bold hover:bg-rose-700 disabled:opacity-50"
                                :disabled="reportForm.processing"
                            >
                                {{ reportForm.processing ? 'Sending…' : 'Submit report' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link, usePage, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const page = usePage();

const props = defineProps({
    product: Object,
    product_review_summary: {
        type: Object,
        default: () => ({ avg: null, count: 0 }),
    },
    product_reviews: {
        type: Array,
        default: () => [],
    },
    relatedProducts: Array,
    totalStock: Number,
    availableStock: Number,
    hasVariations: {
        type: Boolean,
        default: false,
    },
    variationGroups: {
        type: Array,
        default: () => [],
    },
    variationStocks: {
        type: Array,
        default: () => [],
    },
    nearestExpiryDate: {
        type: String,
        default: null,
    },
    nearestBatchNumber: {
        type: String,
        default: null,
    },
    messaging: {
        type: Object,
        default: null,
    },
    hide_seller_message_cta: {
        type: Boolean,
        default: false,
    },
});

const quantity = ref(1);
const adding = ref(false);
const buyingNow = ref(false);
const activeImageIndex = ref(0);
const selectedVariationId = ref(null);
const reportModalOpen = ref(false);

const reportForm = useForm({
    reason: 'misleading',
    details: '',
});

const canReportProduct = computed(
    () => Boolean(page.props.auth?.user?.email_verified_at)
);

function submitProductReport() {
    reportForm.post(route('products.report', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            reportModalOpen.value = false;
            reportForm.reset();
            reportForm.clearErrors();
        },
    });
}

const isDistributorSuspended = computed(() => props.product.distributor.is_suspended);

const galleryUrls = computed(() => {
    const imgs = props.product.images || [];
    if (imgs.length) {
        return imgs.map((i) => i.url || `/storage/${i.image_path}`);
    }
    if (props.product.image_url) {
        return [props.product.image_url];
    }
    return [];
});

const activeImageUrl = computed(() => galleryUrls.value[activeImageIndex.value] || null);

function formatReviewDate(value) {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

// --- Multi-group combinatorial selection ---
const groupSelections = ref([]);

const isMultiGroup = computed(() => props.variationGroups.length > 0);

const allGroupsSelected = computed(() => {
    if (!isMultiGroup.value) return false;
    return props.variationGroups.every((_, i) => groupSelections.value[i] != null);
});

const matchedVariation = computed(() => {
    if (!allGroupsSelected.value) return null;
    const combo = {};
    props.variationGroups.forEach((g, i) => { combo[g.name] = groupSelections.value[i]; });

    return props.variationStocks.find(v => {
        if (!v.combination) return false;
        return props.variationGroups.every(g => v.combination[g.name] === combo[g.name]);
    }) || null;
});

function selectGroupValue(gi, val) {
    const newSel = [...groupSelections.value];
    newSel[gi] = newSel[gi] === val ? null : val;
    groupSelections.value = newSel;
}

function isGroupValueAvailable(gi, val) {
    const partialCombo = {};
    props.variationGroups.forEach((g, i) => {
        if (i === gi) partialCombo[g.name] = val;
        else if (groupSelections.value[i] != null) partialCombo[g.name] = groupSelections.value[i];
    });

    return props.variationStocks.some(v => {
        if (!v.combination || v.available <= 0) return false;
        return Object.entries(partialCombo).every(([k, pv]) => v.combination[k] === pv);
    });
}

watch(matchedVariation, (v) => {
    selectedVariationId.value = v ? v.id : null;
});

watch(() => props.variationGroups, (groups) => {
    groupSelections.value = groups.map(() => null);
}, { immediate: true });

// --- End multi-group ---

const selectedVariation = computed(() => {
    if (!props.hasVariations || !selectedVariationId.value) return null;
    return props.variationStocks.find((v) => v.id === selectedVariationId.value) || null;
});

const priceAdjustment = computed(() => (selectedVariation.value ? Number(selectedVariation.value.price_adjustment) : 0));

const effectiveRetail = computed(() => Number(props.product.base_price) + priceAdjustment.value);
const effectiveWholesale = computed(() =>
    props.product.wholesale_price ? Number(props.product.wholesale_price) + priceAdjustment.value : null
);

const variationOptionLabel = computed(() => {
    if (!props.variationStocks.length) return 'Options';
    return props.variationStocks[0].option_name || 'Option';
});

const lineAvailable = computed(() => {
    if (props.hasVariations) {
        if (isMultiGroup.value) {
            return matchedVariation.value ? matchedVariation.value.available : 0;
        }
        return selectedVariation.value ? selectedVariation.value.available : 0;
    }
    return props.availableStock;
});

watch(
    () => selectedVariationId.value,
    () => {
        quantity.value = 1;
        sanitizeQuantityInput();
    }
);

watch(
    () => props.variationStocks,
    (rows) => {
        if (!props.hasVariations || isMultiGroup.value) return;
        const first = rows.find((r) => r.available > 0);
        selectedVariationId.value = first ? first.id : null;
    },
    { immediate: true }
);

const cartDisabled = computed(() => {
    if (!props.hasVariations) return false;
    if (isMultiGroup.value) return !allGroupsSelected.value || !matchedVariation.value;
    return !selectedVariationId.value;
});

const sanitizeQuantityInput = () => {
    const parsed = Number(quantity.value);
    if (!Number.isFinite(parsed) || parsed < 1) {
        quantity.value = 1;
        return;
    }
    if (lineAvailable.value && parsed > lineAvailable.value) {
        quantity.value = lineAvailable.value;
    }
};

const bumpQty = (delta) => {
    quantity.value = Math.max(1, Math.min(lineAvailable.value || 1, quantity.value + delta));
};

const addToCart = () => {
    sanitizeQuantityInput();
    if (cartDisabled.value || lineAvailable.value <= 0) return;

    adding.value = true;
    const payload = {
        product_id: props.product.id,
        quantity: quantity.value,
    };
    if (props.hasVariations && selectedVariationId.value) {
        payload.product_variation_id = selectedVariationId.value;
    }

    router.post('/cart/add', payload, {
        preserveScroll: true,
        onFinish: () => {
            adding.value = false;
        },
    });
};

const buyNow = async () => {
    sanitizeQuantityInput();
    if (cartDisabled.value || lineAvailable.value <= 0) return;

    buyingNow.value = true;
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const payload = { product_id: props.product.id, quantity: quantity.value };
        if (props.hasVariations && selectedVariationId.value) {
            payload.product_variation_id = selectedVariationId.value;
        }
        await window.axios.post('/cart/add', payload, {
            headers: { 'X-CSRF-TOKEN': token }
        });
        router.visit('/checkout');
    } catch (e) {
        if (e?.response?.status === 419) {
            window.location.reload();
        } else {
            alert(e?.response?.data?.message || 'Could not process. Please try again.');
            buyingNow.value = false;
        }
    }
};
</script>
