<template>
    <MainLayout>
        <OnboardingWizardModal v-if="$page.props.auth.user?.can_access_wholesale && !$page.props.auth.user?.is_distributor" type="buyer" />

        <section class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-6 sm:pt-8">
            <h1 class="text-2xl sm:text-[28px] font-semibold leading-tight tracking-tight text-ink">Medical supplies from licensed distributors</h1>
            <p class="mt-1.5 text-ink-soft">
                Sellers submit their FDA License to Operate, business permit and other documents, and our team reviews them before approving a shop.
                <Link href="/help" class="font-medium text-brand underline underline-offset-4 hover:text-brand-dark">How verification works</Link>
            </p>
        </section>

        <div v-if="isFilterOpen" @click="isFilterOpen = false" class="fixed inset-0 bg-ink/50 z-40 lg:hidden"></div>

        <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <div class="flex flex-col lg:flex-row gap-8">
                <aside
                    aria-label="Filters"
                    :class="['fixed inset-y-0 left-0 z-50 w-[80vw] max-w-sm bg-white transform lg:transform-none lg:static lg:w-[232px] lg:bg-transparent lg:shadow-none transition-transform duration-300 ease-in-out lg:flex-shrink-0', isFilterOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0']"
                >
                    <div class="lg:hidden px-6 py-4 border-b border-line flex justify-between items-center bg-white">
                        <h2 class="font-semibold text-lg text-ink">Filters</h2>
                        <button @click="isFilterOpen = false" class="p-2 text-ink-soft hover:text-ink hover:bg-mist rounded-control transition-colors" aria-label="Close filters">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 lg:p-0 lg:sticky lg:top-24 h-full lg:h-auto overflow-y-auto lg:overflow-visible pb-24 lg:pb-0">
                        <nav aria-label="Categories" class="border-b border-line pb-5 mb-5">
                            <h3 class="mb-2 text-sm font-semibold text-ink">Category</h3>
                            <ul>
                                <li>
                                    <button type="button" :class="railItem(!filters.category)" :aria-current="!filters.category ? 'true' : undefined" @click="selectCategory('')">
                                        All products
                                    </button>
                                </li>
                                <template v-for="parent in categories" :key="parent.id">
                                    <li>
                                        <button type="button" :class="railItem(Number(filters.category) === parent.id)" :aria-current="Number(filters.category) === parent.id ? 'true' : undefined" @click="selectCategory(parent.id)">
                                            {{ parent.name }}
                                        </button>
                                    </li>
                                    <li v-for="child in (activeParentId === parent.id ? parent.children : [])" :key="child.id">
                                        <button type="button" :class="[railItem(Number(filters.category) === child.id), 'pl-7 text-sm']" :aria-current="Number(filters.category) === child.id ? 'true' : undefined" @click="selectCategory(child.id)">
                                            {{ child.name }}
                                        </button>
                                    </li>
                                </template>
                            </ul>
                            <p v-if="selectedCategoryHint" class="mt-2 px-3 text-sm text-ink-soft">{{ selectedCategoryHint }}</p>
                        </nav>

                        <div class="border-b border-line pb-5 mb-5">
                            <h3 class="mb-2 text-sm font-semibold text-ink">Type</h3>
                            <ul>
                                <li v-for="option in typeOptions" :key="option.value">
                                    <button type="button" :class="railItem(filters.type === option.value)" :aria-current="filters.type === option.value ? 'true' : undefined" @click="selectType(option.value)">
                                        {{ option.label }}
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="border-b border-line pb-5 mb-5">
                            <h3 class="mb-2 text-sm font-semibold text-ink">Price (₱)</h3>
                            <div class="flex items-center gap-2">
                                <TextInput v-model="filters.min_price" @change="applyFilters" type="number" min="0" inputmode="numeric" placeholder="Min" aria-label="Minimum price" class="min-w-0 flex-1" />
                                <TextInput v-model="filters.max_price" @change="applyFilters" type="number" min="0" inputmode="numeric" placeholder="Max" aria-label="Maximum price" class="min-w-0 flex-1" />
                            </div>
                        </div>

                        <BaseButton variant="ghost" size="sm" class="w-full" @click="resetFilters">Reset filters</BaseButton>
                    </div>
                </aside>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <p class="text-ink-soft">
                            <span class="font-semibold tabular-nums text-ink">{{ products.total }}</span> {{ products.total === 1 ? 'product' : 'products' }}
                        </p>

                        <label class="flex items-center gap-2 text-ink-soft">
                            <span>Sort</span>
                            <select
                                v-model="filters.sort"
                                @change="applyFilters"
                                class="h-9 rounded-control border border-line bg-white py-0 pl-3 pr-8 text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-tint"
                            >
                                <option value="popularity">Most popular</option>
                                <option value="newest">Newest</option>
                                <option value="price_low">Price, low to high</option>
                                <option value="price_high">Price, high to low</option>
                                <option value="name">Name, A to Z</option>
                            </select>
                        </label>
                    </div>

                    <!-- Product grid, with a light overlay while filters reload -->
                    <div class="relative min-h-[300px]">
                        <div v-if="isLoading" class="absolute inset-0 z-10 flex items-start justify-center pt-24 rounded-card bg-mist/60">
                            <div class="flex items-center gap-2 rounded-control border border-line bg-white px-4 py-2 text-sm font-medium text-brand shadow-sm">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Updating products</span>
                            </div>
                        </div>

                        <div v-if="products.data.length" class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 auto-rows-fr" :class="{ 'opacity-60': isLoading }">
                            <ProductCard
                                v-for="product in products.data"
                                :key="product.id"
                                :product="product"
                                :showWholesale="true"
                                :showStock="true"
                                :showSeller="true"
                                :showCategory="false"
                            />
                        </div>

                        <div v-else-if="!isLoading" class="flex min-h-[420px] w-full flex-col items-center justify-center rounded-card border border-line bg-white px-6 text-center">
                            <h3 class="text-lg font-semibold text-ink">No products match these filters</h3>
                            <p class="mt-1 mb-5 max-w-sm text-ink-soft">Try a different search term, widen the price range, or clear the filters to see everything.</p>
                            <BaseButton variant="secondary" @click="resetFilters">Clear filters</BaseButton>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="products.data.length" class="mt-10 flex justify-center">
                        <nav class="flex flex-wrap justify-center gap-2" aria-label="Pagination">
                            <Link
                                v-for="link in products.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                preserve-scroll
                                preserve-state
                                replace
                                :only="['products', 'filters']"
                                :class="[
                                    'min-w-10 rounded-control border px-3.5 py-2 text-center text-sm font-medium tabular-nums transition-colors',
                                    link.active
                                        ? 'border-brand bg-brand text-white'
                                        : link.url
                                            ? 'border-line bg-white text-ink hover:border-brand hover:text-brand'
                                            : 'cursor-not-allowed border-line bg-mist text-ink-faint'
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
            class="lg:hidden fixed right-4 z-40 flex items-center gap-2 rounded-full bg-brand px-4 py-3 font-medium text-white shadow-lg transition-colors hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
            style="bottom: calc(5rem + env(safe-area-inset-bottom, 0px))"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            Filters
        </button>
    </MainLayout>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import OnboardingWizardModal from '@/Components/OnboardingWizardModal.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import TextInput from '@/Components/ui/TextInput.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    distributors: Array,
    filters: Object,
});

