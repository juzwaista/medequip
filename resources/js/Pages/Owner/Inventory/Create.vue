<template>
    <OwnerLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <div class="flex items-center text-sm text-ink-soft mb-3">
                    <Link href="/owner/inventory" class="hover:text-brand">Inventory</Link>
                    <span class="mx-2 text-ink-faint">/</span>
                    <span class="text-ink font-medium">Add product</span>
                </div>
                <h1 class="text-3xl font-bold text-ink">Add product</h1>
                <p class="text-ink-soft mt-2">Photos, variations, and stock — all in one place.</p>
            </div>

            <div v-if="pageErrors && Object.keys(pageErrors).length > 0" class="mb-6 rounded-card bg-red-50 border border-red-200 p-4">
                <p class="text-sm font-medium text-red-800 mb-2">Please fix the following:</p>
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    <li v-for="(err, key) in pageErrors" :key="key">{{ Array.isArray(err) ? err[0] : err }}</li>
                </ul>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Gallery -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold   text-ink-soft mb-4">Photos</h2>
                    <p class="text-sm text-ink-soft mb-4">Upload one or more images. Choose which one is the main display.</p>
                    <input
                        type="file"
                        accept="image/*"
                        multiple
                        @change="onImagesChange"
                        class="block w-full max-w-md text-sm text-ink-soft file:mr-4 file:py-2.5 file:px-4 file:rounded-control file:border-0 file:font-semibold file:bg-brand-tint file:text-brand-dark"
                    />
                    <div v-if="imagePreviews.length" class="mt-4 flex flex-wrap gap-4">
                        <div v-for="(src, idx) in imagePreviews" :key="idx" class="text-center">
                            <div class="relative w-28 h-28 rounded-card border border-line overflow-hidden bg-mist">
                                <img :src="src" alt="" class="w-full h-full object-cover" />
                            </div>
                            <label class="mt-2 flex items-center justify-center gap-1 text-xs text-ink cursor-pointer">
                                <input v-model.number="primaryImageIndex" type="radio" :value="idx" class="text-brand" />
                                Main
                            </label>
                        </div>
                    </div>
                    <p v-if="pageErrors.images" class="text-red-600 text-sm mt-2">{{ pageErrors.images }}</p>
                </section>

                <!-- Barcode -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold   text-ink-soft mb-4">Barcode</h2>
                    <div class="flex gap-2 max-w-xl">
                        <input
                            v-model="fields.barcode"
                            type="text"
                            placeholder="Scan or type"
                            class="flex-1 px-4 py-3 border border-line rounded-card font-mono text-sm"
                        />
                        <BarcodeScannerModal @scanned="(code) => (fields.barcode = code)" />
                    </div>
                    <p v-if="pageErrors.barcode" class="text-red-600 text-sm mt-2">{{ pageErrors.barcode }}</p>
                </section>

                <!-- SKU -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold   text-ink-soft mb-4">Product SKU</h2>
                    <div>
                        <input
                            v-model="fields.sku"
                            type="text"
                            placeholder="Optional (e.g., MED-001)"
                            class="w-full px-4 py-3 border border-line rounded-card font-mono text-sm"
                        />
                        <p v-if="pageErrors.sku" class="text-red-600 text-sm mt-2">{{ pageErrors.sku }}</p>
                        <p class="text-xs text-ink-soft mt-2">Leave empty to auto-generate.</p>
                    </div>
                </section>

                <!-- Basics -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold   text-ink-soft mb-4">Basic information</h2>
                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-ink mb-1.5">Product name <span class="text-red-500">*</span></label>
                            <input v-model="fields.name" type="text" required class="w-full px-4 py-3 border border-line rounded-card" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-ink mb-1.5">Category <span class="text-red-500">*</span></label>
                                <select v-model="fields.category_id" required class="w-full px-4 py-3 border border-line rounded-card bg-white shadow-sm focus:ring-2 focus:ring-brand/30 focus:border-brand">
                                    <option value="">Select a category</option>
                                    <template v-for="group in categoryGroups" :key="group.parent.id">
                                        <optgroup :label="group.parent.name">
                                            <option :value="group.parent.id">{{ group.parent.name }} (general)</option>
                                            <option v-for="c in group.children" :key="c.id" :value="c.id">{{ c.name }}</option>
                                        </optgroup>
                                    </template>
                                </select>
                                <p class="mt-1.5 min-h-[2.5rem] text-xs leading-relaxed text-ink-soft">{{ selectedCategoryHint || '' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-ink mb-1.5">Product type <span class="text-red-500">*</span></label>
                                <select v-model="fields.product_type" required class="w-full px-4 py-3 border border-line rounded-card bg-white shadow-sm focus:ring-2 focus:ring-brand/30 focus:border-brand">
                                    <option value="consumable">Consumable</option>
                                    <option value="equipment">Equipment</option>
                                </select>
                                <p class="text-xs text-ink-soft mt-1.5 min-h-[2.5rem]">Shown on the product page (e.g. badge for equipment vs consumable).</p>
                            </div>
                        </div>
                        <div v-if="isMedicineCategory" class="rounded-card border border-brand-soft bg-brand-tint/60 p-4">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input v-model="fields.requires_prescription" type="checkbox" class="mt-1 rounded border-line text-brand" />
                                <span class="text-sm font-medium text-ink">Requires a valid prescription (customer uploads after order)</span>
                            </label>
                        </div>
                        <div class="rounded-card border border-orange-100 bg-orange-50 p-4 sm:p-5">
                            <label class="block text-sm font-semibold text-ink mb-1.5">Vehicle requirement for delivery <span class="text-red-500">*</span></label>
                            <select v-model="fields.vehicle_requirement" required class="w-full px-4 py-3 border border-orange-200 rounded-card bg-white shadow-sm focus:ring-2 focus:ring-orange-500/30 focus:border-orange-500">
                                <option value="motorcycle">Fits in Motorcycle (Small)</option>
                                <option value="car_sedan">Requires Sedan (Medium)</option>
                                <option value="car_hatchback">Requires Hatchback / SUV (Large)</option>
                                <option value="pickup_truck">Requires Pickup Truck (Extra Large)</option>
                                <option value="box_truck">Requires Box Truck (Heavy/Bulky)</option>
                            </select>
                            <p class="text-xs text-ink-soft mt-2">This restricts which couriers can accept orders containing this product. Select the minimum vehicle size required.</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-ink mb-1.5">Brand <span class="text-red-500">*</span></label>
                                <input v-model="fields.brand" type="text" required class="w-full px-4 py-3 border border-line rounded-card" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-ink mb-1.5">Model <span class="text-red-500">*</span></label>
                                <input v-model="fields.model" type="text" required class="w-full px-4 py-3 border border-line rounded-card" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-ink mb-1.5">Description <span class="text-red-500">*</span></label>
                            <textarea v-model="fields.description" required rows="4" class="w-full px-4 py-3 border border-line rounded-card" />
                        </div>
                    </div>
                </section>

                <!-- Pricing -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold   text-ink-soft mb-4">Pricing</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                        <!-- How the product is sold: pack size -->
                        <div class="sm:col-span-2 rounded-card border border-line bg-mist/70 p-4 sm:p-5">
                            <label class="text-sm font-semibold text-ink">How do you sell this product?</label>
                            <p class="text-xs text-ink-soft mt-1.5 mb-3">The prices below are per selling unit. If one unit holds several pieces (for example a box of 10), enter how many. Wholesale minimums are counted in <strong>pieces</strong>, so buyers of boxes qualify correctly.</p>
                            <div class="flex flex-wrap items-end gap-4">
                                <div>
                                    <label class="block text-xs font-bold   text-ink-soft mb-1">Sold as</label>
                                    <input v-model="fields.unit_label" list="unit-label-options" type="text" maxlength="30" placeholder="piece" class="w-40 px-4 py-2.5 border border-line rounded-card bg-white shadow-sm focus:ring-2 focus:ring-brand/30 focus:border-brand" />
                                    <datalist id="unit-label-options">
                                        <option value="piece" /><option value="box" /><option value="pack" /><option value="bottle" /><option value="vial" /><option value="roll" /><option value="set" /><option value="case" />
                                    </datalist>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold   text-ink-soft mb-1">Pieces per {{ !fields.unit_label || fields.unit_label.toLowerCase() === 'piece' ? 'unit' : fields.unit_label }}</label>
                                    <input v-model.number="fields.units_per_pack" type="number" min="1" max="100000" class="w-32 px-4 py-2.5 border border-line rounded-card bg-white shadow-sm focus:ring-2 focus:ring-brand/30 focus:border-brand" />
                                </div>
                            </div>
                            <p v-if="productPack > 1" class="text-xs text-brand-dark mt-3">
                                Each {{ fields.unit_label || 'unit' }} = {{ productPack }} pcs
                                <template v-if="Number(fields.base_price) > 0"> · about ₱{{ (Number(fields.base_price) / productPack).toLocaleString(undefined, { maximumFractionDigits: 2 }) }} per piece at retail</template>.
                                To also sell single pieces or another pack size, add an option below and set its “Pieces / unit”.
                            </p>
                        </div>

                        <div class="flex h-full min-h-0 flex-col rounded-card border border-line bg-mist/70 p-4 sm:p-5">
                            <label class="shrink-0 text-sm font-semibold text-ink">Retail price (₱) <span class="text-red-500">*</span></label>
                            <p class="mt-1.5 min-h-[3.25rem] flex-1 text-xs leading-relaxed text-ink-soft">Your main selling price per unit. Variant price adjustments add to or subtract from this (and from wholesale when it applies).</p>
                            <input
                                v-model.number="fields.base_price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="mt-4 w-full shrink-0 px-4 py-3 border border-line rounded-card bg-white shadow-sm focus:ring-2 focus:ring-brand/30 focus:border-brand"
                            />
                        </div>
                        <div class="flex h-full min-h-0 flex-col rounded-card border border-line bg-mist/70 p-4 sm:p-5">
                            <label class="shrink-0 text-sm font-semibold text-ink flex items-center gap-1.5">
                                Wholesale price (₱)
                                <Tooltip content="Wholesale pricing applies to approved B2B Corporate buyers when they meet the minimum quantity." position="top">
                                    <svg class="w-4 h-4 text-ink-faint hover:text-ink-soft transition cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </Tooltip>
                            </label>
                            <p class="mt-1.5 min-h-[3.25rem] flex-1 text-xs leading-relaxed text-ink-soft">Optional lower price for bulk orders. If you enter a price, set the minimum quantity below.</p>
                            <input
                                v-model.number="fields.wholesale_price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-4 w-full shrink-0 px-4 py-3 border border-line rounded-card bg-white shadow-sm focus:ring-2 focus:ring-brand/30 focus:border-brand"
                            />
                        </div>
                        <div v-if="fields.wholesale_price" class="sm:col-span-2 rounded-card border border-line bg-mist/70 p-4 sm:p-5">
                            <label class="flex items-center gap-1.5 text-sm font-semibold text-ink">
                                Minimum wholesale quantity (pieces) <span class="text-red-500">*</span>
                                <Tooltip content="Corporate buyers must order at least this many units to qualify for wholesale rates." position="top">
                                    <svg class="w-4 h-4 text-ink-faint hover:text-ink-soft transition cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </Tooltip>
                            </label>
                            <p class="text-xs text-ink-soft mt-1.5 mb-3">Buyers must order at least this many <strong>pieces</strong> in total (across all pack sizes of this product) to get the wholesale price.</p>
                            <input
                                v-model.number="fields.wholesale_min_qty"
                                type="number"
                                min="2"
                                required
                                class="w-full max-w-xs px-4 py-3 border border-line rounded-card bg-white shadow-sm focus:ring-2 focus:ring-brand/30 focus:border-brand"
                            />
                            <p v-if="productPack > 1 && Number(fields.wholesale_min_qty) > 0" class="text-xs text-brand-dark mt-2">
                                = {{ Math.ceil(Number(fields.wholesale_min_qty) / productPack) }} {{ pluralize(fields.unit_label || 'unit', Math.ceil(Number(fields.wholesale_min_qty) / productPack)) }} of {{ productPack }} pcs.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Variations -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <div class="flex items-center justify-between gap-4 mb-2">
                        <h2 class="text-xs font-bold   text-ink-soft">Options / Variations</h2>
                        <button type="button" class="text-sm font-semibold text-brand hover:underline" @click="addOptionGroup">+ Add option group</button>
                    </div>
                    <p class="text-sm text-ink-soft mb-4">Define option groups (e.g. Size, Color) and their values. Combinations are auto-generated.</p>

                    <!-- Option Groups -->
                    <div v-if="optionGroups.length === 0" class="text-sm text-ink-soft italic mb-4">No variations — stock applies to the product as a whole.</div>

                    <div v-for="(group, gi) in optionGroups" :key="gi" class="mb-4 p-4 rounded-card border border-line bg-mist/80">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <input
                                v-model="group.name"
                                type="text"
                                placeholder="e.g. Size"
                                title="Option group name"
                                class="px-3 py-2 border border-line rounded-control text-sm font-semibold flex-1 min-w-[10rem] max-w-md"
                                @input="regenerateCombinations"
                            />
                            <button type="button" class="text-xs text-red-600 font-semibold hover:underline shrink-0" @click="removeOptionGroup(gi)">Remove group</button>
                        </div>
                        <div class="flex flex-wrap gap-2 items-center">
                            <span
                                v-for="(val, vi) in group.values"
                                :key="vi"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-control bg-brand-tint border border-brand-soft text-sm text-brand-dark"
                            >
                                {{ val }}
                                <button type="button" @click="removeGroupValue(gi, vi)" class="text-brand hover:text-red-600 font-bold ml-0.5">&times;</button>
                            </span>
                            <input
                                type="text"
                                placeholder="Value"
                                title="Type an option, then press Enter to add"
                                class="px-3 py-1.5 border border-line rounded-control text-sm min-w-[8rem] flex-1 max-w-xs"
                                @keydown.enter.prevent="addGroupValue(gi, $event)"
                            />
                        </div>
                        <p class="text-xs text-ink-soft mt-2">After typing each option (S, M, Red…), press <kbd class="px-1 py-0.5 bg-white border rounded text-xs">Enter</kbd> to add it.</p>
                    </div>

                    <!-- Combination Table -->
                    <div v-if="combinations.length > 0" class="mt-6">
                        <h3 class="text-xs font-bold   text-ink-soft mb-1">Combinations ({{ combinations.length }})</h3>
                        <p class="text-xs text-ink-soft mb-3 leading-relaxed">
                            <span class="font-semibold text-ink">Price adjustment</span> is added on top of your retail price (or wholesale price when the order qualifies).
                            Positive numbers make this variant more expensive; negative numbers discount it. Example: retail ₱100 + adj ₱20 = ₱120 for that variant.
                        </p>
                        <div class="overflow-x-auto rounded-card border border-line">
                            <table class="w-full text-sm min-w-[32rem]">
                                <thead class="bg-mist text-ink-soft">
                                    <tr>
                                        <th class="text-left px-3 py-2 font-semibold">Variant</th>
                                        <th class="text-left px-3 py-2 font-semibold min-w-[8.5rem] whitespace-normal">Adj (₱)</th>
                                        <th class="text-left px-3 py-2 font-semibold w-40">Pieces / unit</th>
                                        <th class="text-left px-3 py-2 font-semibold w-28">Retail</th>
                                        <th class="text-left px-3 py-2 font-semibold w-32">SKU</th>
                                        <th class="text-left px-3 py-2 font-semibold w-24">Stock</th>
                                        <th class="text-center px-3 py-2 font-semibold w-16">Active</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    <tr v-for="(combo, ci) in combinations" :key="combo._label" class="hover:bg-mist/50">
                                        <td class="px-3 py-2 font-medium text-ink">{{ combo._label }}</td>
                                        <td class="px-3 py-2 align-top min-w-[8.5rem]">
                                            <input
                                                v-model.number="combo.price_adjustment"
                                                type="number"
                                                step="0.01"
                                                title="Added to base price; + surcharge or − discount"
                                                class="w-full min-w-[6rem] px-2 py-1.5 border border-line rounded-control text-sm focus:ring-1 focus:ring-brand/40 focus:border-brand"
                                            />
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <div class="flex gap-1">
                                                <input v-model.number="combo.units_per_pack" type="number" min="1" :placeholder="String(productPack)" title="Pieces in one unit of this option. Leave blank to use the product's." class="w-16 px-2 py-1.5 border border-line rounded-control text-sm focus:ring-1 focus:ring-brand/40 focus:border-brand" />
                                                <input v-model="combo.unit_label" type="text" maxlength="30" :placeholder="fields.unit_label || 'piece'" title="What this option is called (box, piece...)" class="w-20 px-2 py-1.5 border border-line rounded-control text-sm focus:ring-1 focus:ring-brand/40 focus:border-brand" />
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 align-top text-ink whitespace-nowrap">₱{{ comboRetail(combo) }}</td>
                                        <td class="px-3 py-2">
                                            <input v-model="combo.sku" type="text" placeholder="Optional" class="w-full px-2 py-1.5 border border-line rounded-control text-sm focus:ring-1 focus:ring-brand/40 focus:border-brand" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input v-model.number="combo.stock" type="number" min="0" placeholder="0" class="w-full px-2 py-1.5 border border-line rounded-control text-sm focus:ring-1 focus:ring-brand/40 focus:border-brand" />
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input v-model="combo.is_active" type="checkbox" class="rounded border-line text-brand" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <p v-if="pageErrors.variation_stocks_json" class="text-red-600 text-sm mt-2">{{ pageErrors.variation_stocks_json }}</p>
                </section>

                <!-- Warranty / Expiry -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold   text-ink-soft mb-4">Warranty &amp; expiration</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="rounded-card border border-line p-4 bg-mist/80">
                            <label class="flex items-center gap-2 text-sm font-semibold text-ink">
                                <input v-model="fields.has_warranty" type="checkbox" class="rounded border-line text-brand" />
                                Has warranty
                            </label>
                            <div v-if="fields.has_warranty" class="mt-3">
                                <label class="block text-xs font-bold   text-ink-soft mb-1">Warranty Months <span class="text-red-500">*</span></label>
                                <input
                                    v-model.number="fields.warranty_months"
                                    type="number"
                                    min="1"
                                    max="120"
                                    placeholder="e.g. 12"
                                    required
                                    class="w-full px-4 py-3 border border-line rounded-card bg-white shadow-sm"
                                />
                            </div>
                        </div>
                        <div class="rounded-card border border-line p-4 bg-mist/80">
                            <label class="flex items-center gap-2 text-sm font-semibold text-ink">
                                <input v-model="fields.has_expiry" type="checkbox" class="rounded border-line text-brand" />
                                Has expiration
                            </label>
                            
                            <div v-if="fields.has_expiry" class="mt-3 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold   text-ink-soft mb-1">Manufacturing Date</label>
                                        <input v-model="fields.manufacturing_date" type="date" class="w-full px-4 py-3 border border-line rounded-card bg-white shadow-sm" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold   text-ink-soft mb-1">Expiration Date <span class="text-red-500">*</span></label>
                                        <input v-model="fields.expiry_date" type="date" required class="w-full px-4 py-3 border border-line rounded-card bg-white shadow-sm" />
                                    </div>
                                </div>

                                <div v-if="shelfLifeText || timeUntilExpiryText" class="p-3 rounded-control bg-brand-tint border border-brand-soft space-y-2">
                                    <div v-if="shelfLifeText" class="flex justify-between items-center text-xs">
                                        <span class="text-ink-soft font-semibold ">Total Shelf Life:</span>
                                        <span class="text-brand-dark font-bold bg-brand-tint/50 px-1.5 py-0.5 rounded">{{ shelfLifeText }}</span>
                                    </div>
                                    <div v-if="timeUntilExpiryText" class="flex justify-between items-center text-xs">
                                        <span class="text-ink-soft font-semibold ">Time Remaining:</span>
                                        <span class="font-bold px-1.5 py-0.5 rounded" :class="timeUntilExpiryText === 'Expired' ? 'bg-red-100 text-red-700' : 'bg-brand-tint text-brand-dark'">
                                            {{ timeUntilExpiryText }}
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold   text-ink-soft mb-1">Batch / Lot Number</label>
                                    <input
                                        v-model="fields.batch_number"
                                        type="text"
                                        placeholder="e.g. BATCH-2024-001"
                                        class="w-full px-4 py-3 border border-line rounded-card bg-white shadow-sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Stock -->
                <section class="rounded-card border border-line bg-white shadow-sm p-6 sm:p-8">
                    <h2 class="text-xs font-bold   text-ink-soft mb-4">Stock</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                        <div
                            v-if="combinations.length === 0"
                            class="flex h-full min-h-0 flex-col rounded-card border border-line bg-mist/70 p-4 sm:p-5"
                        >
                            <label class="shrink-0 text-sm font-semibold text-ink">Quantity on hand <span class="text-red-500">*</span></label>
                            <p class="mt-1.5 flex-1 text-xs leading-relaxed text-ink-soft">Total units for this product when you are not using variants.</p>
                            <input
                                v-model.number="fields.initial_quantity"
                                type="number"
                                min="0"
                                required
                                class="mt-4 w-full shrink-0 px-4 py-3 border border-line rounded-card bg-white shadow-sm focus:ring-2 focus:ring-brand/30 focus:border-brand"
                            />
                        </div>
                        <div
                            :class="combinations.length === 0 ? 'flex h-full min-h-0 flex-col rounded-card border border-line bg-mist/70 p-4 sm:p-5' : 'sm:col-span-2 rounded-card border border-line bg-mist/70 p-4 sm:p-5'"
                        >
                            <label class="shrink-0 text-sm font-semibold text-ink">Low-stock alert at <span class="text-red-500">*</span></label>
                            <p class="mt-1.5 flex-1 text-xs leading-relaxed text-ink-soft">We’ll flag this product when on-hand quantity is at or below this number.</p>
                            <input
                                v-model.number="fields.reorder_level"
                                type="number"
                                min="0"
                                required
                                class="mt-4 w-full max-w-xs shrink-0 px-4 py-3 border border-line rounded-card bg-white shadow-sm focus:ring-2 focus:ring-brand/30 focus:border-brand"
                            />
                        </div>
                    </div>
                </section>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="flex-1 bg-brand text-white px-6 py-3.5 rounded-card font-bold hover:bg-brand-dark disabled:opacity-50 shadow-md"
                    >
                        {{ submitting ? 'Creating…' : 'Create product' }}
                    </button>
                    <Link href="/owner/inventory" class="px-6 py-3.5 border-2 border-line rounded-card font-semibold text-ink hover:bg-mist text-center">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import Tooltip from '@/Components/Tooltip.vue';
import BarcodeScannerModal from '@/Components/BarcodeScannerModal.vue';

const props = defineProps({
    categories: Array,
    medicine_category_ids: { type: Array, default: () => [] },
});

const page = usePage();
const pageErrors = computed(() => page.props.errors || {});

const fields = ref({
    name: '',
    description: '',
    category_id: '',
    product_type: 'consumable',
    brand: '',
    model: '',
    sku: '',
    requires_prescription: false,
    vehicle_requirement: 'motorcycle',
    barcode: '',
    base_price: '',
    wholesale_price: '',
    wholesale_min_qty: '',
    unit_label: 'piece',
    units_per_pack: 1,
    has_warranty: false,
    warranty_months: '',
    has_expiry: false,
    manufacturing_date: '',
    expiry_date: '',
    batch_number: '',
    initial_quantity: 0,
    reorder_level: 10,
});

const imageFiles = ref([]);
const imagePreviews = ref([]);
const primaryImageIndex = ref(0);
const submitting = ref(false);

const optionGroups = ref([]);
const combinations = ref([]);

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
    const match = flat.find((c) => c.id === id);
    return match?.description || '';
});

