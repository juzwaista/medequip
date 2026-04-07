<template>
    <OwnerLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <div class="flex items-center text-sm text-gray-600 mb-3">
                    <Link href="/owner/inventory" class="hover:text-blue-600">Inventory</Link>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-900 font-medium">Edit product</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Edit product</h1>
                <p class="text-gray-600 mt-2">Update photos, options, pricing, and stock.</p>
            </div>

            <div v-if="pageErrors && Object.keys(pageErrors).length > 0" class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4">
                <p class="text-sm font-medium text-red-800 mb-2">Please fix the following:</p>
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    <li v-for="(err, key) in pageErrors" :key="key">{{ Array.isArray(err) ? err[0] : err }}</li>
                </ul>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Gallery -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Photos</h2>
                    <p class="text-sm text-gray-600 mb-4">Check images to remove, add more files, and pick the main display. At least one image must remain.</p>

                    <div v-if="visibleExisting.length" class="flex flex-wrap gap-4 mb-6">
                        <div v-for="img in visibleExisting" :key="img.id" class="text-center">
                            <div class="relative w-28 h-28 rounded-xl border border-gray-200 overflow-hidden bg-gray-50">
                                <img :src="`/storage/${img.image_path}`" alt="" class="w-full h-full object-cover" />
                                <label class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] py-1 cursor-pointer">
                                    <input v-model="removedImageIds" type="checkbox" :value="img.id" class="mr-1 align-middle" />
                                    Remove
                                </label>
                            </div>
                            <label class="mt-2 flex items-center justify-center gap-1 text-xs text-gray-700 cursor-pointer">
                                <input v-model="primaryPick" type="radio" :value="`e:${img.id}`" class="text-blue-600" />
                                Main
                            </label>
                        </div>
                    </div>

                    <label class="block text-sm font-semibold text-gray-800 mb-2">Add more images</label>
                    <input
                        type="file"
                        accept="image/*"
                        multiple
                        @change="onNewImages"
                        class="block w-full max-w-md text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-semibold file:bg-blue-50 file:text-blue-700"
                    />
                    <div v-if="newPreviews.length" class="mt-4 flex flex-wrap gap-4">
                        <div v-for="(src, idx) in newPreviews" :key="'n' + idx" class="text-center">
                            <div class="relative w-28 h-28 rounded-xl border border-gray-200 overflow-hidden bg-gray-50">
                                <img :src="src" alt="" class="w-full h-full object-cover" />
                            </div>
                            <label class="mt-2 flex items-center justify-center gap-1 text-xs text-gray-700 cursor-pointer">
                                <input v-model="primaryPick" type="radio" :value="`n:${idx}`" class="text-blue-600" />
                                Main
                            </label>
                        </div>
                    </div>
                </section>

                <!-- Barcode -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Barcode</h2>
                    <div class="flex gap-2 max-w-xl">
                        <input
                            v-model="fields.barcode"
                            type="text"
                            placeholder="Scan or type"
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-xl font-mono text-sm shadow-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500"
                        />
                        <BarcodeScannerModal @scanned="(code) => (fields.barcode = code)" />
                    </div>
                </section>

                <!-- SKU -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Product SKU</h2>
                    <div>
                        <input
                            v-model="fields.sku"
                            type="text"
                            placeholder="Optional (e.g., MED-001)"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl font-mono text-sm"
                        />
                        <p v-if="pageErrors.sku" class="text-red-600 text-sm mt-2">{{ pageErrors.sku }}</p>
                        <p class="text-xs text-gray-500 mt-2">Your internal reference code for this product.</p>
                    </div>
                </section>

                <!-- Basics -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Basic information</h2>
                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Product name <span class="text-red-500">*</span></label>
                            <input v-model="fields.name" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Category <span class="text-red-500">*</span></label>
                                <select v-model="fields.category_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                                    <template v-for="group in categoryGroups" :key="group.parent.id">
                                        <optgroup :label="group.parent.name">
                                            <option :value="group.parent.id">{{ group.parent.name }} (general)</option>
                                            <option v-for="c in group.children" :key="c.id" :value="c.id">{{ c.name }}</option>
                                        </optgroup>
                                    </template>
                                </select>
                                <p class="mt-1.5 min-h-[2.5rem] text-xs leading-relaxed text-gray-500">{{ selectedCategoryHint || '' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Product type <span class="text-red-500">*</span></label>
                                <select v-model="fields.product_type" required class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                                    <option value="consumable">Consumable</option>
                                    <option value="equipment">Equipment</option>
                                </select>
                                <p class="mt-1.5 min-h-[2.5rem] text-xs text-gray-500">Shown on the product page (e.g. badge for equipment vs consumable).</p>
                            </div>
                        </div>
                        <div v-if="isMedicineCategory" class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-4">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input v-model="fields.requires_prescription" type="checkbox" class="mt-1 rounded border-gray-300 text-blue-600" />
                                <span class="text-sm font-medium text-gray-900">Requires a valid prescription</span>
                            </label>
                        </div>
                        <div class="rounded-xl border border-orange-100 bg-orange-50 p-4 sm:p-5">
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Vehicle requirement for delivery <span class="text-red-500">*</span></label>
                            <select v-model="fields.vehicle_requirement" required class="w-full px-4 py-3 border border-orange-200 rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-orange-500/30 focus:border-orange-500">
                                <option value="motorcycle">Fits in Motorcycle (Small)</option>
                                <option value="car_sedan">Requires Sedan (Medium)</option>
                                <option value="car_hatchback">Requires Hatchback / SUV (Large)</option>
                                <option value="pickup_truck">Requires Pickup Truck (Extra Large)</option>
                                <option value="box_truck">Requires Box Truck (Heavy/Bulky)</option>
                            </select>
                            <p class="text-xs text-gray-600 mt-2">This restricts which couriers can accept orders containing this product. Select the minimum vehicle size required.</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Brand <span class="text-red-500">*</span></label>
                                <input v-model="fields.brand" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Model <span class="text-red-500">*</span></label>
                                <input v-model="fields.model" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Description <span class="text-red-500">*</span></label>
                            <textarea v-model="fields.description" rows="4" required class="w-full px-4 py-3 border border-gray-300 rounded-xl" />
                        </div>
                    </div>
                </section>

                <!-- Pricing -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Pricing</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                        <div class="flex h-full min-h-0 flex-col rounded-xl border border-gray-200 bg-gray-50/70 p-4 sm:p-5">
                            <label class="shrink-0 text-sm font-semibold text-gray-800">Retail price (₱) <span class="text-red-500">*</span></label>
                            <p class="mt-1.5 min-h-[3.25rem] flex-1 text-xs leading-relaxed text-gray-500">Your main selling price per unit. Variant price adjustments add to or subtract from this (and from wholesale when it applies).</p>
                            <input
                                v-model.number="fields.base_price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="mt-4 w-full shrink-0 px-4 py-3 border border-gray-300 rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500"
                            />
                        </div>
                        <div class="flex h-full min-h-0 flex-col rounded-xl border border-gray-200 bg-gray-50/70 p-4 sm:p-5">
                            <label class="shrink-0 text-sm font-semibold text-gray-800">Wholesale price (₱)</label>
                            <p class="mt-1.5 min-h-[3.25rem] flex-1 text-xs leading-relaxed text-gray-500">Optional lower price for bulk orders. If you enter a price, set the minimum quantity below.</p>
                            <input
                                v-model.number="fields.wholesale_price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-4 w-full shrink-0 px-4 py-3 border border-gray-300 rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500"
                            />
                        </div>
                        <div v-if="fields.wholesale_price" class="sm:col-span-2 rounded-xl border border-gray-200 bg-gray-50/70 p-4 sm:p-5">
                            <label class="block text-sm font-semibold text-gray-800">Minimum wholesale quantity <span class="text-red-500">*</span></label>
                            <p class="text-xs text-gray-500 mt-1.5 mb-3">Customers must order at least this many units to get the wholesale price.</p>
                            <input
                                v-model.number="fields.wholesale_min_qty"
                                type="number"
                                min="2"
                                required
                                class="w-full max-w-xs px-4 py-3 border border-gray-300 rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500"
                            />
                        </div>
                    </div>
                </section>

                <!-- Variations -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <div class="flex items-center justify-between gap-4 mb-2">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500">Options / Variations</h2>
                        <button type="button" class="text-sm font-semibold text-blue-600 hover:underline" @click="addOptionGroup">+ Add option group</button>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Define option groups (e.g. Size, Color) and their values. Combinations are auto-generated.</p>

                    <div v-if="optionGroups.length === 0" class="text-sm text-gray-500 italic mb-4">No variations — stock applies to the product as a whole.</div>

                    <div v-for="(group, gi) in optionGroups" :key="gi" class="mb-4 p-4 rounded-xl border border-gray-100 bg-gray-50/80">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <input
                                v-model="group.name"
                                type="text"
                                placeholder="e.g. Size"
                                title="Option group name"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-semibold flex-1 min-w-[10rem] max-w-md"
                                @input="regenerateCombinations"
                            />
                            <button type="button" class="text-xs text-red-600 font-semibold hover:underline shrink-0" @click="removeOptionGroup(gi)">Remove group</button>
                        </div>
                        <div class="flex flex-wrap gap-2 items-center">
                            <span
                                v-for="(val, vi) in group.values"
                                :key="vi"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-50 border border-blue-200 text-sm text-blue-800"
                            >
                                {{ val }}
                                <button type="button" @click="removeGroupValue(gi, vi)" class="text-blue-400 hover:text-red-600 font-bold ml-0.5">&times;</button>
                            </span>
                            <input
                                type="text"
                                placeholder="Value"
                                title="Type an option, then press Enter to add"
                                class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm min-w-[8rem] flex-1 max-w-xs"
                                @keydown.enter.prevent="addGroupValue(gi, $event)"
                            />
                        </div>
                        <p class="text-xs text-gray-500 mt-2">After typing each option (S, M, Red…), press <kbd class="px-1 py-0.5 bg-white border rounded text-[10px]">Enter</kbd> to add it.</p>
                    </div>

                    <!-- Combination Table -->
                    <div v-if="combinations.length > 0" class="mt-6">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Combinations ({{ combinations.length }})</h3>
                        <p class="text-xs text-gray-600 mb-3 leading-relaxed">
                            <span class="font-semibold text-gray-700">Price adjustment</span> is added on top of your retail price (or wholesale price when the order qualifies).
                            Positive numbers make this variant more expensive; negative numbers discount it. Example: retail ₱100 + adj ₱20 = ₱120 for that variant.
                        </p>
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full text-sm min-w-[32rem]">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="text-left px-3 py-2 font-semibold">Variant</th>
                                        <th class="text-left px-3 py-2 font-semibold min-w-[8.5rem] whitespace-normal">Adj (₱)</th>
                                        <th class="text-left px-3 py-2 font-semibold w-32">SKU</th>
                                        <th class="text-left px-3 py-2 font-semibold w-24">Stock</th>
                                        <th class="text-center px-3 py-2 font-semibold w-16">Active</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="(combo, ci) in combinations" :key="combo._label" class="hover:bg-gray-50/50">
                                        <td class="px-3 py-2 font-medium text-gray-900">
                                            {{ combo._label }}
                                            <p v-if="combo._reserved > 0" class="text-[11px] text-amber-800 mt-0.5">{{ combo._reserved }} reserved</p>
                                        </td>
                                        <td class="px-3 py-2 align-top min-w-[8.5rem]">
                                            <input
                                                v-model.number="combo.price_adjustment"
                                                type="number"
                                                step="0.01"
                                                title="Added to base price; + surcharge or − discount"
                                                class="w-full min-w-[6rem] px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-blue-500/40 focus:border-blue-500"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                v-model="combo.sku"
                                                type="text"
                                                placeholder="Optional"
                                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-blue-500/40 focus:border-blue-500"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                v-model.number="combo.stock"
                                                type="number"
                                                :min="combo._reserved || 0"
                                                placeholder="0"
                                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-blue-500/40 focus:border-blue-500"
                                            />
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input v-model="combo.is_active" type="checkbox" class="rounded border-gray-300 text-blue-600" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Warranty / Expiry -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Warranty &amp; expiration</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="rounded-xl border border-gray-100 p-4 bg-gray-50/80">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                <input v-model="fields.has_warranty" type="checkbox" class="rounded border-gray-300 text-blue-600" />
                                Has warranty
                            </label>
                            <input
                                v-if="fields.has_warranty"
                                v-model.number="fields.warranty_months"
                                type="number"
                                min="1"
                                max="120"
                                placeholder="Months"
                                class="mt-3 w-full px-4 py-3 border border-gray-300 rounded-xl"
                            />
                        </div>
                        <div class="rounded-xl border border-gray-100 p-4 bg-gray-50/80">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                <input v-model="fields.has_expiry" type="checkbox" class="rounded border-gray-300 text-blue-600" />
                                Has expiration
                            </label>
                            <input v-if="fields.has_expiry" v-model="fields.expiry_date" type="date" class="mt-3 w-full px-4 py-3 border border-gray-300 rounded-xl" />
                            <input
                                v-if="fields.has_expiry"
                                v-model="fields.batch_number"
                                type="text"
                                placeholder="Batch / lot number"
                                class="mt-3 w-full px-4 py-3 border border-gray-300 rounded-xl"
                            />
                        </div>
                    </div>
                </section>

                <!-- Visibility + stock -->
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 sm:p-8 space-y-6">
                    <label class="inline-flex items-center gap-3 cursor-pointer">
                        <input v-model="fields.is_active" type="checkbox" class="h-5 w-5 rounded border-gray-300 text-blue-600" />
                        <span class="text-sm font-semibold text-gray-900">Product is active (visible to customers)</span>
                    </label>

                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Stock</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                            <div
                                v-if="combinations.length === 0"
                                class="flex h-full min-h-0 flex-col rounded-xl border border-gray-200 bg-gray-50/70 p-4 sm:p-5"
                            >
                                <label class="shrink-0 text-sm font-semibold text-gray-800">Quantity on hand <span class="text-red-500">*</span></label>
                                <p class="mt-1.5 flex-1 text-xs leading-relaxed text-gray-500">Total units for this product when you are not using variants.</p>
                                <input
                                    v-model.number="fields.initial_quantity"
                                    type="number"
                                    :min="baseReserved"
                                    required
                                    class="mt-4 w-full shrink-0 px-4 py-3 border border-gray-300 rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500"
                                />
                                <p v-if="baseReserved > 0" class="text-xs text-amber-800 mt-2 shrink-0">
                                    {{ baseReserved }} unit(s) reserved on open orders (minimum on-hand).
                                </p>
                            </div>
                            <div
                                :class="
                                    combinations.length === 0
                                        ? 'flex h-full min-h-0 flex-col rounded-xl border border-gray-200 bg-gray-50/70 p-4 sm:p-5'
                                        : 'sm:col-span-2 rounded-xl border border-gray-200 bg-gray-50/70 p-4 sm:p-5'
                                "
                            >
                                <label class="shrink-0 text-sm font-semibold text-gray-800">Low-stock alert at <span class="text-red-500">*</span></label>
                                <p class="mt-1.5 flex-1 text-xs leading-relaxed text-gray-500">We’ll flag this product when on-hand quantity is at or below this number.</p>
                                <input
                                    v-model.number="fields.reorder_level"
                                    type="number"
                                    min="0"
                                    required
                                    class="mt-4 w-full max-w-xs shrink-0 px-4 py-3 border border-gray-300 rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="flex-1 bg-blue-600 text-white px-6 py-3.5 rounded-xl font-bold hover:bg-blue-700 disabled:opacity-50 shadow-md"
                    >
                        {{ submitting ? 'Saving…' : 'Save changes' }}
                    </button>
                    <Link href="/owner/inventory" class="px-6 py-3.5 border-2 border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 text-center">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import BarcodeScannerModal from '@/Components/BarcodeScannerModal.vue';

const props = defineProps({
    product: Object,
    inventory: Object,
    variation_stocks: { type: Object, default: () => ({}) },
    variation_reserved: { type: Object, default: () => ({}) },
    base_reserved_quantity: { type: Number, default: 0 },
    categories: Array,
    medicine_category_ids: { type: Array, default: () => [] },
});

const baseReserved = computed(() => Math.max(0, Number(props.base_reserved_quantity ?? 0)));

const page = usePage();
const pageErrors = computed(() => page.props.errors || {});

function dateInput(val) {
    if (!val) return '';
    if (typeof val === 'string') return val.slice(0, 10);
    if (val.date) return String(val.date).slice(0, 10);
    return '';
}

const fields = ref({
    name: props.product.name,
    category_id: props.product.category_id,
    product_type: props.product.product_type || 'consumable',
    brand: props.product.brand || '',
    model: props.product.model || '',
    sku: props.product.sku || '',
    requires_prescription: !!props.product.requires_prescription,
    vehicle_requirement: props.product.vehicle_requirement || 'motorcycle',
    description: props.product.description,
    base_price: props.product.base_price,
    wholesale_price: props.product.wholesale_price || '',
    wholesale_min_qty: props.product.wholesale_min_qty || '',
    has_warranty: props.product.has_warranty || false,
    warranty_months: props.product.warranty_months || '',
    has_expiry: props.product.has_expiry || false,
    expiry_date: dateInput(props.inventory?.expiry_date),
    batch_number: props.inventory?.batch_number || '',
    is_active: props.product.is_active,
    barcode: props.product.barcode || '',
    initial_quantity: 0,
    reorder_level: 10,
});

const baseInventoryRow = computed(
    () => props.product.inventory?.find((i) => i.product_variation_id == null) ?? props.inventory
);

const removedImageIds = ref([]);
const newFiles = ref([]);
const newPreviews = ref([]);
const primaryPick = ref('');
const submitting = ref(false);

const visibleExisting = computed(() => (props.product.images || []).filter((img) => !removedImageIds.value.includes(img.id)));

watch([visibleExisting, newPreviews, removedImageIds], () => { syncPrimaryDefault(); });

function syncPrimaryDefault() {
    const firstE = visibleExisting.value[0];
    const hasNew = newPreviews.value.length > 0;
    if (!firstE && !hasNew) { primaryPick.value = ''; return; }
    const current = primaryPick.value;
    const stillValid =
        (current.startsWith('e:') && visibleExisting.value.some((img) => `e:${img.id}` === current)) ||
        (current.startsWith('n:') && hasNew && Number(current.slice(2)) < newPreviews.value.length);
    if (stillValid) return;
    if (firstE) primaryPick.value = `e:${firstE.id}`;
    else primaryPick.value = 'n:0';
}

const categoryGroups = computed(() => {
    const cats = props.categories || [];
    const parents = cats.filter((c) => !c.parent_id).sort((a, b) => a.name.localeCompare(b.name));
    return parents.map((parent) => ({
        parent,
        children: cats.filter((c) => c.parent_id === parent.id).sort((a, b) => a.name.localeCompare(b.name)),
    }));
});

const selectedCategoryHint = computed(() => {
    const id = Number(fields.value.category_id);
    if (!id) return '';
    const cats = props.categories || [];
    const flat = cats.flatMap((p) => [p, ...(p.children || [])]);
    const match = flat.find((c) => Number(c.id) === id);
    return match?.description || '';
});

const isMedicineCategory = computed(() => {
    const id = Number(fields.value.category_id);
    if (!id) return false;
    return (props.medicine_category_ids || []).map(Number).includes(id);
});

watch(isMedicineCategory, (on) => { if (!on) fields.value.requires_prescription = false; });

// --- Variation Group Builder ---
const optionGroups = ref([]);
const combinations = ref([]);

function initVariationsFromProduct() {
    const savedGroups = props.product.variation_options;
    const existingVars = [...(props.product.variations || [])].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));

    if (Array.isArray(savedGroups) && savedGroups.length > 0) {
        optionGroups.value = savedGroups.map(g => ({ name: g.name, values: [...g.values] }));
        regenerateCombinations();

        existingVars.forEach(v => {
            const label = v.combination
                ? Object.entries(v.combination).map(([k, val]) => `${k}: ${val}`).join(' / ')
                : `${v.option_name}: ${v.option_value}`;
            const match = combinations.value.find(c => c._label === label);
            if (match) {
                match.id = v.id;
                match.price_adjustment = Number(v.price_adjustment ?? 0);
                match.sku = v.sku || '';
                match.is_active = v.is_active !== false;
                match.stock = Number(props.variation_stocks[v.id] ?? 0);
                match._reserved = Number(props.variation_reserved?.[v.id] ?? 0);
            }
        });
    } else if (existingVars.length > 0) {
        const groupMap = {};
        existingVars.forEach(v => {
            const name = v.option_name || 'Option';
            if (!groupMap[name]) groupMap[name] = [];
            if (!groupMap[name].includes(v.option_value)) groupMap[name].push(v.option_value);
        });

        optionGroups.value = Object.entries(groupMap).map(([name, values]) => ({ name, values }));
        regenerateCombinations();

        existingVars.forEach(v => {
            const label = `${v.option_name}: ${v.option_value}`;
            const match = combinations.value.find(c => c._label === label);
            if (match) {
                match.id = v.id;
                match.price_adjustment = Number(v.price_adjustment ?? 0);
                match.sku = v.sku || '';
                match.is_active = v.is_active !== false;
                match.stock = Number(props.variation_stocks[v.id] ?? 0);
                match._reserved = Number(props.variation_reserved?.[v.id] ?? 0);
            }
        });
    }
}

