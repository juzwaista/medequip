<template>
    <div class="relative w-full">
        <form role="search" @submit.prevent="submit">
            <svg class="pointer-events-none absolute left-3.5 top-3 h-5 w-5 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7" /><path d="M20 20l-4-4" />
            </svg>
            <input
                v-model="query"
                type="search"
                placeholder="Search products, brands or sellers"
                aria-label="Search products, brands or sellers"
                autocomplete="off"
                class="block h-11 w-full rounded-control border border-line bg-mist pl-11 pr-24 text-ink placeholder:text-ink-faint focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-tint [&::-webkit-search-cancel-button]:hidden"
                @input="onInput"
                @focus="onInput"
                @blur="closeSoon"
                @keydown.esc="open = false"
            />
            <button
                type="submit"
                class="absolute right-1 top-1 h-9 rounded-control bg-brand px-4 font-medium text-white transition-colors hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
            >
                Search
            </button>
        </form>

        <div
            v-if="open && (results.products.length || results.distributors.length)"
            class="absolute left-0 right-0 top-full z-40 mt-2 max-h-96 overflow-y-auto rounded-card border border-line bg-white text-left shadow-lg"
        >
            <div v-if="results.distributors.length">
                <p class="border-b border-line bg-mist px-4 py-2 text-sm font-semibold text-ink-soft">Sellers</p>
                <Link
                    v-for="dist in results.distributors"
                    :key="'d-' + dist.id"
                    :href="`/seller/${dist.slug}`"
                    class="flex items-center gap-3 border-b border-line px-4 py-3 last:border-0 hover:bg-mist"
                >
                    <span class="flex h-10 w-10 flex-none items-center justify-center overflow-hidden rounded-full bg-brand-tint font-semibold text-brand">
                        <img v-if="dist.logo_path" :src="'/storage/' + dist.logo_path" alt="" class="h-full w-full object-cover" />
                        <span v-else>{{ dist.company_name.charAt(0) }}</span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-medium text-ink">{{ dist.company_name }}</span>
                        <span class="block text-sm text-ink-soft">{{ dist.products_count || 0 }} products</span>
                    </span>
                </Link>
            </div>

            <div v-if="results.products.length">
                <p class="border-b border-line bg-mist px-4 py-2 text-sm font-semibold text-ink-soft">Products</p>
                <Link
                    v-for="prod in results.products"
                    :key="'p-' + prod.id"
                    :href="`/products/${prod.slug}`"
                    class="flex items-center gap-3 border-b border-line px-4 py-3 last:border-0 hover:bg-mist"
                >
                    <span class="flex h-10 w-10 flex-none items-center justify-center overflow-hidden rounded-control border border-line bg-[#F7FAFA]">
                        <img v-if="prod.image_path" :src="'/storage/' + prod.image_path" alt="" class="h-full w-full object-cover" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-medium text-ink">{{ prod.name }}</span>
                        <span class="block truncate text-sm text-ink-soft">{{ prod.brand || 'Generic' }}, ₱{{ Number(prod.base_price).toLocaleString() }}</span>
                    </span>
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();

const query = ref(page.props.filters?.search || '');
const open = ref(false);
const results = ref({ products: [], distributors: [] });
let debounceTimer = null;

// Keep the box in sync when the catalog page changes the search itself (e.g. "Reset filters").
watch(() => page.props.filters?.search, (value) => {
    query.value = value || '';
});

const onInput = () => {
    clearTimeout(debounceTimer);
    if (!query.value.trim()) {
        open.value = false;
        results.value = { products: [], distributors: [] };
        return;
    }
    open.value = true;
    debounceTimer = setTimeout(async () => {
        try {
            const res = await window.axios.get(`/products/search?q=${encodeURIComponent(query.value)}`);
            results.value = res.data;
        } catch (error) {
            console.error('Autocomplete fetch failed', error);
        }
    }, 300);
};

// Delay closing so a click on a suggestion isn't swallowed by the blur.
const closeSoon = () => {
    setTimeout(() => { open.value = false; }, 200);
};

const submit = () => {
    open.value = false;
    const onCatalog = page.component === 'Products/Index';
    // On the catalog, keep the current filters and only change the search term.
    const current = onCatalog ? { ...(page.props.filters || {}) } : {};
    delete current.search;

    router.get('/products', { ...current, search: query.value.trim() || undefined }, onCatalog
        ? { preserveState: true, preserveScroll: true, only: ['products', 'filters'] }
        : {});
};
</script>