const isMedicineCategory = computed(() => {
    const id = Number(fields.value.category_id);
    if (!id) return false;
    return (props.medicine_category_ids || []).map(Number).includes(id);
});

watch(isMedicineCategory, (on) => {
    if (!on) fields.value.requires_prescription = false;
});

const shelfLifeText = computed(() => {
    if (!fields.value.manufacturing_date || !fields.value.expiry_date) return '';
    const m = new Date(fields.value.manufacturing_date);
    const e = new Date(fields.value.expiry_date);
    if (e <= m) return 'Invalid dates (Expiry must be after Mfg)';

    const diffMonths = (e.getFullYear() - m.getFullYear()) * 12 + (e.getMonth() - m.getMonth());
    if (diffMonths >= 12) {
        const yrs = (diffMonths / 12).toFixed(1);
        return `${yrs} Year${yrs != 1 ? 's' : ''}`;
    }
    return `${diffMonths} Month${diffMonths != 1 ? 's' : ''}`;
});

const timeUntilExpiryText = computed(() => {
    if (!fields.value.expiry_date) return '';
    const e = new Date(fields.value.expiry_date);
    const now = new Date();
    
    if (e <= now) return 'Expired';

    let years = e.getFullYear() - now.getFullYear();
    let months = e.getMonth() - now.getMonth();
    
    if (months < 0) {
        years--;
        months += 12;
    }

    const parts = [];
    if (years > 0) parts.push(`${years}yr`);
    if (months > 0) parts.push(`${months}mo`);
    
    if (parts.length === 0) {
        const diffDays = Math.ceil((e - now) / (1000 * 60 * 60 * 24));
        if (diffDays > 0) return `${diffDays}d left`;
        return 'Expiring soon';
    }
    
    return parts.join(' ') + ' left';
});

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

