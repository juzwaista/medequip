<template>
    <MainLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 w-full min-h-[calc(100dvh-6.5rem)] sm:min-h-[calc(100dvh-5.5rem)] pb-36 sm:pb-10 lg:pb-8">
            <!-- Breadcrumb -->
            <nav class="flex flex-wrap items-center gap-1.5 mb-6 text-sm text-ink-soft" aria-label="Breadcrumb">
                <Link href="/products" class="hover:text-brand hover:underline underline-offset-2">Products</Link>
                <span class="text-ink-faint" aria-hidden="true">/</span>
                <Link :href="`/products?category=${product.category.id}`" class="hover:text-brand hover:underline underline-offset-2 truncate max-w-[10rem] sm:max-w-none">
                    {{ product.category.name }}
                </Link>
                <span class="text-ink-faint" aria-hidden="true">/</span>
                <span class="text-ink font-medium truncate">{{ product.name }}</span>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 lg:items-stretch lg:min-h-[min(40rem,calc(100dvh-11rem))]">
                <!-- Gallery -->
                <div class="lg:col-span-6 space-y-3">
                    <div class="aspect-square w-full min-h-[18rem] sm:min-h-[20rem] rounded-card bg-[#F7FAFA] border border-line overflow-hidden flex items-center justify-center">
                        <img
                            v-if="activeImageUrl"
                            :src="activeImageUrl"
                            :alt="product.name"
                            class="w-full h-full object-contain mix-blend-multiply"
                        />
                        <svg v-else class="h-20 w-20 text-ink-faint/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-label="No image">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div v-if="galleryUrls.length > 1" class="flex gap-2 overflow-x-auto pb-1">
                        <button
                            v-for="(url, idx) in galleryUrls"
                            :key="idx"
                            type="button"
                            @click="activeImageIndex = idx"
                            :aria-label="`Show image ${idx + 1}`"
                            :aria-current="activeImageIndex === idx ? 'true' : undefined"
                            class="flex-shrink-0 w-16 h-16 rounded-control border-2 overflow-hidden bg-[#F7FAFA] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                            :class="activeImageIndex === idx ? 'border-brand' : 'border-line hover:border-ink-faint'"
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
                            class="inline-flex items-center px-2 py-0.5 rounded-control text-sm font-medium bg-amber-50 text-amber-900 border border-amber-200"
                        >
                            Prescription required
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-control text-sm font-medium bg-white text-ink-soft border border-line">
                            {{ product.product_type === 'equipment' ? 'Equipment' : 'Consumable' }}
                        </span>
                        <span v-if="product.has_warranty" class="inline-flex items-center px-2 py-0.5 rounded-control text-sm font-medium bg-brand-tint text-brand-dark border border-brand-soft">
                            {{ product.warranty_months }} mo warranty
                        </span>
                    </div>

                    <div class="flex flex-wrap items-start justify-between gap-3 gap-y-2">
                        <h1 class="text-2xl sm:text-[28px] font-semibold tracking-tight text-ink leading-tight flex-1 min-w-0">{{ product.name }}</h1>
                        <button
                            v-if="canReportProduct"
                            type="button"
                            class="shrink-0 text-ink-faint hover:text-danger hover:bg-red-50 rounded-control p-2 transition-colors flex items-center justify-center -mt-1 -mr-2"
                            @click="reportModalOpen = true"
                            title="Report listing"
                            aria-label="Report listing"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </button>
                    </div>
                    <p class="text-ink-soft mt-1">
                        {{ [product.brand, product.model].filter(Boolean).join(', ') }}
                    </p>
                    <p v-if="product_review_summary?.avg" class="text-sm mt-2 flex flex-wrap items-center gap-2">
                        <span class="flex items-center gap-0.5" aria-hidden="true">
                            <template v-for="n in 5" :key="n">
                                <svg class="w-4 h-4" :class="n <= Math.round(product_review_summary.avg) ? 'text-amber-600' : 'text-line'" fill="currentColor" viewBox="0 0 20 20"><path d="M10 1.8l2.4 5 5.5.7-4 3.8 1 5.4L10 14l-4.9 2.7 1-5.4-4-3.8 5.5-.7z"/></svg>
                            </template>
                        </span>
                        <span class="text-ink font-semibold tabular-nums">{{ product_review_summary.avg.toFixed(1) }}</span>
                        <span class="text-ink-soft">({{ product_review_summary.count }} review{{ product_review_summary.count === 1 ? '' : 's' }})</span>
                    </p>

                    <!-- Seller inline info -->
                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-soft bg-white px-3 py-2.5 rounded-card border border-line">
                        <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                            <span>Sold by</span>
                            <SellerMark
                                :name="product.distributor.company_name"
                                :verified="!!product.distributor.is_verified"
                                :href="product.distributor.slug ? `/seller/${product.distributor.slug}` : null"
                            />
                            <span v-if="product.distributor.is_suspended" class="inline-flex items-center px-1.5 py-0.5 rounded-control text-xs font-medium bg-red-50 text-danger border border-red-200">
                                Suspended
                            </span>
                        </div>
                        <div v-if="!hide_seller_message_cta" class="flex items-center sm:pl-4 sm:border-l border-line">
                            <Link
                                v-if="messaging?.start_url"
                                :href="messaging.start_url"
                                class="inline-flex items-center gap-1.5 font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                Message seller
                            </Link>
                            <p
                                v-else-if="page.props.auth?.user && !page.props.auth.user.email_verified_at"
                                class="text-sm text-ink-soft"
                            >
                                <span class="text-amber-800">Verify your email</span> to message the seller
                            </p>
                            <Link
                                v-else-if="!page.props.auth?.user"
                                href="/login"
                                class="inline-flex items-center gap-1.5 font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2"
                            >
                                Log in to message
                            </Link>
                        </div>
                    </div>

                    <!-- Price block -->
                    <div class="mt-5 rounded-card border border-line bg-white p-4">
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <span class="text-3xl font-semibold tracking-tight tabular-nums text-ink">₱{{ Number(effectiveRetail).toLocaleString() }}</span>
                            <span class="text-sm text-ink-soft">Retail<template v-if="linePack > 1 || lineUnitLabel !== 'piece'">, per {{ lineUnitLabel }}<template v-if="linePack > 1"> ({{ linePack }} pcs)</template></template></span>
                        </div>
                        <div v-if="product.wholesale_price" class="mt-3 pt-3 border-t border-line">
                            <template v-if="isApprovedBusiness">
                                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1 text-sm">
                                    <span class="font-semibold text-brand tabular-nums">₱{{ Number(effectiveWholesale).toLocaleString() }}</span>
                                    <span class="text-ink-soft">wholesale</span>
                                    <span class="text-ink-soft">from {{ product.wholesale_min_qty }} pcs<template v-if="linePack > 1"> ({{ wholesaleMinUnits }} {{ pluralize(lineUnitLabel, wholesaleMinUnits) }})</template>, counted across all pack sizes</span>
                                </div>
                            </template>
                            <template v-else>
                                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <span class="text-ink-soft">Wholesale pricing is available for verified businesses</span>
                                    <Link href="/business-account/apply" class="text-brand font-medium hover:underline underline-offset-2">Upgrade to a business account</Link>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Variations: multi-group combinatorial -->
                    <div v-if="hasVariations && variationGroups.length > 0" class="mt-5 space-y-4">
                        <div v-for="(group, gi) in variationGroups" :key="gi">
                            <p class="text-sm font-semibold text-ink mb-2">{{ group.name }}</p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="val in group.values"
                                    :key="val"
                                    type="button"
                                    @click="selectGroupValue(gi, val)"
                                    :disabled="!isGroupValueAvailable(gi, val)"
                                    class="px-3 py-1.5 rounded-control border text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                    :class="groupSelections[gi] === val
                                        ? 'border-brand bg-brand-tint text-ink'
                                        : !isGroupValueAvailable(gi, val)
                                            ? 'border-line text-ink-faint cursor-not-allowed bg-mist'
                                            : 'border-line bg-white text-ink hover:border-brand'"
                                >
                                    {{ val }}
                                </button>
                            </div>
                        </div>
                        <p v-if="!allGroupsSelected" class="text-sm text-amber-800">Select all options to add to cart.</p>
                        <p v-else-if="matchedVariation" class="text-sm text-ink-soft">{{ matchedVariation.available }} available</p>
                    </div>

                    <!-- Variations: legacy flat buttons (single group, no variationGroups) -->
                    <div v-else-if="hasVariations" class="mt-5">
                        <p class="text-sm font-semibold text-ink mb-2">{{ variationOptionLabel }}</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="v in variationStocks"
                                :key="v.id"
                                type="button"
                                @click="selectedVariationId = v.id"
                                :disabled="v.available <= 0"
                                class="px-3 py-1.5 rounded-control border text-sm font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                :class="selectedVariationId === v.id
                                    ? 'border-brand bg-brand-tint text-ink'
                                    : v.available <= 0
                                        ? 'border-line text-ink-faint cursor-not-allowed bg-mist'
                                        : 'border-line bg-white text-ink hover:border-brand'"
                            >
                                {{ v.option_value }}
                                <span class="text-sm font-normal text-ink-soft tabular-nums">({{ v.available }})</span>
                            </button>
                        </div>
                        <p v-if="!selectedVariationId" class="text-sm text-amber-800 mt-2">Select an option to add to cart.</p>
                    </div>

                    <!-- Stock -->
                    <p class="mt-4 text-sm">
                        <span class="font-semibold text-ink">{{ hasVariations ? 'Total stock' : 'Stock' }}</span>
                        <span :class="totalStock > 0 ? 'text-brand' : 'text-danger'" class="ml-1.5 font-medium tabular-nums">
                            <!-- Options in different pack sizes can't be added as "units", so total them in pieces -->
                            {{ totalStock > 0 ? (hasMixedPacks ? `${availablePieces} pcs available in total` : `${totalStock} available`) : 'Out of stock' }}
                        </span>
                    </p>

                    <!-- Suspension banner -->
                    <div v-if="product.distributor.is_suspended" class="mt-4 rounded-card bg-red-50 border border-red-200 p-4 text-sm text-red-900 flex items-start gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div>
                            <span class="font-semibold">This seller is suspended.</span><br>
                            This product can't be purchased right now.
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-4">
                        <!-- Wholesale savings -->
                        <div v-if="wholesaleSavings" class="mt-4 bg-brand-tint border border-brand-soft rounded-card p-3">
                            <p class="text-sm font-medium text-ink leading-snug">
                                You save <span class="font-semibold text-brand-dark tabular-nums">₱{{ Number(wholesaleSavings.total).toLocaleString() }}</span> ({{ wholesaleSavings.percentage }}%) with wholesale pricing on this order.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 items-center pt-2">
                            <!-- Qty stepper -->
                            <div class="inline-flex items-center border border-line rounded-control overflow-hidden bg-white flex-shrink-0" role="group" aria-label="Quantity">
                                <button
                                    type="button"
                                    @click="bumpQty(-1)"
                                    :disabled="quantity <= 1 || lineAvailable <= 0"
                                    class="w-10 h-10 flex items-center justify-center text-ink-soft hover:bg-mist disabled:opacity-40 text-lg"
                                    aria-label="Decrease quantity"
                                >−</button>
                                <input
                                    type="number"
                                    v-model.number="quantity"
                                    @input="sanitizeQuantityInput"
                                    min="1"
                                    :max="lineAvailable"
                                    aria-label="Quantity"
                                    class="w-12 h-10 text-center font-semibold tabular-nums text-ink border-0 border-x border-line focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-tint"
                                />
                                <button
                                    type="button"
                                    @click="bumpQty(1)"
                                    :disabled="quantity >= lineAvailable || lineAvailable <= 0"
                                    class="w-10 h-10 flex items-center justify-center text-ink-soft hover:bg-mist disabled:opacity-40 text-lg"
                                    aria-label="Increase quantity"
                                >+</button>
                            </div>

                            <!-- Add to cart -->
                            <button
                                type="button"
                                @click="addToCart"
                                :disabled="adding || lineAvailable <= 0 || cartDisabled || product.distributor.is_suspended"
                                class="flex items-center justify-center gap-1.5 rounded-control border border-brand text-brand font-medium bg-white hover:bg-brand hover:text-white transition-colors h-10 px-3 sm:px-4 disabled:cursor-not-allowed disabled:border-line disabled:bg-mist disabled:text-ink-faint focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                :title="adding ? 'Adding…' : 'Add to cart'"
                                aria-label="Add to cart"
                            >
                                <svg v-if="adding" class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <svg v-else class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span class="hidden sm:inline">{{ adding ? 'Adding…' : 'Add to cart' }}</span>
                            </button>

                            <!-- Request a quote -->
                            <button
                                type="button"
                                @click="openRfqModal"
                                :disabled="product.distributor.is_suspended"
                                class="flex items-center justify-center gap-1.5 rounded-control border border-line text-ink font-medium bg-white hover:bg-mist transition-colors h-10 px-3 sm:px-4 shrink-0 disabled:cursor-not-allowed disabled:text-ink-faint focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                title="Request a quote"
                                aria-label="Request a quote"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <span class="hidden sm:inline">Request quote</span>
                            </button>

                            <!-- Buy now: primary action, full width on phones -->
                            <button
                                type="button"
                                @click="buyNow"
                                :disabled="buyingNow || lineAvailable <= 0 || cartDisabled || product.distributor.is_suspended"
                                class="flex-1 flex items-center justify-center rounded-control border border-transparent bg-brand hover:bg-brand-dark text-white font-medium h-10 px-5 min-w-[7rem] max-sm:w-full max-sm:basis-full transition-colors disabled:cursor-not-allowed disabled:border-line disabled:bg-mist disabled:text-ink-faint disabled:hover:bg-mist focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                            >
                                Buy now
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="mt-10 rounded-card border border-line bg-white p-5 sm:p-6">
                <h2 class="text-lg font-semibold tracking-tight text-ink mb-3">About this item</h2>
                <p class="text-ink-soft leading-relaxed whitespace-pre-line max-w-[75ch]">{{ product.description }}</p>

                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 text-sm">
                    <div class="rounded-control bg-mist p-3">
                        <dt class="text-ink-soft">Brand</dt>
                        <dd class="font-semibold text-ink mt-0.5">{{ product.brand }}</dd>
                    </div>
                    <div class="rounded-control bg-mist p-3">
                        <dt class="text-ink-soft">Model</dt>
                        <dd class="font-semibold text-ink mt-0.5">{{ product.model }}</dd>
                    </div>
                    <div class="rounded-control bg-mist p-3">
                        <dt class="text-ink-soft">Category</dt>
                        <dd class="font-semibold text-ink mt-0.5">{{ product.category.name }}</dd>
                    </div>
                    <div v-if="product.has_expiry" class="rounded-control bg-mist p-3">
                        <dt class="text-ink-soft">Expiry</dt>
                        <dd class="font-semibold text-amber-800 mt-0.5">
                            {{ nearestExpiryDate ? new Date(nearestExpiryDate).toLocaleDateString() : 'Tracked per batch' }}
                        </dd>
                        <dd v-if="timeBeforeExpiry" class="text-xs font-medium text-amber-900 bg-amber-100 px-1.5 py-0.5 rounded-control mt-1 inline-block">
                            {{ timeBeforeExpiry }}
                        </dd>
                        <dd v-if="nearestBatchNumber" class="text-sm text-ink-soft mt-1">Batch {{ nearestBatchNumber }}</dd>
                    </div>
                    <div v-if="product.has_warranty" class="rounded-control bg-mist p-3">
                        <dt class="text-ink-soft">Warranty</dt>
                        <dd class="font-semibold text-brand mt-0.5">{{ product.warranty_months }} months</dd>
                    </div>
                </dl>
            </div>

            <!-- Ratings and reviews -->
            <div class="mt-6 rounded-card border border-line bg-white p-5 sm:p-6">
                <h2 class="text-lg font-semibold tracking-tight text-ink mb-4">Ratings and reviews</h2>
                <div v-if="!product_reviews?.length" class="text-ink-soft py-2">
                    No ratings or reviews yet.
                </div>
                <ul v-else class="space-y-5 divide-y divide-line">
                    <li
                        v-for="r in product_reviews"
                        :key="r.id"
                        class="pt-5 first:pt-0"
                    >
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="flex items-center gap-0.5" aria-hidden="true">
                                <template v-for="n in 5" :key="n">
                                    <svg class="w-3.5 h-3.5" :class="n <= r.stars ? 'text-amber-600' : 'text-line'" fill="currentColor" viewBox="0 0 20 20"><path d="M10 1.8l2.4 5 5.5.7-4 3.8 1 5.4L10 14l-4.9 2.7 1-5.4-4-3.8 5.5-.7z"/></svg>
                                </template>
                            </span>
                            <span class="text-sm font-semibold text-ink">{{ r.reviewer_name }}</span>
                            <span class="text-sm text-ink-faint">{{ formatReviewDate(r.created_at) }}</span>
                        </div>
                        <p v-if="r.body" class="text-ink-soft whitespace-pre-line max-w-[75ch]">{{ r.body }}</p>
                    </li>
                </ul>
            </div>

            <!-- Related / frequently bought together -->
            <div v-if="relatedProducts.length" class="mt-10">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold tracking-tight text-ink">Frequently bought together</h2>
                    <p class="text-sm text-ink-soft mt-0.5">Customers who bought this also purchased these items.</p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Link
                        v-for="related in relatedProducts"
                        :key="related.id"
                        :href="`/products/${related.slug}`"
                        class="group relative rounded-card border border-line bg-white overflow-hidden hover:border-brand hover:shadow-[0_0_0_1px_#0B6E6B] transition-shadow flex flex-col"
                    >
                        <div v-if="related.is_dss_recommendation" class="absolute top-2 left-2 z-10 rounded-control border border-[#B9C7DA] bg-white px-2 py-0.5 text-xs font-medium text-seal">
                            System pick
                        </div>
                        <div class="relative aspect-square shrink-0 overflow-hidden bg-[#F7FAFA] border-b border-line flex items-center justify-center">
                            <img
                                v-if="related.image_url"
                                :src="related.image_url"
                                :alt="related.name"
                                class="absolute inset-0 w-full h-full object-contain p-4 mix-blend-multiply"
                            />
                            <svg v-else class="h-10 w-10 text-ink-faint/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-label="No image">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="p-3">
                            <p class="font-medium text-ink line-clamp-2 group-hover:text-brand">{{ related.name }}</p>
                            <p class="font-semibold text-ink mt-1 tabular-nums">₱{{ Number(related.base_price).toLocaleString() }}</p>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Report listing modal -->
            <div
                v-if="reportModalOpen"
                class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 bg-ink/50"
                role="dialog"
                aria-modal="true"
                aria-labelledby="report-listing-title"
                @click.self="reportModalOpen = false"
            >
                <div class="bg-white rounded-t-card sm:rounded-card shadow-xl w-full max-w-md p-5 sm:p-6 max-h-[90vh] overflow-y-auto" @click.stop>
                    <h2 id="report-listing-title" class="text-lg font-semibold text-ink">Report this listing</h2>
                    <p class="text-ink-soft mt-1">Tell us what is wrong. Our moderation team will review it.</p>
                    <form class="mt-4 space-y-4" @submit.prevent="submitProductReport">
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1">Reason</label>
                            <select
                                v-model="reportForm.reason"
                                class="block w-full h-11 rounded-control border border-line bg-white px-3 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-tint"
                                required
                            >
                                <option value="misleading">Misleading or inaccurate</option>
                                <option value="prohibited">Prohibited item</option>
                                <option value="counterfeit">Counterfeit or unsafe</option>
                                <option value="spam">Spam or duplicate</option>
                                <option value="wrong_category">Wrong category</option>
                                <option value="other">Other</option>
                            </select>
                            <p v-if="reportForm.errors.reason" class="text-sm text-danger mt-1">{{ reportForm.errors.reason }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1">Details (optional)</label>
                            <textarea
                                v-model="reportForm.details"
                                rows="3"
                                maxlength="2000"
                                class="block w-full rounded-control border border-line bg-white px-3 py-2 text-ink placeholder:text-ink-faint focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-tint"
                                placeholder="What should we know?"
                            />
                            <p v-if="reportForm.errors.details" class="text-sm text-danger mt-1">{{ reportForm.errors.details }}</p>
                        </div>
                        <div class="flex flex-col-reverse sm:flex-row gap-2 sm:justify-end pt-2">
                            <BaseButton variant="ghost" @click="reportModalOpen = false">Cancel</BaseButton>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center h-10 px-4 rounded-control bg-danger text-white font-medium hover:bg-red-800 transition-colors disabled:opacity-50"
                                :disabled="reportForm.processing"
                            >
                                {{ reportForm.processing ? 'Sending…' : 'Submit report' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Business account upsell modal -->
            <div
                v-if="b2bUpsellModalOpen"
                class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 bg-ink/50"
                role="dialog"
                aria-modal="true"
                aria-labelledby="b2b-upsell-title"
                @click.self="b2bUpsellModalOpen = false"
            >
                <div class="bg-white rounded-t-card sm:rounded-card shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
                    <h2 id="b2b-upsell-title" class="text-xl font-semibold tracking-tight text-ink mb-2">Quotes and wholesale need a business account</h2>
                    <p class="text-ink-soft mb-6">
                        Requesting quotes and wholesale pricing is available to verified business accounts. Applying is free.
                    </p>

                    <div class="flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
                        <BaseButton variant="ghost" @click="b2bUpsellModalOpen = false">Maybe later</BaseButton>
                        <BaseButton href="/business-account/apply">Apply for a business account</BaseButton>
                    </div>
                </div>
            </div>

            <!-- Request quote modal -->
            <div
                v-if="rfqModalOpen"
                class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 bg-ink/50"
                role="dialog"
                aria-modal="true"
                aria-labelledby="rfq-title"
                @click.self="rfqModalOpen = false"
            >
                <div class="bg-white rounded-t-card sm:rounded-card shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                    <h2 id="rfq-title" class="text-xl font-semibold tracking-tight text-ink">Request a quote</h2>
                    <p class="text-ink-soft mb-5">Negotiate bulk pricing with the distributor.</p>

                    <form @submit.prevent="submitRfq" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-ink mb-1">Target quantity</label>
                                <TextInput
                                    v-model="rfqForm.requested_quantity"
                                    type="number"
                                    min="1"
                                    required
                                    :error="rfqForm.errors.requested_quantity"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-ink mb-1">Target unit price (₱)</label>
                                <TextInput
                                    v-model="rfqForm.target_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    placeholder="0.00"
                                    :error="rfqForm.errors.target_price"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ink mb-1">Message to seller</label>
                            <textarea
                                v-model="rfqForm.note"
                                rows="3"
                                class="block w-full rounded-control border border-line bg-white px-3 py-2 text-ink placeholder:text-ink-faint resize-none focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-tint"
                                placeholder="Explain your procurement needs, required delivery timeline, etc."
                            ></textarea>
                            <p v-if="rfqForm.errors.note" class="text-sm text-danger mt-1">{{ rfqForm.errors.note }}</p>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-2 border-t border-line">
                            <BaseButton variant="ghost" @click="rfqModalOpen = false">Cancel</BaseButton>
                            <BaseButton type="submit" :disabled="rfqForm.processing">
                                {{ rfqForm.processing ? 'Sending request…' : 'Send request' }}
                            </BaseButton>
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
import BaseButton from '@/Components/ui/BaseButton.vue';
import TextInput from '@/Components/ui/TextInput.vue';
import SellerMark from '@/Components/ui/SellerMark.vue';

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
    hasMixedPacks: { type: Boolean, default: false },
    availablePieces: { type: Number, default: 0 },
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
const rfqModalOpen = ref(false);
const b2bUpsellModalOpen = ref(false);

const reportForm = useForm({
    reason: 'misleading',
    details: '',
});


const rfqForm = useForm({
    product_id: props.product.id,
    distributor_id: props.product.distributor_id,
    requested_quantity: quantity.value,
    target_price: '',
    note: '',
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

const timeBeforeExpiry = computed(() => {
    if (!props.nearestExpiryDate) return null;
    const expiry = new Date(props.nearestExpiryDate);
    const now = new Date();
    
    if (expiry <= now) return 'Expired';

    let years = expiry.getFullYear() - now.getFullYear();
    let months = expiry.getMonth() - now.getMonth();
    
    if (months < 0) {
        years--;
        months += 12;
    }

    const parts = [];
    if (years > 0) parts.push(`${years}yr`);
    if (months > 0) parts.push(`${months}mo`);
    
    if (parts.length === 0 && years === 0 && months === 0) {
        const diffDays = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
        if (diffDays > 0) return `${diffDays}d before expiry`;
        return 'Expiring soon';
    }
    
    return parts.join(' ') + ' before expiry';
});

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

// Pack sizes: the product's price is per its own selling unit; an option that sells a different
// number of pieces per unit (e.g. "Box of 10") costs (per-piece price × its pieces) + adjustment.
// "box" -> "boxes", "piece" -> "pieces"
const pluralize = (word, n) => (n === 1 ? word : (/(s|x|z|ch|sh)$/i.test(word) ? `${word}es` : `${word}s`));

const productPack = computed(() => Math.max(1, Number(props.product.units_per_pack) || 1));
const linePack = computed(() => Math.max(1, Number(selectedVariation.value?.units_per_pack) || productPack.value));
const lineUnitLabel = computed(() => selectedVariation.value?.unit_label || props.product.unit_label || 'piece');
const scaleToLine = (price) => (Number(price) * linePack.value) / productPack.value;

const effectiveRetail = computed(() => scaleToLine(props.product.base_price) + priceAdjustment.value);
const effectiveWholesale = computed(() =>
    props.product.wholesale_price ? scaleToLine(props.product.wholesale_price) + priceAdjustment.value : null
);

const piecesInQuantity = computed(() => Number(quantity.value) * linePack.value);
const wholesaleMinUnits = computed(() =>
    props.product.wholesale_min_qty ? Math.ceil(Number(props.product.wholesale_min_qty) / linePack.value) : null
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

const isApprovedBusiness = computed(() => {
    return !!page.props.auth?.user?.can_access_wholesale;
});

const hasBusinessProfile = computed(() => {
    const user = page.props.auth?.user;
    return user && user.business_profile;
});

const wholesaleSavings = computed(() => {
    if (!isApprovedBusiness.value) return null;
    if (!props.product.wholesale_price || !props.product.wholesale_min_qty) return null;
    if (piecesInQuantity.value < Number(props.product.wholesale_min_qty)) return null;

    const retail = Number(effectiveRetail.value);
    const wholesale = Number(effectiveWholesale.value);
    const savingsPerUnit = retail - wholesale;
    
    if (savingsPerUnit <= 0) return null;

    const totalSavings = savingsPerUnit * quantity.value;
    const percentage = Math.round((savingsPerUnit / retail) * 100);

    return {
        total: totalSavings,
        percentage: percentage
    };
});

const totalPrice = computed(() => {
    const isWholesale = wholesaleSavings.value !== null;
    const unitPrice = isWholesale ? Number(effectiveWholesale.value) : Number(effectiveRetail.value);
    return unitPrice * quantity.value;
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

const openRfqModal = () => {
    if (!page.props.auth?.user) {
        window.location.href = '/login';
        return;
    }
    if (!isApprovedBusiness.value) {
        b2bUpsellModalOpen.value = true;
        return;
    }
    rfqForm.requested_quantity = quantity.value;
    rfqForm.target_price = props.product.wholesale_price || props.product.base_price;
    rfqForm.note = `Hello! I would like to request a quotation for ${quantity.value} pcs of ${props.product.name}.`;
    rfqModalOpen.value = true;
};

const submitRfq = () => {
    rfqForm.post(route('rfq.store'), {
        preserveScroll: true,
        onSuccess: () => {
            rfqModalOpen.value = false;
        }
    });
};

const buyNow = async () => {
    sanitizeQuantityInput();
    if (cartDisabled.value || lineAvailable.value <= 0) return;

    buyingNow.value = true;
    try {
        const payload = {
            product_id: props.product.id,
            quantity: quantity.value,
            buy_now: true
        };
        if (props.hasVariations && selectedVariationId.value) {
            payload.product_variation_id = selectedVariationId.value;
        }
        
        // Pass buy_now as query param to checkout
        router.visit(route('checkout', payload));
    } catch (e) {
        buyingNow.value = false;
    }
};
</script>
