<template>
    <Link
        :href="'/products/' + product.id"
        class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md hover:border-blue-300 transition-all group flex flex-col h-full min-h-0 relative shadow-sm"
    >
        <!-- Badges -->
        <div class="absolute top-2 left-2 flex flex-col gap-1 z-10">
            <span v-if="!product.is_active" class="bg-gray-800/95 text-white text-[9px] font-black uppercase px-2 py-1 rounded-md backdrop-blur-sm shadow-sm ring-1 ring-white/20">Inactive</span>
            <span v-if="isOutOfStock" class="bg-red-600/95 text-white text-[9px] font-black uppercase px-2 py-1 rounded-md backdrop-blur-sm shadow-sm ring-1 ring-white/20">Out of Stock</span>
            <span v-if="showWholesale && product.wholesale_price" class="bg-indigo-600/95 text-white text-[9px] font-black uppercase px-2 py-1 rounded-md backdrop-blur-sm shadow-sm ring-1 ring-white/20">Wholesale</span>
        </div>

        <!-- Image Container -->
        <div class="h-40 sm:h-48 md:h-56 bg-slate-50 overflow-hidden flex items-center justify-center p-3 shrink-0 relative group-hover:bg-blue-50/30 transition-colors">
            <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="product.name"
                class="max-w-full max-h-full object-contain group-hover:scale-105 transition duration-500 mix-blend-multiply"
                :class="{'grayscale opacity-50': isOutOfStock || !product.is_active || (product.distributor && product.distributor.is_suspended)}"
            />
            <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-300">
                <svg class="h-10 w-10 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <span class="text-[9px] font-bold uppercase tracking-tighter">No Image</span>
            </div>

            <!-- Suspension Overlay -->
            <div v-if="product.distributor && product.distributor.is_suspended" class="absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity group-hover:bg-white/40">
                <span class="bg-rose-600 text-white text-[9px] font-black uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-xl transform -rotate-12 border-2 border-white ring-4 ring-rose-500/10">
                    Seller Suspended
                </span>
            </div>
        </div>

        <!-- Content -->
        <div class="p-3 sm:p-4 flex flex-col flex-1 min-h-[9rem] sm:min-h-[10rem]">
            <div class="mb-auto">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest truncate">{{ product.brand || 'Generic' }}</p>
                    <div v-if="showSeller && product.distributor" class="text-[10px] font-medium text-blue-600 truncate max-w-[65%] flex items-center gap-1">
                        <Link :href="'/seller/' + product.distributor.slug" class="hover:underline opacity-80 hover:opacity-100 transition-opacity truncate">
                            By: {{ product.distributor.company_name }}
                        </Link>
                    </div>
                </div>
                
                <h3 class="text-sm sm:text-[17px] font-bold text-slate-900 line-clamp-2 mb-1 group-hover:text-blue-600 transition-colors leading-tight h-10 sm:h-12">
                    {{ product?.name || 'Unnamed Product' }}
                </h3>
                
                <p v-if="showCategory && product?.category" class="text-[11px] sm:text-xs text-slate-500 mb-1 line-clamp-1 italic truncate">{{ product.category.name }}</p>
            </div>

            <!-- Unified Footer Section -->
            <div class="mt-auto pt-3 border-t border-slate-100 flex flex-col gap-2">
                <div class="flex items-center justify-between gap-1">
                    <div class="flex flex-col min-w-0 h-10 sm:h-12 justify-center">
                        <span class="text-sm sm:text-[18px] font-bold text-slate-900 tabular-nums truncate">
                            ₱{{ Number(product.base_price).toLocaleString() }}
                        </span>
                        <span v-if="showWholesale && product.wholesale_price" class="text-[10px] sm:text-[11px] font-bold text-indigo-600 truncate">
                            ₱{{ Number(product.wholesale_price).toLocaleString() }} <span class="text-slate-400 font-normal">(Min: {{ product.wholesale_min_qty || 1 }})</span>
                        </span>
                    </div>
                    <div v-if="showStock && !isOutOfStock && stockCount > 0" class="flex items-center gap-1 text-[8px] font-black text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100 uppercase shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>In Stock</span>
                    </div>
                    <div v-else-if="!isOutOfStock && stockCount > 0 && stockCount <= 5" class="text-[8px] font-black text-orange-600 uppercase bg-orange-50 px-1.5 py-0.5 rounded border border-orange-100 italic animate-pulse whitespace-nowrap">
                        {{ stockCount }} LEFT
                    </div>
                    <div v-else class="text-[8px] font-black text-red-600 uppercase bg-red-50 px-1.5 py-0.5 rounded border border-red-100 whitespace-nowrap">
                        {{ isOutOfStock ? 'OUT' : '' }}
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1 min-w-0">
                        <div class="flex items-center text-amber-400 shrink-0">
                            <svg class="w-3 h-3" :class="Math.round(product.reviews_avg_stars || 0) > 0 ? 'fill-current' : 'text-slate-200 fill-current'" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <span class="text-[10px] sm:text-xs font-bold tabular-nums text-slate-700">
                            {{ Number(product.reviews_avg_stars || 0).toFixed(1) }}
                        </span>
                    </div>
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-tighter">
                        {{ Number(product.units_sold || 0).toLocaleString() }} sold
                    </span>
                </div>
            </div>
        </div>
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    product: {
        type: Object,
        required: true
    },
    showWholesale: { type: Boolean, default: false },
    showStock: { type: Boolean, default: false },
    showSeller: { type: Boolean, default: false },
    showCategory: { type: Boolean, default: true },
});

const stockCount = computed(() => {
    if (!props.product.inventory || !Array.isArray(props.product.inventory)) return 0;
    return props.product.inventory.reduce((total, item) => {
        const q = Number(item.quantity) || 0;
        const r = Number(item.reserved_quantity) || 0;
        return total + (q - r);
    }, 0);
});

const isOutOfStock = computed(() => {
    return stockCount.value <= 0;
});
</script>
