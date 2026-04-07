<template>
    <MainLayout>
    <!-- <div class="relative bg-slate-800 overflow-hidden min-h-[400px] flex items-center">
        <div class="absolute inset-0 opacity-[0.03] bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:24px_24px]"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center w-full">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-6 text-white tracking-tight">
                Welcome to <br class="hidden md:block"/> 
                <span class="text-blue-400">MedEquip.</span>
            </h1>
            
            <p class="text-slate-300 text-lg md:text-xl max-w-2xl mx-auto font-normal mb-10">
                Sourcing healthcare products made easy. Shop for essential medical supplies directly from verified distributors in Cavite.
            </p>
            
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-xl p-2 flex items-center focus-within:ring-4 focus-within:ring-blue-500/30 transition-shadow">
                    <div class="pl-4 pr-2 text-slate-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    
                    <input 
                        v-model="searchQuery"
                        @keyup.enter="applyFilters"
                        type="text" 
                        placeholder="Search surgical tape, stethoscopes, gloves..." 
                        class="w-full bg-transparent px-2 py-3 text-slate-900 placeholder-slate-400 text-lg focus:outline-none border-none truncate"
                    />
                    
                    <button 
                        @click="applyFilters"
                        class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors ml-2"
                    >
                        Search
                    </button>
                </div>
            </div>
        </div>
    </div> -->

    <div class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 min-h-[300px] flex items-center">
    <!-- Subtle animated background pattern with strict overflow container -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#00f_1px,transparent_1px)] [background-size:24px_24px] animate-pulse"></div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center w-full">
        <!-- Hero Heading -->
        <h1 class="text-3xl md:text-4xl font-extrabold mb-4 text-white tracking-tight leading-tight">
            Welcome to <br class="hidden md:block"/>
            <span class="text-blue-400">MedEquip</span>
        </h1>

        <!-- Subheading -->
        <p class="text-slate-300 text-sm md:text-sm max-w-2xl mx-auto font-normal mb-8 animate-fadeIn">
            Sourcing healthcare products made easy. Shop for essential medical supplies directly from verified distributors in Cavite.
        </p>

        <!-- Search Bar -->
        <div class="relative w-full max-w-2xl mx-auto">
            <div class="flex items-center bg-white rounded-xl shadow-md border-2 border-transparent focus-within:border-blue-400 hover:border-blue-300 transition-colors duration-300">
                <!-- Icon -->
                <div class="pl-3 pr-2 text-blue-400 flex-shrink-0">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Input -->
                <input 
                    v-model="searchQuery"
                    @input="handleSearchInput"
                    @focus="handleSearchInput"
                    @blur="closeAutocomplete"
                    @keyup.enter="applyFilters"
                    type="text" 
                    placeholder="Search surgical tape, stethoscopes, gloves..." 
                    class="w-full px-3 py-2 text-slate-900 placeholder-slate-400 text-sm sm:text-base bg-transparent focus:outline-none"
                    autocomplete="off"
                />

                <!-- Search Button -->
                <button 
                    @click="applyFilters"
                    class="flex-shrink-0 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 sm:px-6 py-2 rounded-xl font-semibold transition-colors duration-300 shadow-md hover:shadow-lg text-sm sm:text-base relative z-10"
                >
                    Search
                </button>
            </div>
            
            <!-- Autocomplete Dropdown -->
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="showAutocomplete && (autocompleteResults.products.length > 0 || autocompleteResults.distributors.length > 0)" 
                     class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-slate-100 overflow-hidden z-40 text-left max-h-96 overflow-y-auto">
                    
                    <!-- Distributors section -->
                    <div v-if="autocompleteResults.distributors.length > 0">
                        <div class="px-4 py-2 bg-slate-50/80 border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Distributors</div>
                        <Link 
                            v-for="dist in autocompleteResults.distributors" 
                            :key="'d-'+dist.id" 
                            :href="`/seller/${dist.slug}`"
                            class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition border-b border-slate-50 last:border-0"
                        >
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg shrink-0 overflow-hidden shadow-inner">
                                <img v-if="dist.logo_path" :src="'/storage/' + dist.logo_path" class="w-full h-full object-cover" />
                                <span v-else>{{ dist.company_name.charAt(0) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-900 truncate">{{ dist.company_name }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">Showing {{ dist.products_count || 0 }} products</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 shadow-sm transition whitespace-nowrap hidden sm:block pointer-events-none">View Profile</span>
                            </div>
                        </Link>
                    </div>
                    
                    <!-- Products section -->
                    <div v-if="autocompleteResults.products.length > 0">
                        <div class="px-4 py-2 bg-slate-50/80 border-b border-y border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Products</div>
                        <Link 
                            v-for="prod in autocompleteResults.products" 
                            :key="'p-'+prod.id" 
                            :href="`/products/${prod.id}`"
                            class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition border-b border-slate-50 last:border-0"
                        >
                            <div class="w-10 h-10 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-200">
                                <img v-if="prod.image_path" :src="'/storage/' + prod.image_path" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs sm:text-sm font-bold text-slate-900 truncate">{{ prod.name }}</p>
                                <p class="text-[10px] sm:text-xs text-slate-500 truncate">{{ prod.brand || 'Generic' }} · ₱{{ Number(prod.base_price).toLocaleString() }}</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </transition>
        </div>

        <!-- Optional small interactive hints under search bar -->
        <p class="text-slate-400 text-xs mt-2 animate-pulse">
            Try searching for <span class="font-medium text-blue-400">stethoscope</span>, <span class="font-medium text-blue-400">gloves</span>, or <span class="font-medium text-blue-400">surgical tape</span>
        </p>
    </div>

    <!-- Optional decorative icons floating -->
    <div class="absolute top-6 left-6 text-blue-500 opacity-20 animate-bounce-slow">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c2 2 4 4 4 4m0 0l-4 4-4-4m4-4v8"/>
        </svg>
    </div>
    <div class="absolute bottom-6 right-12 text-blue-400 opacity-15 animate-bounce-slow">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </div>
</div>

<!-- end of improved code -->

    <div v-if="isFilterOpen" @click="isFilterOpen = false" class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden backdrop-blur-sm transition-opacity"></div>

    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8 bg-slate-50">
        <div class="flex flex-col lg:flex-row gap-8">
            <aside :class="['fixed inset-y-0 left-0 z-50 w-[80vw] max-w-sm bg-white shadow-2xl transform lg:transform-none lg:static lg:w-64 lg:bg-transparent lg:shadow-none transition-transform duration-300 ease-in-out lg:flex-shrink-0', isFilterOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0']">
                <div class="lg:hidden px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-white">
                    <h3 class="font-bold text-lg text-slate-900">Filters</h3>
                    <button @click="isFilterOpen = false" class="p-2 text-slate-500 hover:text-slate-900 bg-slate-100 rounded-lg transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="bg-white lg:rounded-xl lg:border lg:border-slate-200 p-6 lg:sticky lg:top-24 space-y-6 h-full lg:h-auto overflow-y-auto lg:overflow-visible pb-24 lg:pb-6">
                    <h3 class="hidden lg:flex font-bold text-lg text-slate-900 items-center border-b border-slate-100 pb-4">
                        <svg class="h-5 w-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filters
                    </h3>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Category</label>
                        <select 
                            v-model="filters.category" 
                            @change="applyFilters"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                            <option value="">All Categories</option>
                            <template v-for="category in categories" :key="category.id">
                                <optgroup :label="category.name">
                                    <option :value="category.id">All {{ category.name }}</option>
                                    <option 
                                        v-for="child in category.children" 
                                        :key="child.id" 
                                        :value="child.id"
                                    >
                                        {{ child.name }}
                                    </option>
                                </optgroup>
                            </template>
                        </select>
                        <p v-if="selectedCategoryHint" class="text-xs text-slate-500 mt-1.5">{{ selectedCategoryHint }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Type</label>
                        <select 
                            v-model="filters.type" 
                            @change="applyFilters"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                            <option value="">All Types</option>
                            <option value="equipment">Equipment</option>
                            <option value="consumable">Consumable</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Price Range (₱)</label>
                        <div class="flex items-center space-x-2">
                            <input 
                                v-model.number="filters.min_price"
                                @change="applyFilters"
                                type="number" 
                                placeholder="Min" 
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <span class="text-slate-400">-</span>
                            <input 
                                v-model.number="filters.max_price"
                                @change="applyFilters"
                                type="number" 
                                placeholder="Max" 
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <button 
                        @click="resetFilters"
                        class="w-full px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold mt-4"
                    >
                        Reset Filters
                    </button>
                </div>
            </aside>

            <div class="flex-1 min-w-0">
    <!-- Top Bar: Showing total & Sort -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <p class="text-slate-600 text-sm">
            Showing <span class="font-bold text-slate-900">{{ products.total }}</span> products
        </p>
        
      <div class="flex items-center gap-2">
    <span class="text-sm text-slate-500">Sort by:</span>

    <select 
        v-model="filters.sort" 
        @change="applyFilters"
        class="px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white shadow-sm"
    >   <option disabled value="">Sort By</option>
        <option value="newest">Newest First</option>
        <option value="price_low">Price: Low to High</option>
        <option value="price_high">Price: High to Low</option>
        <option value="name">Name: A-Z</option>
    </select>
</div>
    </div>

    <!-- Product Grid -->
    <div v-if="products.data.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-4 md:gap-6 auto-rows-fr">
        <div
            v-for="product in products.data"
            :key="product.id"
            class="group bg-white rounded-lg sm:rounded-xl border border-slate-200 hover:border-blue-300 hover:shadow-md transition-all duration-200 overflow-hidden flex flex-col h-full min-h-0"
        >
            <Link :href="`/products/${product.id}`" class="block flex-shrink-0 relative border-b border-slate-100">
                <div class="relative h-36 sm:h-48 md:h-60 lg:h-72 bg-slate-50 overflow-hidden p-2 sm:p-4 md:p-7 flex items-center justify-center">
                    <img
                        v-if="product.image_url"
                        :src="product.image_url"
                        :alt="product.name"
                        class="max-w-full max-h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform duration-300"
                    />
                    <div v-else class="flex flex-col items-center justify-center text-slate-300">
                        <svg class="h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-xs font-medium">No Image</span>
                    </div>
                    
                    <div v-if="product.wholesale_price" class="absolute top-1.5 left-1.5 sm:top-3 sm:left-3 bg-indigo-600 text-white text-[8px] sm:text-[9px] uppercase tracking-wider sm:tracking-widest px-1.5 py-0.5 sm:px-2 sm:py-1 rounded sm:rounded-md font-black shadow-md border border-indigo-400/30">
                        Wholesale
                    </div>

                    <!-- Suspension Badge -->
                    <div v-if="product.distributor.is_suspended" class="absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity group-hover:bg-white/40">
                        <span class="bg-rose-600 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-lg transform -rotate-12 border-2 border-white">
                            Seller Suspended
                        </span>
                    </div>
                </div>
            </Link>

            <div class="p-2.5 sm:p-4 md:p-5 flex flex-col flex-1 min-w-0 min-h-[6.5rem] sm:min-h-[7.5rem] md:min-h-[8rem]">
                <p class="text-[9px] sm:text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider sm:tracking-widest truncate">{{ product.brand || 'Generic' }}</p>
                
                <Link :href="`/products/${product.id}`" class="block mb-1.5 sm:mb-2 group/title min-w-0">
                    <h3 class="font-bold text-slate-900 group-hover/title:text-blue-600 transition-colors line-clamp-2 leading-snug text-xs sm:text-base">
                        {{ product.name }}
                    </h3>
                </Link>
                
                <div class="text-[10px] sm:text-xs text-slate-500 mb-1.5 sm:mb-2 flex items-center gap-1 mt-0.5 min-w-0">
                    <span class="text-slate-400 flex-shrink-0">Sold by:</span>
                    <Link
                        :href="`/seller/${product.distributor.slug}`"
                        class="font-semibold text-blue-600 hover:text-blue-800 hover:underline truncate min-w-0 block"
                    >
                        {{ product.distributor.company_name }}
                    </Link>
                </div>

                <div v-if="product.reviews_count > 0" class="flex items-center gap-1 mb-1.5 sm:mb-2">
                    <div class="flex items-center">
                        <svg
                            v-for="s in 5"
                            :key="s"
                            class="w-3 h-3 sm:w-3.5 sm:h-3.5"
                            :class="s <= Math.round(product.reviews_avg_stars || 0) ? 'text-amber-400' : 'text-slate-200'"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] sm:text-xs text-slate-500 font-medium">
                        {{ Number(product.reviews_avg_stars || 0).toFixed(1) }}
                        <span class="text-slate-400">({{ product.reviews_count }})</span>
                    </span>
                </div>

                <div class="flex flex-col gap-1.5 sm:flex-row sm:items-start sm:justify-between sm:gap-3 mt-auto">
                    <div class="min-w-0">
                        <span class="text-sm sm:text-xl font-black text-slate-900 tabular-nums">
                            ₱{{ Number(product.base_price).toLocaleString() }}
                        </span>
                        <p v-if="product.wholesale_price" class="text-[9px] sm:text-[10px] text-slate-500 mt-1 leading-snug line-clamp-2 sm:line-clamp-none">
                            <span class="font-bold text-indigo-600">₱{{ Number(product.wholesale_price).toLocaleString() }}</span>
                            <span class="hidden sm:inline"> wholesale, min. {{ product.wholesale_min_qty }}</span>
                            <span class="sm:hidden"> / min {{ product.wholesale_min_qty }}</span>
                        </p>
                    </div>
                    
                    <span class="flex-shrink-0 inline-flex items-center self-start text-[9px] sm:text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 sm:px-2 sm:py-0.5 rounded-full font-bold border border-emerald-100">
                        <span class="w-1 h-1 bg-emerald-500 rounded-full mr-1 sm:mr-1.5 flex-shrink-0"></span>
                        <span class="hidden sm:inline">In Stock</span>
                        <span class="sm:hidden">Stock</span>
                    </span>
                </div>

            </div>
        </div>
    </div>

    <!-- No products fallback -->
    <div v-else class="w-full text-center bg-white rounded-xl border border-slate-200 flex flex-col items-center justify-center" style="min-height: 500px;">
        <svg class="h-16 w-16 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <h3 class="text-lg font-bold text-slate-900 mb-1">No products found</h3>
        <p class="text-slate-500 mb-6 text-sm">We couldn't find any products matching your filters.</p>
        <button @click="resetFilters" class="px-6 py-2.5 bg-white border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 transition-colors">
            Clear Filters
        </button>
    </div>

    <!-- Pagination -->
    <div v-if="products.data.length" class="mt-10 flex justify-center">
        <nav class="flex flex-wrap justify-center gap-2">
            <Link
                v-for="link in products.links"
                :key="link.label"
                :href="link.url || '#'"
                :class="[
                    'px-4 py-2 rounded-lg transition-colors text-sm font-semibold border',
                    link.active 
                        ? 'bg-blue-600 text-white border-blue-600 shadow-sm' 
                        : link.url 
                            ? 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300' 
                            : 'bg-slate-50 text-slate-400 border-slate-200 cursor-not-allowed'
                ]"
                v-html="link.label"
            />
        </nav>
    </div>
</div>
        </div>
    </div>

    <button 
        @click="isFilterOpen = true"
        class="lg:hidden fixed bottom-24 right-4 z-40 bg-blue-600 text-white rounded-full p-4 shadow-lg shadow-blue-600/30 hover:bg-blue-700 transition-colors flex items-center justify-center"
        style="bottom: calc(5rem + env(safe-area-inset-bottom, 0px))"
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
    </button>
</MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    distributors: Array,
    filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const showAutocomplete = ref(false);
const autocompleteResults = ref({ products: [], distributors: [] });
let debounceTimer = null;

const handleSearchInput = () => {
    clearTimeout(debounceTimer);
    if (!searchQuery.value.trim()) {
        showAutocomplete.value = false;
        autocompleteResults.value = { products: [], distributors: [] };
        return;
    }
    showAutocomplete.value = true;
    debounceTimer = setTimeout(async () => {
        try {
            const res = await window.axios.get(`/products/search?q=${encodeURIComponent(searchQuery.value)}`);
            autocompleteResults.value = res.data;
        } catch (error) {
            console.error('Autocomplete fetch failed', error);
        }
    }, 300);
};

// Delaying close so that clicking a link isn't interrupted by blur event hiding it
const closeAutocomplete = () => {
    setTimeout(() => {
        showAutocomplete.value = false;
    }, 200);
};

const filters = reactive({
    category: props.filters.category || '',
    distributor: props.filters.distributor || '',
    type: props.filters.type || '',
    min_price: props.filters.min_price || '',
    max_price: props.filters.max_price || '',
    sort: props.filters.sort || 'newest',
});

const isFilterOpen = ref(false);

const selectedCategoryHint = computed(() => {
    const id = Number(filters.category);
    if (!id) return '';
    const cats = props.categories || [];
    for (const parent of cats) {
        if (parent.id === id) return parent.description || '';
        for (const child of (parent.children || [])) {
            if (child.id === id) return child.description || '';
        }
    }
    return '';
});

const applyFilters = () => {
    router.get('/products', {
        search: searchQuery.value,
        ...filters
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    searchQuery.value = '';
    filters.category = '';
    filters.distributor = '';
    filters.type = '';
    filters.min_price = '';
    filters.max_price = '';
    filters.sort = 'newest';
    applyFilters();
};
</script>