const isLoading = ref(false);

const filters = reactive({
    category: props.filters?.category || '',
    distributor: props.filters?.distributor || '',
    type: props.filters?.type || '',
    min_price: props.filters?.min_price || '',
    max_price: props.filters?.max_price || '',
    sort: props.filters?.sort || 'popularity',
});

// Sync local filter state if props change from navigation
watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        filters.category = newFilters.category || '';
        filters.distributor = newFilters.distributor || '';
        filters.type = newFilters.type || '';
        filters.min_price = newFilters.min_price || '';
        filters.max_price = newFilters.max_price || '';
        filters.sort = newFilters.sort || 'popularity';
    }
}, { deep: true });

const isFilterOpen = ref(false);

const typeOptions = [
    { value: '', label: 'All types' },
    { value: 'equipment', label: 'Equipment' },
    { value: 'consumable', label: 'Consumable' },
];

// Shared look for the filter lists (categories and type).
const railItem = (active) => [
    'flex w-full items-center justify-between rounded-r-control border-l-2 px-3 py-1.5 text-left transition-colors',
    active
        ? 'border-brand bg-brand-tint/60 font-medium text-ink lg:bg-white'
        : 'border-transparent text-ink-soft hover:bg-white/70 hover:text-ink',
];

// The top-level category that contains the current selection, so its subcategories can be listed.
const activeParentId = computed(() => {
    const id = Number(filters.category);
    if (!id) return null;
    for (const parent of props.categories || []) {
        if (parent.id === id) return parent.id;
        if ((parent.children || []).some((child) => child.id === id)) return parent.id;
    }
    return null;
});

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

const selectCategory = (id) => {
    filters.category = id;
    applyFilters();
};

const selectType = (value) => {
    filters.type = value;
    applyFilters();
};

// The search term lives in the header search box; keep whatever is currently applied.
const applyFilters = (options = {}) => {
    isLoading.value = true;
    router.get('/products', {
        search: options.clearSearch ? undefined : (props.filters?.search || undefined),
        category: filters.category || undefined,
        distributor: filters.distributor || undefined,
        type: filters.type || undefined,
        min_price: filters.min_price || undefined,
        max_price: filters.max_price || undefined,
        sort: filters.sort || 'popularity',
    }, {
        preserveState: true,
        preserveScroll: true,
        showProgress: false,
        only: ['products', 'filters'],
        onFinish: () => {
            isLoading.value = false;
            isFilterOpen.value = false;
        }
    });
};

const resetFilters = () => {
    filters.category = '';
    filters.distributor = '';
    filters.type = '';
    filters.min_price = '';
    filters.max_price = '';
    filters.sort = 'popularity';
    applyFilters({ clearSearch: true });
};
</script>