// "box" -> "boxes", "piece" -> "pieces"
const pluralize = (word, n) => (n === 1 ? word : (/(s|x|z|ch|sh)$/i.test(word) ? `${word}es` : `${word}s`));

const productPack = computed(() => Math.max(1, Number(fields.value.units_per_pack) || 1));

// Retail price of one unit of a variant: per-piece price x the variant's pieces, plus its adjustment.
function comboRetail(c) {
    const pack = Math.max(1, Number(c.units_per_pack) || productPack.value);
    const price = ((Number(fields.value.base_price) || 0) * pack) / productPack.value + (Number(c.price_adjustment) || 0);
    return price.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function regenerateCombinations() {
    const groups = optionGroups.value.filter(g => g.name.trim() && g.values.length > 0);
    if (groups.length === 0) {
        combinations.value = [];
        return;
    }

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
            price_adjustment: old?.price_adjustment ?? 0,
            sku: old?.sku ?? '',
            units_per_pack: old?.units_per_pack ?? '',
            unit_label: old?.unit_label ?? '',
            stock: old?.stock ?? 0,
            is_active: old?.is_active ?? true,
        };
    });
}

function revokePreviews() {
    imagePreviews.value.forEach((url) => {
        if (url && url.startsWith('blob:')) URL.revokeObjectURL(url);
    });
}

