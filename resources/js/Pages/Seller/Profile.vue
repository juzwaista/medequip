<template>
    <MainLayout>
        <div class="min-h-screen bg-gray-50">
            <!-- Cover Photo Hero -->
            <div class="relative h-56 md:h-64 bg-gradient-to-r from-blue-600 to-blue-800">
                <img
                    v-if="distributor.cover_photo_path"
                    :src="`/storage/${distributor.cover_photo_path}`"
                    alt="Cover Photo"
                    class="w-full h-full object-cover"
                />
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Profile Header -->
                <div class="relative -mt-20 mb-6">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex flex-col md:flex-row gap-6 items-start">
                            <div class="w-28 h-28 flex-shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl overflow-hidden border-4 border-white shadow-lg">
                                <img
                                    v-if="distributor.logo_path"
                                    :src="`/storage/${distributor.logo_path}`"
                                    :alt="distributor.company_name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-4xl font-bold text-gray-600">
                                    {{ distributor.company_name.charAt(0) }}
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 truncate">{{ distributor.company_name }}</h1>
                                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-sm text-gray-600">
                                    <span class="flex items-center">
                                        <svg class="h-4 w-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Verified Seller
                                    </span>
                                    <span v-if="stats.shop_rating_avg" class="flex items-center text-amber-700 font-semibold">
                                        <svg class="w-4 h-4 mr-0.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        {{ stats.shop_rating_avg.toFixed(1) }}
                                        <span class="text-gray-500 font-normal ml-1">({{ stats.shop_rating_count }} {{ stats.shop_rating_count === 1 ? 'review' : 'reviews' }})</span>
                                    </span>
                                    <span v-else class="flex items-center text-gray-400">
                                        <svg class="w-4 h-4 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        No ratings yet
                                    </span>
                                    <span>Member since {{ stats.active_since }}</span>
                                    <span>{{ stats.total_products }} products</span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 flex-shrink-0">
                                <Link
                                    v-if="messaging?.start_url"
                                    :href="messaging.start_url"
                                    class="px-5 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-900 transition text-center text-sm font-medium"
                                >
                                    Message Shop
                                </Link>
                                <a
                                    v-if="distributor.website"
                                    :href="distributor.website"
                                    target="_blank"
                                    class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-center text-sm font-medium"
                                >
                                    Website
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Bar -->
                <div class="mb-6 border-b border-gray-200">
                    <nav class="flex gap-1" aria-label="Shop navigation">
                        <button
                            v-for="t in tabs"
                            :key="t.key"
                            @click="switchTab(t.key)"
                            :class="[
                                'px-5 py-3 text-sm font-medium border-b-2 transition-colors',
                                activeTab === t.key
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            {{ t.label }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->

                <!-- === SHOP TAB === -->
                <div v-if="activeTab === 'shop'" class="pb-12 space-y-10">
                    <!-- About -->
                    <section v-if="distributor.description || distributor.address || distributor.email || hasSocialLinks || hasBusinessHours" class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">About</h2>
                        <div v-if="distributor.description" class="mb-4">
                            <p
                                class="text-gray-700 leading-relaxed text-sm sm:text-base whitespace-pre-line"
                                :class="aboutExpanded ? '' : 'line-clamp-4'"
                            >{{ distributor.description }}</p>
                            <button
                                v-if="aboutNeedsToggle"
                                type="button"
                                class="mt-2 text-sm font-semibold text-blue-600 hover:text-blue-800"
                                @click="aboutExpanded = !aboutExpanded"
                            >
                                {{ aboutExpanded ? 'Show less' : 'Read more' }}
                            </button>
                        </div>

                        <details v-if="distributor.address || distributor.email || hasSocialLinks || hasBusinessHours" class="group border-t border-gray-100 pt-4 mt-1">
                            <summary class="text-sm font-semibold text-gray-800 cursor-pointer list-none flex items-center justify-between gap-2">
                                <span>Contact & hours</span>
                                <svg class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                            <!-- Contact details -->
                            <div v-if="distributor.address || distributor.email || hasSocialLinks" class="space-y-2">
                                <div v-if="distributor.address" class="flex items-start gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>{{ distributor.address }}</span>
                                </div>
                                <div v-if="distributor.email" class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <a :href="'mailto:' + distributor.email" class="text-blue-600 hover:underline">{{ distributor.email }}</a>
                                </div>
                                <div v-if="hasSocialLinks" class="flex items-center gap-3 pt-1">
                                    <a v-if="distributor.social_links?.facebook" :href="distributor.social_links.facebook" target="_blank" class="text-gray-400 hover:text-blue-600 transition" title="Facebook">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    </a>
                                    <a v-if="distributor.social_links?.instagram" :href="distributor.social_links.instagram" target="_blank" class="text-gray-400 hover:text-pink-600 transition" title="Instagram">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                    </a>
                                    <a v-if="distributor.social_links?.tiktok" :href="distributor.social_links.tiktok" target="_blank" class="text-gray-400 hover:text-black transition" title="TikTok">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                                    </a>
                                </div>
                            </div>

                            <!-- Business hours -->
                            <div v-if="hasBusinessHours">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Business Hours</h4>
                                <div class="space-y-1">
                                    <div v-for="entry in distributor.business_hours" :key="entry.day" class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ entry.day }}</span>
                                        <span v-if="entry.closed" class="text-gray-400">Closed</span>
                                        <span v-else class="text-gray-900">{{ entry.open }} - {{ entry.close }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </details>
                    </section>

                    <!-- Recommended Products -->
                    <section v-if="recommendedProducts && recommendedProducts.length">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Recommended</h2>
                            <button @click="switchTab('products')" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all</button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 auto-rows-fr">
                            <ProductCard v-for="p in recommendedProducts" :key="p.id" :product="p" />
                        </div>
                    </section>

                    <!-- Top Sellers -->
                    <section v-if="topSellers && topSellers.length">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Top Sellers</h2>
                            <button @click="switchTab('products')" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all</button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 auto-rows-fr">
                            <ProductCard v-for="p in topSellers" :key="p.id" :product="p" />
                        </div>
                    </section>

                    <!-- Empty shop state -->
                    <div v-if="(!recommendedProducts || !recommendedProducts.length) && (!topSellers || !topSellers.length)" class="text-center py-16 bg-white rounded-xl">
                        <svg class="h-14 w-14 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        <h3 class="text-base font-semibold text-gray-900">No products yet</h3>
                        <p class="text-gray-500 text-sm mt-1">This shop hasn't listed any products.</p>
                    </div>
                </div>

                <!-- === PRODUCTS TAB === -->
                <div v-if="activeTab === 'products'" class="pb-12">
                    <!-- Filters toolbar -->
                    <div class="flex flex-col sm:flex-row gap-3 mb-6">
                        <div class="relative flex-1">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input
                                v-model="localSearch"
                                @keyup.enter="applyProductFilters"
                                type="text"
                                placeholder="Search products..."
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                            />
                        </div>
                        <select
                            v-model="localCategory"
                            @change="applyProductFilters"
                            class="px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">All categories</option>
                            <template v-for="cat in shopCategories" :key="cat.id">
                                <option :value="cat.id">{{ cat.name }} ({{ cat.total_product_count }})</option>
                            </template>
                        </select>
                        <select
                            v-model="localSort"
                            @change="applyProductFilters"
                            class="px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="newest">Newest</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="best_rated">Best Rated</option>
                        </select>
                    </div>

                    <!-- Product Grid -->
                    <div v-if="products && products.data && products.data.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 auto-rows-fr">
                        <ProductCard v-for="p in products.data" :key="p.id" :product="p" />
                    </div>

                    <div v-else class="text-center py-16 bg-white rounded-xl">
                        <svg class="h-14 w-14 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <h3 class="text-base font-semibold text-gray-900">No products found</h3>
                        <p class="text-gray-500 text-sm mt-1">
                            {{ hasActiveProductFilters ? 'A category, search, or sort filter may be hiding results.' : 'This shop has no active listings yet.' }}
                        </p>
                        <button
                            v-if="hasActiveProductFilters"
                            type="button"
                            class="mt-4 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition"
                            @click="clearProductFilters"
                        >
                            Clear filters and show all products
                        </button>
                    </div>

                    <!-- Pagination -->
                    <div v-if="products && products.data && products.data.length && products.last_page > 1" class="mt-8 flex justify-center gap-1.5">
                        <template v-for="link in products.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3.5 py-2 rounded-lg text-sm',
                                    link.active
                                        ? 'bg-blue-600 text-white font-medium'
                                        : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                                ]"
                                v-html="link.label"
                                preserve-state
                            />
                        </template>
                    </div>
                </div>

                <!-- === REVIEWS TAB (product reviews only) === -->
                <div v-if="activeTab === 'reviews'" class="pb-12">
                    <div v-if="shopReviews && shopReviews.data && shopReviews.data.length" class="space-y-4">
                        <article
                            v-for="rev in shopReviews.data"
                            :key="rev.id"
                            class="bg-white rounded-xl border border-gray-200 p-5"
                        >
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="flex items-center gap-0.5" aria-hidden="true">
                                <template v-for="n in 5" :key="n">
                                    <svg class="w-3.5 h-3.5" :class="n <= rev.stars ? 'text-amber-500' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </template>
                            </span>
                                <Link
                                    v-if="rev.product"
                                    :href="`/products/${rev.product.id}`"
                                    class="text-sm font-semibold text-blue-600 hover:text-blue-800 hover:underline"
                                >
                                    {{ rev.product.name }}
                                </Link>
                            </div>
                            <p v-if="rev.body" class="text-sm text-gray-700 whitespace-pre-line mb-3">{{ rev.body }}</p>
                            <p class="text-xs text-gray-400">
                                {{ rev.user?.name || 'Customer' }}
                                <span v-if="formatReviewDate(rev.created_at)"> · {{ formatReviewDate(rev.created_at) }}</span>
                            </p>
                        </article>
                    </div>
                    <div v-else class="text-center py-16 bg-white rounded-xl border border-gray-100">
                        <svg class="h-14 w-14 mx-auto text-gray-300 mb-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <h3 class="text-base font-semibold text-gray-900">No product reviews yet</h3>
                        <p class="text-gray-500 text-sm mt-1">Reviews from buyers will appear here.</p>
                    </div>
                    <div
                        v-if="shopReviews && shopReviews.data && shopReviews.data.length && shopReviews.last_page > 1"
                        class="mt-8 flex justify-center gap-1.5"
                    >
                        <template v-for="link in shopReviews.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3.5 py-2 rounded-lg text-sm',
                                    link.active
                                        ? 'bg-blue-600 text-white font-medium'
                                        : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                                ]"
                                v-html="link.label"
                                preserve-state
                            />
                        </template>
                    </div>
                </div>

                <!-- === CATEGORIES TAB === -->
                <div v-if="activeTab === 'categories'" class="pb-12">
                    <div v-if="shopCategories.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <button
                            v-for="cat in shopCategories"
                            :key="cat.id"
                            @click="goToCategory(cat.id)"
                            class="bg-white rounded-xl border border-gray-200 p-5 text-left hover:border-blue-300 hover:shadow-md transition group"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">{{ cat.name }}</h3>
                                <span class="text-xs font-medium bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full">{{ cat.total_product_count }} {{ cat.total_product_count === 1 ? 'product' : 'products' }}</span>
                            </div>
                            <p v-if="cat.description" class="text-sm text-gray-500 line-clamp-2 mb-3">{{ cat.description }}</p>
                            <div v-if="cat.children && cat.children.length" class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="child in cat.children"
                                    :key="child.id"
                                    class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md"
                                >
                                    {{ child.name }} ({{ child.shop_product_count }})
                                </span>
                            </div>
                        </button>
                    </div>

                    <div v-else class="text-center py-16 bg-white rounded-xl">
                        <svg class="h-14 w-14 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        <h3 class="text-base font-semibold text-gray-900">No categories</h3>
                        <p class="text-gray-500 text-sm mt-1">This shop doesn't have listed products in any category yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    distributor: Object,
    stats: Object,
    shopCategories: { type: Array, default: () => [] },
    activeTab: { type: String, default: 'shop' },
    messaging: { type: Object, default: null },
    recommendedProducts: { type: Array, default: () => [] },
    topSellers: { type: Array, default: () => [] },
    products: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
    shopReviews: { type: Object, default: null },
});