function addOptionGroup() {
    optionGroups.value.push({ name: '', values: [] });
}

function removeOptionGroup(gi) {
    optionGroups.value.splice(gi, 1);
    regenerateCombinations();
}

function addGroupValue(gi, event) {
    const val = event.target.value.trim();
    if (!val) return;
    if (optionGroups.value[gi].values.includes(val)) { event.target.value = ''; return; }
    optionGroups.value[gi].values.push(val);
    event.target.value = '';
    regenerateCombinations();
}

function removeGroupValue(gi, vi) {
    optionGroups.value[gi].values.splice(vi, 1);
    regenerateCombinations();
}

function regenerateCombinations() {
    const groups = optionGroups.value.filter(g => g.name.trim() && g.values.length > 0);
    if (groups.length === 0) { combinations.value = []; return; }

    const oldMap = {};
    combinations.value.forEach(c => { oldMap[c._label] = c; });

    const cartesian = (arrays) => {
        return arrays.reduce((acc, arr) => acc.flatMap(combo => arr.map(val => [...combo, val])), [[]]);
    };

    const valueSets = groups.map(g => g.values);
    const combos = cartesian(valueSets);

    combinations.value = combos.map(vals => {
        const combo = {};
        groups.forEach((g, i) => { combo[g.name] = vals[i]; });
        const label = groups.map((g, i) => `${g.name}: ${vals[i]}`).join(' / ');

        const old = oldMap[label];
        return {
            _label: label,
            combination: combo,
            id: old?.id ?? null,
            price_adjustment: old?.price_adjustment ?? 0,
            sku: old?.sku ?? '',
            stock: old?.stock ?? 0,
            is_active: old?.is_active ?? true,
            _reserved: old?._reserved ?? 0,
        };
    });
}