onUnmounted(() => { revokePreviews(); });

function onImagesChange(event) {
    revokePreviews();
    const files = Array.from(event.target.files || []).filter(Boolean);
    imageFiles.value = files;
    imagePreviews.value = files.map((f) => URL.createObjectURL(f));
    primaryImageIndex.value = 0;
}

function appendBool(fd, key, val) {
    fd.append(key, val ? '1' : '0');
}

function submitForm() {
    submitting.value = true;

    const fd = new FormData();
    fd.append('name', fields.value.name);
    fd.append('description', fields.value.description);
    fd.append('category_id', String(fields.value.category_id || ''));
    fd.append('product_type', fields.value.product_type);
    fd.append('brand', fields.value.brand);
    fd.append('model', fields.value.model);
    if (fields.value.sku) fd.append('sku', fields.value.sku);
    appendBool(fd, 'requires_prescription', fields.value.requires_prescription);
    fd.append('vehicle_requirement', fields.value.vehicle_requirement);
    fd.append('base_price', String(fields.value.base_price ?? ''));
    if (fields.value.wholesale_price !== '' && fields.value.wholesale_price != null) {
        fd.append('wholesale_price', String(fields.value.wholesale_price));
    }
    if (fields.value.wholesale_min_qty !== '' && fields.value.wholesale_min_qty != null) {
        fd.append('wholesale_min_qty', String(fields.value.wholesale_min_qty));
    }
    fd.append('unit_label', String(fields.value.unit_label || 'piece').trim() || 'piece');
    fd.append('units_per_pack', String(Math.max(1, Number(fields.value.units_per_pack) || 1)));
    appendBool(fd, 'has_warranty', fields.value.has_warranty);
    if (fields.value.has_warranty && fields.value.warranty_months) {
        fd.append('warranty_months', String(fields.value.warranty_months));
    }
    appendBool(fd, 'has_expiry', fields.value.has_expiry);
    if (fields.value.has_expiry && fields.value.manufacturing_date) fd.append('manufacturing_date', fields.value.manufacturing_date);
    if (fields.value.barcode) fd.append('barcode', fields.value.barcode);

    imageFiles.value.forEach((file) => fd.append('images[]', file));

    if (combinations.value.length > 0) {
        const varsPayload = combinations.value.map((c) => ({
            option_name: Object.keys(c.combination).join(' / '),
            option_value: Object.values(c.combination).join(' / '),
            combination: c.combination,
            price_adjustment: c.price_adjustment ?? 0,
            sku: c.sku || null,
            units_per_pack: c.units_per_pack ? Number(c.units_per_pack) : null,
            unit_label: c.unit_label ? String(c.unit_label).trim() : null,
            is_active: c.is_active,
        }));
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

    fd.append('primary_image_index', String(primaryImageIndex.value));
    fd.append('initial_quantity', String(fields.value.initial_quantity ?? 0));
    fd.append('reorder_level', String(fields.value.reorder_level ?? 10));
    if (fields.value.has_expiry && fields.value.expiry_date) fd.append('expiry_date', fields.value.expiry_date);
    if (fields.value.batch_number) fd.append('batch_number', fields.value.batch_number);

    router.post('/owner/inventory', fd, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => { submitting.value = false; },
    });
}
</script>