const tabs = [
    { key: 'shop', label: 'Shop' },
    { key: 'products', label: 'Products' },
    { key: 'categories', label: 'Categories' },
    { key: 'reviews', label: 'Reviews' },
];

function formatReviewDate(value) {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

const localSearch = ref(props.filters?.search || '');
const localCategory = ref(props.filters?.category != null && props.filters?.category !== '' ? String(props.filters.category) : '');
const localSort = ref(props.filters?.sort || 'newest');

const hasActiveProductFilters = computed(() => {
    const s = String(localSearch.value || '').trim();
    const c = String(localCategory.value || '').trim();
    const sort = localSort.value || 'newest';
    return s.length > 0 || c.length > 0 || sort !== 'newest';
});

/** Keep toolbar in sync after Inertia visits (pagination, category drill-in, etc.). */
watch(
    () => [props.activeTab, props.filters],
    () => {
        if (props.activeTab !== 'products') {
            return;
        }
        localSearch.value = props.filters?.search || '';
        localCategory.value = props.filters?.category != null && props.filters?.category !== '' ? String(props.filters.category) : '';
        localSort.value = props.filters?.sort || 'newest';
    },
    { deep: true }
);

const hasSocialLinks = computed(() => {
    const s = props.distributor.social_links;
    return s && (s.facebook || s.instagram || s.tiktok);
});

const hasBusinessHours = computed(() => {
    const h = props.distributor.business_hours;
    return Array.isArray(h) && h.length > 0;
});

const aboutExpanded = ref(false);
const aboutNeedsToggle = computed(() => String(props.distributor.description || '').length > 220);

const shopUrl = computed(() => `/seller/${props.distributor.slug}`);

function switchTab(tab) {
    const params = { tab };
    if (tab === 'products') {
        // Opening Products from the tab bar should show the full catalog — stale category/search
        // from the Categories tab or a previous visit otherwise hides every row.
        localSearch.value = '';
        localCategory.value = '';
        localSort.value = 'newest';
    }
    router.get(shopUrl.value, params, { preserveState: false });
}

function clearProductFilters() {
    localSearch.value = '';
    localCategory.value = '';
    localSort.value = 'newest';
    router.get(shopUrl.value, { tab: 'products' }, { preserveState: false });
}

function applyProductFilters() {
    const params = { tab: 'products' };
    if (localSearch.value) params.search = localSearch.value;
    if (localCategory.value) params.category = localCategory.value;
    if (localSort.value && localSort.value !== 'newest') params.sort = localSort.value;
    router.get(shopUrl.value, params, { preserveState: true, preserveScroll: true });
}

function goToCategory(categoryId) {
    localCategory.value = String(categoryId);
    router.get(shopUrl.value, { tab: 'products', category: categoryId }, { preserveState: false });
}

// --- ProductCard inline component ---
const ProductCard = {
    props: { product: Object },
    template: `
        <Link
            :href="'/products/' + product.id"
            class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md hover:border-gray-300 transition group flex flex-col h-full min-h-0"
        >
            <div class="h-36 sm:h-44 md:h-52 lg:h-56 bg-gray-100 overflow-hidden flex items-center justify-center p-2 shrink-0">
                <img
                    v-if="productImage"
                    :src="productImage"
                    :alt="product.name"
                    class="max-w-full max-h-full object-contain group-hover:scale-105 transition duration-300"
                />
                <div v-else class="w-full h-full flex items-center justify-center">
                    <svg class="h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </div>
            </div>
            <div class="p-3 flex flex-col flex-1 min-h-[6.5rem] sm:min-h-[7.5rem]">
                <h3 class="text-sm font-medium text-gray-900 line-clamp-2 mb-1 min-h-[2.5rem]">{{ product.name }}</h3>
                <p v-if="product.category" class="text-xs text-gray-500 mb-1.5 line-clamp-1">{{ product.category.name }}</p>
                <p v-else class="text-xs text-transparent mb-1.5 select-none" aria-hidden="true">&nbsp;</p>
                <div v-if="product.reviews_avg_stars" class="flex items-center gap-1 mb-1.5 min-h-[1.25rem]">
                    <div class="flex">
                        <svg v-for="i in 5" :key="i" class="w-3.5 h-3.5" :class="i <= Math.round(product.reviews_avg_stars) ? 'text-amber-400' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <span class="text-xs text-gray-500">({{ product.reviews_count }})</span>
                </div>
                <div v-else class="min-h-[1.25rem] mb-1.5" aria-hidden="true"></div>
                <div class="flex items-center justify-between mt-auto pt-1">
                    <span class="text-sm font-bold text-blue-600">₱{{ Number(product.base_price).toLocaleString() }}</span>
                </div>
            </div>
        </Link>
    `,
    computed: {
        productImage() {
            if (this.product.image_url) return this.product.image_url;
            if (this.product.image_path) return '/storage/' + this.product.image_path;
            if (this.product.images && this.product.images.length) {
                const primary = this.product.images.find(i => i.is_primary);
                const img = primary || this.product.images[0];
                return img?.image_path ? '/storage/' + img.image_path : null;
            }
            return null;
        }
    },
    components: { Link },
};
</script>