onMounted(() => {
    const base = baseInventoryRow.value;
    if (!(props.product.variations || []).length) {
        const q = Number(base?.quantity ?? 0);
        fields.value.initial_quantity = Number.isFinite(q) ? q : 0;
    }
    const rl = Number(base?.reorder_level ?? 10);
    fields.value.reorder_level = Number.isFinite(rl) ? rl : 10;
    syncPrimaryDefault();
    initVariationsFromProduct();
});

function revokeNew() {
    newPreviews.value.forEach((url) => { if (url && url.startsWith('blob:')) URL.revokeObjectURL(url); });
}
onUnmounted(() => { revokeNew(); });

function onNewImages(e) {
    revokeNew();
    const files = Array.from(e.target.files || []).filter(Boolean);
    newFiles.value = files;
    newPreviews.value = files.map((f) => URL.createObjectURL(f));
}

function appendBool(fd, key, val) { fd.append(key, val ? '1' : '0'); }

function submitForm() {
    submitting.value = true;

    const fd = new FormData();
    fd.append('_method', 'put');
    fd.append('name', fields.value.name);
    fd.append('description', fields.value.description);
    fd.append('category_id', String(fields.value.category_id || ''));
    fd.append('product_type', fields.value.product_type);
    fd.append('brand', fields.value.brand);
    fd.append('model', fields.value.model);
    fd.append('sku', fields.value.sku);
    appendBool(fd, 'requires_prescription', fields.value.requires_prescription);
    fd.append('vehicle_requirement', fields.value.vehicle_requirement);
    fd.append('base_price', String(fields.value.base_price ?? ''));
    if (fields.value.wholesale_price !== '' && fields.value.wholesale_price != null) {
        fd.append('wholesale_price', String(fields.value.wholesale_price));
    }
    if (fields.value.wholesale_min_qty !== '' && fields.value.wholesale_min_qty != null) {
        fd.append('wholesale_min_qty', String(fields.value.wholesale_min_qty));
    }
    appendBool(fd, 'has_warranty', fields.value.has_warranty);
    if (fields.value.has_warranty && fields.value.warranty_months) {
        fd.append('warranty_months', String(fields.value.warranty_months));
    }
    appendBool(fd, 'has_expiry', fields.value.has_expiry);
    appendBool(fd, 'is_active', fields.value.is_active);
    if (fields.value.barcode) fd.append('barcode', fields.value.barcode);

    removedImageIds.value.forEach((id) => fd.append('removed_image_ids[]', String(id)));
    newFiles.value.forEach((f) => fd.append('images[]', f));

    if (combinations.value.length > 0) {
        const varsPayload = combinations.value.map((c) => {
            const payload = {
                option_name: Object.keys(c.combination).join(' / '),
                option_value: Object.values(c.combination).join(' / '),
                combination: c.combination,
                price_adjustment: c.price_adjustment ?? 0,
                sku: c.sku || null,
                is_active: c.is_active,
            };
            if (c.id != null) payload.id = c.id;
            return payload;
        });
        fd.append('variations_json', JSON.stringify(varsPayload));
        fd.append('variation_stocks_json', JSON.stringify(combinations.value.map(c => c.stock ?? 0)));

        const groupsDef = optionGroups.value
            .filter(g => g.name.trim() && g.values.length > 0)
            .map(g => ({ name: g.name.trim(), values: g.values }));
        fd.append('variation_options_json', JSON.stringify(groupsDef));
    } else {
        fd.append('variations_json', '[]');
        fd.append('variation_stocks_json', '[]');
    }

    fd.append('initial_quantity', String(fields.value.initial_quantity ?? 0));
    fd.append('reorder_level', String(fields.value.reorder_level ?? 10));
    if (fields.value.has_expiry && fields.value.expiry_date) fd.append('expiry_date', fields.value.expiry_date);
    if (fields.value.batch_number) fd.append('batch_number', fields.value.batch_number);

    if (primaryPick.value.startsWith('e:')) {
        fd.append('primary_image_id', primaryPick.value.slice(2));
    } else if (primaryPick.value.startsWith('n:') && newFiles.value.length) {
        fd.append('primary_image_index', primaryPick.value.slice(2));
    }

    router.post(`/owner/inventory/${props.product.id}`, fd, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => { submitting.value = false; },
    });
}
</script>
