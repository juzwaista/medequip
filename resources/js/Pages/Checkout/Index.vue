<template>
    <MainLayout>
        <div
            class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 py-6 sm:py-8 pb-24 md:pb-8"
        >
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-ink">Checkout</h1>
                <p class="text-ink-soft mt-2">Complete your order</p>
            </div>

            <form
                @submit.prevent="submitOrder"
                class="grid grid-cols-1 lg:grid-cols-3 gap-8"
            >
                <!-- Hidden OCR Results -->
                <input type="hidden" name="ocr_results" :value="JSON.stringify(form.ocr_results)" />
                <!-- Order Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Error Display -->
                    <div
                        v-if="
                            form.errors && Object.keys(form.errors).length > 0
                        "
                        class="bg-red-50 border-l-4 border-red-500 rounded-control p-4"
                    >
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-5 w-5 text-red-400"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3
                                    class="text-sm font-medium text-red-800 mb-2"
                                >
                                    Please correct the following errors:
                                </h3>
                                <ul
                                    class="text-sm text-red-700 list-disc list-inside space-y-1"
                                >
                                    <li
                                        v-for="(error, field) in form.errors"
                                        :key="field"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Fulfillment Method Selection -->
                    <div class="bg-white rounded-card shadow-md p-6">
                        <h2 class="text-xl font-bold text-ink mb-4">How would you like to get your order?</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <label
                                :class="[
                                    'flex flex-col items-center justify-center p-4 rounded-card border-2 cursor-pointer transition-all',
                                    form.fulfillment_method === 'delivery'
                                        ? 'border-brand bg-brand-tint'
                                        : 'border-line bg-mist hover:border-line'
                                ]"
                            >
                                <input type="radio" v-model="form.fulfillment_method" value="delivery" class="sr-only" />
                                <svg class="w-8 h-8 mb-2" :class="form.fulfillment_method === 'delivery' ? 'text-brand' : 'text-ink-faint'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 011 1v2.5a.5.5 0 01-1 0V16zm-1.833 3.333H6.833a1.167 1.167 0 01-1.167-1.167V10H15v8.167a1.167 1.167 0 01-1.167 1.167z" />
                                </svg>
                                <span class="text-sm font-bold" :class="form.fulfillment_method === 'delivery' ? 'text-brand-dark' : 'text-ink'">Courier Delivery</span>
                            </label>

                            <label
                                :class="[
                                    'flex flex-col items-center justify-center p-4 rounded-card border-2 cursor-pointer transition-all',
                                    form.fulfillment_method === 'pickup'
                                        ? 'border-brand bg-brand-tint'
                                        : 'border-line bg-mist hover:border-line'
                                ]"
                            >
                                <input type="radio" v-model="form.fulfillment_method" value="pickup" class="sr-only" />
                                <svg class="w-8 h-8 mb-2" :class="form.fulfillment_method === 'pickup' ? 'text-brand' : 'text-ink-faint'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <span class="text-sm font-bold" :class="form.fulfillment_method === 'pickup' ? 'text-brand-dark' : 'text-ink'">Store Pick-up</span>
                            </label>
                        </div>
                    </div>

                    <!-- Delivery Information -->
                    <div v-if="form.fulfillment_method === 'delivery'" class="bg-white rounded-card shadow-md p-6   duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-ink">
                                Delivery Information
                            </h2>
                            <Link
                                href="/addresses"
                                class="text-sm font-semibold text-brand hover:text-brand-dark"
                                >Manage Addresses</Link
                            >
                        </div>

                        <div class="space-y-4">
                            <!-- No Saved Addresses -->
                            <div
                                v-if="savedAddresses.length === 0"
                                class="bg-amber-50 rounded-control p-6 text-center border border-amber-200"
                            >
                                <svg
                                    class="w-12 h-12 text-amber-500 mx-auto mb-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                    ></path>
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                    ></path>
                                </svg>
                                <h3
                                    class="text-lg font-bold text-amber-900 mb-1"
                                >
                                    No saved addresses
                                </h3>
                                <p class="text-amber-800 text-sm mb-4">
                                    Please add a delivery address to proceed
                                    with checkout.
                                </p>
                                <a
                                    href="/addresses"
                                    class="inline-block bg-brand text-white font-semibold py-2 px-4 rounded-control hover:bg-brand-dark transition"
                                    >Manage Addresses</a
                                >
                            </div>

                            <!-- Saved Addresses -->
                            <div
                                v-if="savedAddresses.length > 0"
                                class="mb-6 p-4 rounded-control border border-line bg-mist"
                            >
                                <label
                                    class="block text-sm font-semibold text-ink mb-2"
                                    >Select Delivery Address *</label
                                >
                                <select
                                    v-model="selectedSavedAddress"
                                    required
                                    class="w-full px-4 py-3 border border-line rounded-control focus:ring-2 focus:ring-brand bg-white min-h-[44px] touch-manipulation"
                                >
                                    <option value="" disabled hidden>
                                        Select a delivery address
                                    </option>
                                    <option
                                        v-for="addr in savedAddresses"
                                        :key="addr.id"
                                        :value="addr.id"
                                    >
                                        {{ addr.label ? `${addr.label}: ` : ""
                                        }}{{ addr.address_line }}, Brgy.
                                        {{ addr.barangay }}, {{ addr.city }}
                                    </option>
                                </select>
                            </div>

                            <!-- Selected Address Preview -->
                            <div
                                v-if="selectedSavedAddress"
                                class="mt-4 p-5 rounded-card border-2 border-brand bg-brand-tint relative overflow-hidden"
                            >
                                <div
                                    class="absolute top-0 right-0 pt-4 pr-4 text-brand"
                                >
                                    <svg
                                        class="h-6 w-6"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>

                                <div
                                    v-if="currentAddressObj"
                                    class="flex flex-col"
                                >
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3
                                            class="text-lg font-bold text-ink"
                                        >
                                            {{
                                                currentAddressObj.label ||
                                                "Delivery Address"
                                            }}
                                        </h3>
                                        <span
                                            v-if="currentAddressObj.is_default"
                                            class="bg-brand-soft text-brand-dark text-xs px-2.5 py-0.5 rounded-full font-semibold border border-brand-soft"
                                        >
                                            Default
                                        </span>
                                    </div>
                                    <p class="font-bold text-ink">
                                        {{ currentAddressObj.recipient_name }}
                                    </p>
                                    <p class="text-ink font-medium">
                                        {{ currentAddressObj.contact_number }}
                                    </p>
                                    <p class="text-ink-soft mt-2">
                                        {{ form.delivery_address }}
                                    </p>
                                </div>
                            </div>

                            <!-- Order Notes -->
                            <div
                                v-if="savedAddresses.length > 0"
                                class="pt-4 border-t border-line"
                            >
                                <label
                                    class="block text-sm font-semibold text-ink mb-2"
                                    >Order Notes (Optional)</label
                                >
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="w-full px-4 py-3 border border-line rounded-control focus:ring-2 focus:ring-brand focus:border-transparent"
                                    placeholder="Special instructions for your order"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Pick-up Information -->
                    <div v-else class="bg-white rounded-card shadow-md p-6   duration-300">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-brand-tint rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-ink">Pick-up Location</h2>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-brand-tint border border-brand-soft rounded-card p-4">
                                <p class="text-sm text-brand-dark leading-relaxed">
                                    <strong>How it works:</strong> Once your order is ready, you'll receive specific pick-up instructions and the store's exact location via notifications. No delivery fee will be charged.
                                </p>
                            </div>

                            <div class="space-y-4">
                                <div v-for="distributorId in Array.from(new Set(cartItems.map(i => i.product.distributor_id)))" :key="distributorId" class="p-4 rounded-card border border-line bg-mist/50">
                                    <p class="text-xs font-semibold text-ink-faint   mb-1">Seller</p>
                                    <p class="font-bold text-ink">{{ cartItems.find(i => i.product.distributor_id === distributorId).product.distributor_name }}</p>
                                    <div class="mt-3 flex items-start gap-2">
                                        <svg class="w-4 h-4 text-brand mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        </svg>
                                        <p class="text-xs text-ink font-medium">
                                            {{ cartItems.find(i => i.product.distributor_id === distributorId).product.distributor?.address || 'Address information pending' }}
                                        </p>
                                    </div>
                                    <div v-if="cartItems.find(i => i.product.distributor_id === distributorId).product.distributor?.latitude" class="mt-3">
                                        <MapDisplay
                                            :lat="cartItems.find(i => i.product.distributor_id === distributorId).product.distributor.latitude"
                                            :lng="cartItems.find(i => i.product.distributor_id === distributorId).product.distributor.longitude"
                                            height="150px"
                                        />
                                        <a
                                            :href="`https://www.google.com/maps/search/?api=1&query=${cartItems.find(i => i.product.distributor_id === distributorId).product.distributor.latitude},${cartItems.find(i => i.product.distributor_id === distributorId).product.distributor.longitude}`"
                                            target="_blank"
                                            class="mt-2 inline-flex items-center text-xs font-bold text-brand hover:text-brand-dark  "
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            Open in Google Maps
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Pick-up Notes -->
                            <div class="pt-4 border-t border-line">
                                <label class="block text-sm font-semibold text-ink mb-2">Pick-up Notes (Optional)</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="w-full px-4 py-3 border border-line rounded-control focus:ring-2 focus:ring-brand focus:border-transparent"
                                    placeholder="e.g., Someone else will pick it up for me"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="bg-white rounded-card shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-ink">
                                Order Items
                            </h2>
                            <span
                                class="text-xs font-bold text-ink-faint  "
                                >{{ cartItems.length }} Product{{
                                    cartItems.length !== 1 ? "s" : ""
                                }}</span
                            >
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="item in cartItems"
                                :key="item.line_key"
                                class="flex justify-between items-start gap-4 p-4 rounded-card border border-line bg-mist/50 hover:bg-mist transition"
                            >
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="font-bold text-ink truncate tracking-tight sm:text-lg"
                                    >
                                        {{ item.product.name }}
                                    </p>
                                    <p
                                        v-if="item.variation_label"
                                        class="text-xs text-brand-dark font-bold   mt-0.5"
                                    >
                                        {{ item.variation_label }}
                                    </p>
                                    <p
                                        v-if="item.units_per_pack > 1"
                                        class="text-[11px] text-ink-soft mt-0.5"
                                    >
                                        Sold per {{ item.unit_label }} · {{ item.units_per_pack }} pcs each ({{ item.pieces }} pcs)
                                    </p>
                                    <div class="flex flex-col gap-0.5 mt-1.5">
                                        <span
                                            class="text-xs font-bold text-ink-faint  "
                                            >{{ item.quantity }} × ₱{{
                                                Number(
                                                    item.unit_price,
                                                ).toLocaleString()
                                            }}</span
                                        >
                                        <div
                                            v-if="item.is_wholesale"
                                            class="flex items-center gap-1.5 mt-0.5"
                                        >
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold bg-brand-tint text-brand-dark  tracking-tight"
                                            >
                                                Wholesale Applied
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="flex flex-col items-end">
                                        <!-- Original Total -->
                                        <span
                                            v-if="item.is_wholesale"
                                            class="text-xs text-ink-faint line-through font-medium"
                                            >₱{{
                                                Number(
                                                    item.retail_unit_price *
                                                        item.quantity,
                                                ).toLocaleString()
                                            }}</span
                                        >
                                        <span
                                            v-else
                                            class="text-xs text-ink-faint font-medium font-medium mt-0.5"
                                            >₱{{
                                                Number(
                                                    item.quantity *
                                                        item.unit_price,
                                                ).toLocaleString()
                                            }}</span
                                        >

                                        <!-- Final Price -->
                                        <span
                                            class="text-xl font-semibold text-ink leading-none tabular-nums mt-1"
                                        >
                                            ₱{{
                                                Number(
                                                    item.subtotal,
                                                ).toLocaleString()
                                            }}
                                        </span>

                                        <!-- VAT Status -->
                                        <span
                                            v-if="item.product.is_vat_exempt"
                                            class="text-xs font-bold text-amber-600   mt-1"
                                        >
                                            VAT Exempt
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Discount / SC / PWD -->
                    <div class="bg-white rounded-card shadow-md p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <h2 class="text-xl font-bold text-ink">
                                Discounts & Exemptions
                            </h2>
                        </div>

                        <div class="space-y-4">
                            <div
                                class="flex items-start gap-4 p-4 rounded-card border border-line bg-mist/50 group hover:border-brand-soft transition-colors"
                            >
                                <div class="flex-shrink-0 mt-0.5">
                                    <input
                                        v-model="form.apply_discount"
                                        type="checkbox"
                                        id="apply_discount"
                                        class="h-6 w-6 text-brand rounded-control border-line focus:ring-brand cursor-pointer"
                                    />
                                </div>
                                <div class="flex-1">
                                    <label
                                        for="apply_discount"
                                        class="block text-sm font-bold text-ink cursor-pointer"
                                        >Apply Senior Citizen or PWD
                                        Exemption / Discount</label
                                    >
                                    <p class="text-xs text-ink-soft mt-1">
                                        Get 20% discount and VAT exemption on
                                        items for personal use.
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="form.apply_discount"
                                class="mt-4 p-4 rounded-card bg-brand-tint/50 border border-brand-soft space-y-4    duration-300"
                                                     <!-- Saved IDs Selector -->
                                <div v-if="savedDiscountIds && savedDiscountIds.length > 0" class="mb-4">
                                    <label class="block text-xs font-bold text-ink-soft   mb-2">Choose an ID</label>
                                    <div class="space-y-2">
                                        <label 
                                            v-for="savedId in savedDiscountIds" 
                                            :key="savedId.id"
                                            :class="[
                                                'flex items-start gap-3 p-3 rounded-control border-2 cursor-pointer transition',
                                                form.use_saved_discount && form.saved_discount_id === savedId.id
                                                    ? 'border-brand bg-brand-tint'
                                                    : 'border-line bg-white hover:border-line'
                                            ]"
                                        >
                                            <input 
                                                type="radio" 
                                                name="saved_id" 
                                                :value="savedId.id" 
                                                v-model="form.saved_discount_id" 
                                                @change="form.use_saved_discount = true"
                                                class="mt-0.5 text-brand focus:ring-brand" 
                                            />
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-ink text-sm">{{ savedId.label || 'Saved ID' }}</span>
                                                    <span class="bg-brand-tint text-brand-dark text-xs px-2 py-0.5 rounded-full font-bold ">{{ savedId.discount_type }}</span>
                                                </div>
                                                <p class="text-xs text-ink-soft mt-0.5">{{ savedId.id_name }} • {{ savedId.id_number }}</p>
                                            </div>
                                        </label>
                                        
                                        <label 
                                            :class="[
                                                'flex items-start gap-3 p-3 rounded-control border-2 cursor-pointer transition',
                                                !form.use_saved_discount
                                                    ? 'border-brand bg-brand-tint'
                                                    : 'border-line bg-white hover:border-line'
                                            ]"
                                        >
                                            <input 
                                                type="radio" 
                                                name="saved_id" 
                                                :value="null" 
                                                v-model="form.saved_discount_id" 
                                                @change="form.use_saved_discount = false"
                                                class="mt-0.5 text-brand focus:ring-brand" 
                                            />
                                            <div class="flex-1">
                                                <span class="font-bold text-ink text-sm">Enter a new ID</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div v-if="!form.use_saved_discount" class="space-y-4">
                                    <!-- Discount Type -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <label
                                            :class="[
                                                'flex items-center justify-center gap-2 p-3 rounded-control border-2 cursor-pointer transition',
                                                form.discount_type === 'senior'
                                                    ? 'border-brand bg-brand-tint'
                                                    : 'border-line bg-white',
                                            ]"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.discount_type"
                                                value="senior"
                                                class="sr-only"
                                            />
                                            <span class="text-sm font-bold"
                                                >Senior Citizen</span
                                            >
                                        </label>
                                        <label
                                            :class="[
                                                'flex items-center justify-center gap-2 p-3 rounded-control border-2 cursor-pointer transition',
                                                form.discount_type === 'pwd'
                                                    ? 'border-brand bg-brand-tint'
                                                    : 'border-line bg-white',
                                            ]"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.discount_type"
                                                value="pwd"
                                                class="sr-only"
                                            />
                                            <span class="text-sm font-bold"
                                                >PWD</span
                                            >
                                        </label>
                                    </div>

                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                    >
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-ink-soft   mb-1.5"
                                                >Full Name (must match ID) *</label
                                            >
                                            <input
                                                v-model="form.discount_id_name"
                                                type="text"
                                                class="w-full px-4 py-2.5 border border-line rounded-control text-sm focus:ring-2 focus:ring-brand"
                                                placeholder="e.g. JUAN DELA CRUZ"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-ink-soft   mb-1.5"
                                                >ID Number *</label
                                            >
                                            <input
                                                v-model="form.discount_id_number"
                                                type="text"
                                                class="w-full px-4 py-2.5 border border-line rounded-control text-sm focus:ring-2 focus:ring-brand"
                                                placeholder="SC/PWD ID Number"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-xs font-bold text-ink-soft   mb-1.5"
                                            >ID Photo (Front/Back) *</label
                                        >
                                        <div
                                            class="flex items-center justify-center w-full relative"
                                        >
                                            <label
                                                class="flex flex-col items-center justify-center w-full h-32 border-2 border-line border-dashed rounded-control cursor-pointer bg-mist hover:bg-mist"
                                            >
                                                <div
                                                    v-if="!form.discount_id_image"
                                                    class="flex flex-col items-center justify-center pt-5 pb-6"
                                                >
                                                    <svg
                                                        class="w-8 h-8 mb-4 text-ink-soft"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                                        ></path>
                                                    </svg>
                                                    <p
                                                        class="mb-2 text-sm text-ink-soft font-semibold"
                                                    >
                                                        Click to upload ID photo
                                                    </p>
                                                    <p
                                                        class="text-xs text-ink-faint"
                                                    >
                                                        PNG, JPG or WEBP (MAX. 8MB)
                                                    </p>
                                                </div>
                                                <div
                                                    v-else
                                                    class="flex flex-col items-center justify-center p-4"
                                                >
                                                    <svg
                                                        class="w-10 h-10 mb-2 text-brand"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                    <p
                                                        class="text-xs font-bold text-brand truncate max-w-xs"
                                                    >
                                                        {{
                                                            form.discount_id_image
                                                                .name
                                                        }}
                                                    </p>
                                                    <button
                                                        type="button"
                                                        @click.prevent.stop="
                                                            form.discount_id_image =
                                                                null
                                                        "
                                                        class="mt-2 text-xs text-red-500 font-bold  underline"
                                                    >
                                                        Remove
                                                    </button>
                                                </div>
                                                <input
                                                    type="file"
                                                    @change="e => onUploadFile('discount_id_image', e.target.files[0])"
                                                    class="hidden"
                                                    accept="image/*"
                                                />
                                            </label>
                                            <div v-if="scanningFields['discount_id_image']" class="absolute inset-0 bg-brand-tint/50 flex flex-col items-center justify-center rounded-control ">
                                                <svg class="animate-spin h-8 w-8 text-brand mb-2" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <p class="text-xs font-bold text-brand   italic ">Scanning ID...</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 p-3 bg-white rounded-control border border-line">
                                        <div class="flex items-center gap-2">
                                            <input 
                                                type="checkbox" 
                                                id="save_discount_id" 
                                                v-model="form.save_discount_id" 
                                                class="h-4 w-4 text-brand rounded border-line"
                                            />
                                            <label for="save_discount_id" class="text-sm font-semibold text-ink cursor-pointer">
                                                Save this ID for future orders
                                            </label>
                                        </div>
                                        <div v-if="form.save_discount_id" class="mt-3">
                                            <input 
                                                type="text" 
                                                v-model="form.discount_id_label" 
                                                placeholder="Label (e.g. My SC ID)" 
                                                class="w-full px-3 py-2 border border-line rounded-control text-sm focus:ring-brand focus:border-brand"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2 pt-2">
                                    <input
                                        type="checkbox"
                                        v-model="form.discount_terms"
                                        id="discount_terms"
                                        required
                                        class="h-5 w-5 text-brand rounded border-line mt-1 cursor-pointer"
                                    />
                                    <label
                                        for="discount_terms"
                                        class="text-xs text-ink-soft leading-snug cursor-pointer"
                                    >
                                        I certify that this purchase is for my
                                        personal use and that the ID provided is
                                        valid and belongs to me.
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prescription Verification Details -->
                    <div
                        v-if="cart_has_prescription_items"
                        class="bg-white rounded-card shadow-md p-6"
                    >
                        <div class="flex items-center gap-2 mb-4">
                            <h2 class="text-xl font-bold text-ink">
                                Prescription Verification
                            </h2>
                        </div>

                        <div class="space-y-4">
                            <div
                                class="bg-amber-50 border border-amber-100 rounded-control p-3"
                            >
                                <p class="text-xs text-amber-900 font-bold mb-1">
                                    This order includes prescription medicine
                                </p>
                                <p class="text-[11px] text-amber-800 leading-relaxed">
                                    This order contains prescription items. The distributor cannot accept or process your order unless you provide a valid patient name, ID photo, and prescription photo below.
                                </p>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-ink-soft   mb-1.5"
                                        >Patient Name (as per ID) *</label
                                    >
                                    <input
                                        v-model="form.prescription_patient_name"
                                        type="text"
                                        class="w-full px-4 py-2.5 border border-line rounded-control text-sm focus:ring-2 focus:ring-brand"
                                        placeholder="Full name of the patient"
                                    />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-ink-soft   mb-1.5"
                                            >Patient's Valid ID *</label
                                        >
                                        <div
                                            class="flex items-center justify-center w-full"
                                        >
                                            <label
                                                class="flex flex-col items-center justify-center w-full h-36 border-2 border-line border-dashed rounded-card cursor-pointer bg-mist/50 hover:bg-mist hover:border-brand-soft transition-all group"
                                            >
                                                <div
                                                    v-if="!form.prescription_id_image"
                                                    class="flex flex-col items-center justify-center p-4 text-center"
                                                >
                                                    <div class="w-10 h-10 bg-mist rounded-full flex items-center justify-center mb-2 group-hover:bg-brand-tint group-hover:text-brand transition-colors">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                        </svg>
                                                    </div>
                                                    <p class="text-[11px] text-ink-soft font-bold mb-0.5">Upload ID Photo</p>
                                                    <p class="text-xs text-ink-faint">PNG, JPG or WEBP</p>
                                                </div>
                                                <div
                                                    v-else
                                                    class="flex flex-col items-center justify-center p-4 text-center"
                                                >
                                                    <div class="w-10 h-10 bg-brand-tint text-brand rounded-full flex items-center justify-center mb-2">
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                    <p class="text-xs font-bold text-brand line-clamp-1 px-2">
                                                        {{ form.prescription_id_image.name }}
                                                    </p>
                                                    <button type="button" @click.prevent="form.prescription_id_image = null" class="mt-1 text-xs text-red-500 font-bold  hover:underline">Change</button>
                                                </div>
                                                <input
                                                    type="file"
                                                    @change="e => onUploadFile('prescription_id_image', e.target.files[0])"
                                                    class="hidden"
                                                    accept="image/*"
                                                />
                                            </label>
                                            <div v-if="scanningFields['prescription_id_image']" class="absolute inset-0 bg-brand-tint/50 flex flex-col items-center justify-center rounded-card ">
                                                <svg class="animate-spin h-6 w-6 text-brand mb-1" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <p class="text-xs font-bold text-brand   italic ">Scanning...</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-xs font-bold text-ink-soft   mb-1.5"
                                            >Prescription Photo *</label
                                        >
                                        <div
                                            class="flex items-center justify-center w-full"
                                        >
                                            <label
                                                class="flex flex-col items-center justify-center w-full h-36 border-2 border-line border-dashed rounded-card cursor-pointer bg-mist/50 hover:bg-mist hover:border-brand-soft transition-all group"
                                            >
                                                <div
                                                    v-if="!form.prescription_image"
                                                    class="flex flex-col items-center justify-center p-4 text-center"
                                                >
                                                    <div class="w-10 h-10 bg-mist rounded-full flex items-center justify-center mb-2 group-hover:bg-brand-tint group-hover:text-brand transition-colors">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </div>
                                                    <p class="text-[11px] text-ink-soft font-bold mb-0.5">Upload Prescription</p>
                                                    <p class="text-xs text-ink-faint">PNG, JPG or WEBP</p>
                                                </div>
                                                <div
                                                    v-else
                                                    class="flex flex-col items-center justify-center p-4 text-center"
                                                >
                                                    <div class="w-10 h-10 bg-brand-tint text-brand rounded-full flex items-center justify-center mb-2">
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                    <p class="text-xs font-bold text-brand line-clamp-1 px-2">
                                                        {{ form.prescription_image.name }}
                                                    </p>
                                                    <button type="button" @click.prevent="form.prescription_image = null" class="mt-1 text-xs text-red-500 font-bold  hover:underline">Change</button>
                                                </div>
                                                <input
                                                    type="file"
                                                    @change="e => onUploadFile('prescription_image', e.target.files[0])"
                                                    class="hidden"
                                                    accept="image/*"
                                                />
                                            </label>
                                            <div v-if="scanningFields['prescription_image']" class="absolute inset-0 bg-brand-tint/50 flex flex-col items-center justify-center rounded-card ">
                                                <svg class="animate-spin h-6 w-6 text-brand mb-1" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <p class="text-xs font-bold text-brand   italic ">Scanning...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tax Information (B2B) -->
                    <div class="bg-white rounded-card shadow-md p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <h2 class="text-xl font-bold text-ink">
                                Tax Information
                            </h2>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-semibold text-ink mb-2"
                                    >Tax Identification Number (TIN)</label
                                >
                                <input
                                    v-model="form.tin"
                                    type="text"
                                    maxlength="15"
                                    @input="formatTIN"
                                    class="w-full px-4 py-3 border border-line rounded-control focus:ring-2 focus:ring-brand focus:border-transparent font-mono "
                                    placeholder="000-000-000-000"
                                />
                                <p class="mt-2 text-xs text-ink-soft italic">
                                    Provide your TIN if you require a
                                    VAT-compliant invoice for business auditing.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div
                        class="bg-white rounded-card shadow-md p-4 sm:p-6 lg:sticky lg:top-24"
                    >
                        <h2 class="text-xl font-bold text-ink mb-4">
                            Order Summary
                        </h2>

                        <div class="space-y-3 mb-6">
                            <div
                                class="flex justify-between items-center text-sm font-medium text-ink-soft px-1 pt-1"
                            >
                                <span>Items Total</span>
                                <span class="text-ink font-bold"
                                    >₱{{
                                        Number(
                                            originalSubtotal,
                                        ).toLocaleString()
                                    }}</span
                                >
                            </div>

                            <div
                                class="flex justify-between items-center text-sm font-medium text-ink-soft px-1"
                            >
                                <span>Delivery Fee</span>
                                <span class="text-ink font-bold"
                                    >₱{{
                                        Number(
                                            form.fulfillment_method === 'pickup' ? 0 : (shipping_fee_total || 0),
                                        ).toLocaleString()
                                    }}</span
                                >
                            </div>

                            <div
                                v-if="totalSavings > 0"
                                class="flex justify-between items-center text-xs font-bold text-brand bg-brand-tint/50 px-2 py-1 rounded-control border border-brand-soft/50"
                            >
                                <span class=" tracking-tight italic"
                                    >Wholesale Discount Applied</span
                                >
                                <span
                                    >−₱{{
                                        Number(totalSavings).toLocaleString()
                                    }}</span
                                >
                            </div>

                            <div
                                v-if="localDiscountAmount > 0"
                                class="flex justify-between items-center text-xs font-bold text-brand bg-brand-tint px-2 py-1 rounded-control border border-brand-soft"
                            >
                                <span class=" tracking-tight"
                                    >SC/PWD Discount Applied (Pending)</span
                                >
                                <span
                                    >−₱{{
                                        Number(
                                            localDiscountAmount,
                                        ).toLocaleString()
                                    }}</span
                                >
                            </div>

                            <div
                                class="pt-4 border-t border-line flex flex-col gap-1"
                            >
                                <span
                                    class="text-xs font-bold text-brand  "
                                    >Amount to Pay</span
                                >
                                <div class="flex items-baseline gap-2">
                                    <span
                                        v-if="
                                            totalSavings > 0 ||
                                            localDiscountAmount > 0
                                        "
                                        class="text-sm text-ink-faint line-through font-medium"
                                        >₱{{
                                            Number(
                                                originalSubtotal +
                                                    (shipping_fee_total || 0),
                                            ).toLocaleString()
                                        }}</span
                                    >
                                    <span
                                        class="text-3xl font-semibold text-ink tabular-nums leading-none"
                                        >₱{{
                                            Number(grandTotal).toLocaleString()
                                        }}</span
                                    >
                                </div>
                            </div>

                            <!-- VAT Breakdown -->
                            <div
                                class="mt-4 p-3 bg-mist rounded-control border border-line space-y-1.5 grayscale opacity-70 hover:grayscale-0 hover:opacity-100 transition-all duration-300"
                            >
                                <div
                                    class="flex justify-between items-center text-xs font-bold  "
                                    :class="form.apply_discount ? 'text-ink-faint' : 'text-ink-faint'"
                                >
                                    <span>VATable Sales</span>
                                    <div class="flex items-center gap-1.5">
                                        <span v-if="form.apply_discount" class="line-through opacity-60">₱{{ 
                                            Number((props.subtotal + props.shipping_fee_total) / 1.12).toLocaleString(undefined, {
                                                minimumFractionDigits: 2, maximumFractionDigits: 2
                                            })
                                        }}</span>
                                        <span :class="{'text-brand': form.apply_discount}">₱{{
                                            Number(vatBreakdown.vatableSales).toLocaleString(undefined, {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })
                                        }}</span>
                                    </div>
                                </div>
                                <div
                                    class="flex justify-between items-center text-xs font-bold  "
                                    :class="form.apply_discount ? 'text-ink-faint' : 'text-ink-faint'"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <span>VAT Amount (12%)</span>
                                        <span v-if="form.apply_discount" class="bg-brand-tint text-brand-dark text-xs px-1 rounded-sm  font-semibold">EXEMPTED</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span v-if="form.apply_discount" class="line-through opacity-60">₱{{ 
                                            Number((props.subtotal + props.shipping_fee_total) - ((props.subtotal + props.shipping_fee_total) / 1.12)).toLocaleString(undefined, {
                                                minimumFractionDigits: 2, maximumFractionDigits: 2
                                            })
                                        }}</span>
                                        <span :class="{'text-brand': form.apply_discount}">₱{{
                                            Number(vatBreakdown.vatAmount).toLocaleString(undefined, {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })
                                        }}</span>
                                    </div>
                                </div>
                                <div
                                    v-if="vatBreakdown.vatExemptSales > 0"
                                    class="flex justify-between items-center text-xs font-bold  "
                                    :class="form.apply_discount ? 'text-ink border-t border-line pt-1 mt-1' : 'text-ink-faint'"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <span>VAT-Exempt Sales</span>
                                        <span v-if="form.apply_discount" class="bg-brand-tint text-brand-dark text-xs px-1 rounded-sm  font-semibold ">
                                            {{ form.discount_type }} Applied
                                        </span>
                                    </div>
                                    <span :class="{'text-brand-dark': form.apply_discount}">₱{{
                                        Number(
                                            vatBreakdown.vatExemptSales,
                                        ).toLocaleString(undefined, {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        })
                                    }}</span>
                                </div>
                                <p
                                    class="text-xs text-ink-faint italic leading-tight mt-1"
                                >
                                    Total amount is inclusive of 12% VAT where
                                    applicable.
                                </p>
                            </div>
                        </div>

                        <!-- Payment Method Selection -->
                        <div class="mb-6">
                            <h3
                                class="text-sm font-semibold text-ink mb-3"
                            >
                                Payment Method *
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <!-- GCash -->
                                <label
                                    :class="[
                                        'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 cursor-pointer transition-all select-none',
                                        form.payment_method === 'gcash'
                                            ? 'border-brand bg-brand-tint shadow-sm'
                                            : 'border-line hover:border-line bg-white',
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.payment_method"
                                        value="gcash"
                                        class="sr-only"
                                    />
                                    <!-- GCash logo -->
                                    <svg
                                        width="22"
                                        height="22"
                                        viewBox="0 0 100 100"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <rect
                                            width="100"
                                            height="100"
                                            rx="20"
                                            fill="#007DFE"
                                        />
                                        <path
                                            d="M73 50c0 12.703-10.297 23-23 23S27 62.703 27 50s10.297-23 23-23c6.364 0 12.12 2.578 16.3 6.75l-6.6 6.6A13 13 0 0050 37c-7.18 0-13 5.82-13 13s5.82 13 13 13c5.89 0 10.86-3.92 12.4-9.32H50V47h23v3z"
                                            fill="white"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-semibold"
                                        :class="
                                            form.payment_method === 'gcash'
                                                ? 'text-brand-dark'
                                                : 'text-ink'
                                        "
                                        >GCash</span
                                    >
                                    <svg
                                        v-if="form.payment_method === 'gcash'"
                                        class="h-4 w-4 text-brand"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </label>

                                <!-- Maya -->
                                <label
                                    :class="[
                                        'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 cursor-pointer transition-all select-none',
                                        form.payment_method === 'paymaya'
                                            ? 'border-brand bg-brand-tint shadow-sm'
                                            : 'border-line hover:border-line bg-white',
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.payment_method"
                                        value="paymaya"
                                        class="sr-only"
                                    />
                                    <!-- Maya logo -->
                                    <svg
                                        width="22"
                                        height="22"
                                        viewBox="0 0 100 100"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <rect
                                            width="100"
                                            height="100"
                                            rx="20"
                                            fill="#2CC84D"
                                        />
                                        <text
                                            x="50"
                                            y="68"
                                            font-family="Arial"
                                            font-weight="bold"
                                            font-size="40"
                                            text-anchor="middle"
                                            fill="white"
                                        >
                                            M
                                        </text>
                                    </svg>
                                    <span
                                        class="text-sm font-semibold"
                                        :class="
                                            form.payment_method === 'paymaya'
                                                ? 'text-brand-dark'
                                                : 'text-ink'
                                        "
                                        >Maya</span
                                    >
                                    <svg
                                        v-if="form.payment_method === 'paymaya'"
                                        class="h-4 w-4 text-brand"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </label>

                                <!-- Credit/Debit Card -->
                                <label
                                    :class="[
                                        'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 cursor-pointer transition-all select-none',
                                        form.payment_method === 'card'
                                            ? 'border-brand bg-brand-tint shadow-sm'
                                            : 'border-line hover:border-line bg-white',
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.payment_method"
                                        value="card"
                                        class="sr-only"
                                    />
                                    <!-- Card icon -->
                                    <svg
                                        width="22"
                                        height="22"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <rect
                                            width="24"
                                            height="24"
                                            rx="4"
                                            fill="#7C3AED"
                                        />
                                        <rect
                                            x="2"
                                            y="7"
                                            width="20"
                                            height="10"
                                            rx="1.5"
                                            fill="white"
                                            fill-opacity="0.2"
                                        />
                                        <rect
                                            x="2"
                                            y="10"
                                            width="20"
                                            height="3"
                                            fill="white"
                                            fill-opacity="0.6"
                                        />
                                        <rect
                                            x="4"
                                            y="14"
                                            width="5"
                                            height="1.5"
                                            rx="0.75"
                                            fill="white"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-semibold"
                                        :class="
                                            form.payment_method === 'card'
                                                ? 'text-brand-dark'
                                                : 'text-ink'
                                        "
                                        >Card</span
                                    >
                                    <svg
                                        v-if="form.payment_method === 'card'"
                                        class="h-4 w-4 text-brand"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </label>


                            <!-- Cash on Delivery (Disabled for now)
                                <label :class="[
                                    'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 transition-all select-none',
                                    cod_available
                                        ? form.payment_method === 'cod'
                                            ? 'border-orange-400 bg-orange-50 shadow-sm cursor-pointer'
                                            : 'border-line hover:border-line bg-white cursor-pointer'
                                        : 'border-line bg-mist cursor-not-allowed opacity-60'
                                ]">
                                    <input type="radio" v-model="form.payment_method" value="cod" class="sr-only" :disabled="!cod_available" />
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="24" height="24" rx="4" fill="#EA580C"/>
                                        <rect x="2" y="7" width="20" height="10" rx="1.5" fill="white" fill-opacity="0.25"/>
                                        <circle cx="12" cy="12" r="3" fill="white" fill-opacity="0.7"/>
                                        <path d="M4 9.5h2M18 9.5h2M4 14.5h2M18 14.5h2" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                    <span class="text-sm font-semibold" :class="form.payment_method === 'cod' ? 'text-orange-700' : 'text-ink'">Cash on Delivery</span>
                                    <svg v-if="form.payment_method === 'cod'" class="h-4 w-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </label>
                                -->

                                <!-- Purchase Order (B2B Only) -->
                                <label
                                    v-if="isApprovedBusiness"
                                    :class="[
                                        'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 cursor-pointer transition-all select-none',
                                        form.payment_method === 'purchase_order'
                                            ? 'border-brand bg-brand-tint shadow-sm'
                                            : 'border-line hover:border-line bg-white',
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.payment_method"
                                        value="purchase_order"
                                        class="sr-only"
                                    />
                                    <!-- PO icon -->
                                    <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span
                                        class="text-sm font-semibold flex items-center gap-1.5"
                                        :class="
                                            form.payment_method === 'purchase_order'
                                                ? 'text-brand-dark'
                                                : 'text-ink'
                                        "
                                    >
                                        Purchase Order (Net-30)
                                        <Tooltip @click.stop content="Upload your company's Purchase Order document. The seller will verify it before processing your order." position="top">
                                            <svg class="w-4 h-4 text-brand hover:text-brand transition cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </Tooltip>
                                    </span>
                                    <svg
                                        v-if="form.payment_method === 'purchase_order'"
                                        class="h-4 w-4 text-brand"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </label>
                            </div>

                            <p
                                v-if="false && !cod_available"
                                class="mt-2 text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-control px-3 py-2"
                            >
                                Cash on delivery isn’t available when too many
                                of your past orders were rejected (including
                                prescription declines). Your current rate is
                                about
                                <strong
                                    >{{
                                        Number(
                                            cod_rejection_rate_percent || 0,
                                        ).toFixed(1)
                                    }}%</strong
                                >
                                — use card, e-wallet, or Maya instead.
                            </p>
                        </div>

                        <!-- Purchase Order File Upload / Selection -->
                        <div
                            v-if="form.payment_method === 'purchase_order'"
                            class="bg-brand-tint border border-brand-soft rounded-control p-4 mb-6"
                        >
                            <h3 class="font-bold text-brand-dark mb-2">Purchase Order Details</h3>
                            <p class="text-xs text-brand-dark mb-4">Upload the Purchase Order document for this order. Payment is expected within 30 days of invoice.</p>

                            <div v-if="savedPurchaseOrders.length > 0" class="mb-4">
                                <label class="block text-xs font-semibold text-ink mb-1">Company / billing details</label>
                                <select v-model="form.saved_purchase_order_id" class="w-full rounded-control border-line text-sm focus:ring-brand focus:border-brand">
                                    <option :value="null">-- No saved profile --</option>
                                    <option v-for="po in savedPurchaseOrders" :key="po.id" :value="po.id">
                                        {{ po.label }} ({{ po.company_name }})
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-ink mb-1">PO Document *</label>
                                <input type="file" @change="e => form.po_document = e.target.files[0]" class="block w-full text-sm text-ink-soft file:mr-4 file:py-2 file:px-4 file:rounded-control file:border-0 file:text-sm file:font-semibold file:bg-brand-tint file:text-brand-dark hover:file:bg-brand-soft" accept=".pdf,.png,.jpg,.jpeg" />
                                <p v-if="form.errors.po_document" class="text-red-600 text-xs mt-1.5">{{ form.errors.po_document }}</p>

                                <label v-if="!form.saved_purchase_order_id" class="mt-3 flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="form.save_purchase_order" class="h-4 w-4 text-brand rounded border-line focus:ring-brand" />
                                    <span class="text-xs text-ink font-medium">Save my company details for future purchase orders</span>
                                </label>
                            </div>
                        </div>

                        <div
                            v-if="form.payment_method !== 'cod'"
                            class="bg-brand-tint border border-brand-soft rounded-control p-3 mb-6"
                        >
                            <div class="flex items-start">
                                <svg
                                    class="h-5 w-5 text-brand mr-2 mt-0.5 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                    />
                                </svg>
                                <p class="text-xs text-brand-dark">
                                    <strong>Buyer protection:</strong> Your
                                    payment is held by the platform until you
                                    confirm receipt of your order.
                                </p>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing || !isFormValid"
                            class="w-full bg-brand hover:bg-brand-dark text-white px-6 py-4 rounded-card hover:shadow-xl transition font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        >
                            <svg
                                v-if="form.processing"
                                class="animate-spin h-5 w-5 mr-2"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            {{
                                form.processing
                                    ? "Processing..."
                                    : form.payment_method === "purchase_order"
                                      ? `Submit Purchase Order (₱${Number(grandTotal).toLocaleString()})`
                                      : `Pay ₱${Number(grandTotal).toLocaleString()} & Place Order`
                            }}
                        </button>

                        <a
                            href="/cart"
                            class="block w-full text-center text-brand hover:text-brand-dark mt-4 font-medium"
                        >
                            &larr; Back to Cart
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useForm, Link, usePage } from "@inertiajs/vue3";
import MainLayout from "@/Layouts/MainLayout.vue";
import Tooltip from "@/Components/Tooltip.vue";
import MapDisplay from "@/Components/MapDisplay.vue";
import { useOCR } from "@/Composables/useOCR";

const page = usePage();
const { scanImage, extractName } = useOCR();
const scanningFields = ref({});

const props = defineProps({
    cartItems: Array,
    subtotal: Number,
    shipping_fee_per_order: {
        type: Number,
        default: 0,
    },
    shipping_fee_total: {
        type: Number,
        default: 0,
    },
    estimated_total: {
        type: Number,
        default: 0,
    },
    distributor_count: {
        type: Number,
        default: 1,
    },
    cities: Object,
    barangays: Object,
    savedAddresses: {
        type: Array,
        default: () => [],
    },
    savedDiscountIds: {
        type: Array,
        default: () => [],
    },
    savedPurchaseOrders: {
        type: Array,
        default: () => [],
    },

    cart_has_prescription_items: {
        type: Boolean,
        default: false,
    },
    cod_available: {
        type: Boolean,
        default: true,
    },
    cod_rejection_rate_percent: {
        type: Number,
        default: 0,
    },
    queryParams: {
        type: Object,
        default: () => ({}),
    },
    auth: Object,
});

const onUploadFile = async (field, file) => {
    if (!file) {
        form[field] = null;
        return;
    }
    form[field] = file;

    if (file.type.match('image.*')) {
        scanningFields.value[field] = true;
        try {
            const text = await scanImage(file);
            const extractedName = extractName(text);
            
            if (extractedName) {
                form.ocr_results[field] = {
                    extracted_name: extractedName,
                };
                
                // If it's the ID and we don't have a name yet, pre-fill it
                if (field === 'discount_id_image' && !form.discount_id_name) {
                    form.discount_id_name = extractedName;
                } else if (field === 'prescription_id_image' && !form.prescription_patient_name) {
                    form.prescription_patient_name = extractedName;
                }
            }
        } catch (error) {
            console.error('OCR failed', error);
        } finally {
            scanningFields.value[field] = false;
        }
    }
};

// Address fields
const selectedSavedAddress = ref("");
const currentAddressObj = ref(null);

// Auto-fill from saved address
watch(selectedSavedAddress, (newId) => {
    if (!newId) {
        currentAddressObj.value = null;
        return;
    }

    const address = props.savedAddresses.find((a) => a.id === newId);
    if (address) {
        currentAddressObj.value = address;
        form.customer_name = address.recipient_name || "";
        form.contact_number = String(address.contact_number || "")
            .replace(/\D/g, "")
            .slice(0, 11);

        const parts = [];
        if (address.address_line) parts.push(address.address_line);
        if (address.barangay) parts.push("Brgy. " + address.barangay);
        if (address.city) parts.push(address.city + ", Cavite");
        if (address.zip_code) parts.push(address.zip_code);

        form.delivery_address = parts.join(", ");
        form.delivery_latitude = address.latitude || null;
        form.delivery_longitude = address.longitude || null;
    }
});

onMounted(() => {
    const defaultAddress =
        props.savedAddresses.find((addr) => addr.is_default) ||
        props.savedAddresses[0];
    if (defaultAddress) {
        selectedSavedAddress.value = defaultAddress.id;
    }

    // Auto-fill TIN from user profile
    if (page.props.auth.user?.tin) {
        form.tin = page.props.auth.user.tin;
    }
});

const formatTIN = (e) => {
    let value = e.target.value.replace(/\D/g, ""); // Remove all non-digits

    // Limit to 12 digits
    if (value.length > 12) {
        value = value.slice(0, 12);
    }

    let formatted = "";
    for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 3 === 0) {
            formatted += "-";
        }
        formatted += value[i];
    }

    form.tin = formatted;
};

const form = useForm({
    customer_name: "",
    delivery_address: "",
    delivery_latitude: null,
    delivery_longitude: null,
    contact_number: "",
    notes: "",
    fulfillment_method: "delivery", // delivery | pickup
    payment_method: "gcash",
    buy_now: !!(props.queryParams?.buy_now && (props.queryParams.buy_now === 'true' || props.queryParams.buy_now === '1' || props.queryParams.buy_now === true || props.queryParams.buy_now === 1)),
    product_id: props.queryParams?.product_id ? Number(props.queryParams.product_id) : null,
    product_variation_id: props.queryParams?.product_variation_id ? Number(props.queryParams.product_variation_id) : null,
    quantity: props.queryParams?.quantity ? parseInt(props.queryParams.quantity) : null,
    tin: props.auth?.user?.tin || "",
    reference_number: "",
    proof: null,

    // New fields
    apply_discount: false,
    use_saved_discount: false,
    saved_discount_id: null,
    discount_type: "senior",
    discount_id_number: "",
    discount_id_name: "",
    discount_id_image: null,
    discount_terms: false,
    save_discount_id: false,
    discount_id_label: "",

    prescription_patient_name: "",
    prescription_id_image: null,
    prescription_image: null,
    ocr_results: {},
    selected_items: props.queryParams?.selected_items || null,

    // B2B Purchase Order fields
    po_document: null,
    saved_purchase_order_id: null,
    save_purchase_order: false,
});

// These watchers read `form`, so they must be declared after it (a watch getter runs when the
// watcher is created, and `immediate` runs the callback straight away).
watch(
    () => props.cod_available,
    (ok) => {
        if (!ok && form.payment_method === "cod") {
            form.payment_method = "gcash";
        }
    },
    { immediate: true },
);

// Clear discount errors when toggled off
watch(() => form.apply_discount, (val) => {
    if (!val) {
        // Clear all discount related errors immediately when unchecked
        const fieldsToClear = [
            'discount_type',
            'discount_id_number',
            'discount_id_name',
            'discount_id_image',
            'discount_terms'
        ];
        fieldsToClear.forEach(field => {
            if (form.errors[field]) delete form.errors[field];
        });
        
        // Also ensure any lingering validation errors from backend for these fields are cleared
        form.clearErrors(...fieldsToClear);
    }
});

const isApprovedBusiness = computed(() => {
    return !!page.props.auth?.user?.can_access_wholesale;
});

const localDiscountAmount = computed(() => {
    if (!form.apply_discount) return 0;

    // Senior/PWD Discount is 20% OFF the Net-of-VAT amount.
    // First, we find the net amount for normally vatable items
    let normallyVatableSubtotal = 0;
    props.cartItems.forEach((item) => {
        if (!item.product.is_vat_exempt) {
            normallyVatableSubtotal += Number(item.subtotal);
        }
    });

    const netOfVatSubtotal = normallyVatableSubtotal / 1.12;
    
    // Total net subtotal (Net-Vatable + originally Exempt items)
    const totalNetSubtotal = netOfVatSubtotal + props.cartItems.reduce((sum, item) => {
        return sum + (item.product.is_vat_exempt ? Number(item.subtotal) : 0);
    }, 0);

    return Math.round(totalNetSubtotal * 0.2);
});

const sanitizeContactNumber = () => {
    form.contact_number = String(form.contact_number || "")
        .replace(/\D/g, "")
        .slice(0, 11);
};

// Payment method options — compact pill style, no external icon URLs
const paymentMethods = computed(() => {
    const base = [
        { value: "gcash", label: "GCash" },
        { value: "paymaya", label: "Maya" },
        { value: "card", label: "Card" },
    ];
    if (isApprovedBusiness.value) {
        base.push({ value: "purchase_order", label: "Purchase Order (Net-30)" });
    }
    return base;
});

const grandTotal = computed(() => {
    const shipping = form.fulfillment_method === 'pickup' ? 0 : Number(props.shipping_fee_total || 0);
    const base = Number(props.subtotal || 0) + shipping;
    return Math.max(0, base - localDiscountAmount.value);
});

const originalSubtotal = computed(() => {
    return props.cartItems.reduce(
        (sum, item) => sum + Number(item.retail_unit_price) * item.quantity,
        0,
    );
});

const totalSavings = computed(() =>
    Math.max(0, originalSubtotal.value - props.subtotal),
);

const vatBreakdown = computed(() => {
    let vatableItemsRawTotal = 0;
    let exemptItemsRawTotal = 0;

    props.cartItems.forEach((item) => {
        // If apply_discount is true, ALL items become VAT Exempt for this customer
        if (form.apply_discount || item.product.is_vat_exempt) {
            exemptItemsRawTotal += Number(item.subtotal);
        } else {
            vatableItemsRawTotal += Number(item.subtotal);
        }
    });

    const shipping = form.fulfillment_method === 'pickup' ? 0 : Number(props.shipping_fee_total || 0);
    
    // Shipping is technically vatable unless also exempt by special rule, 
    // but usually stays vatable for the courier service. 
    // For simplicity and standard practice, we'll keep shipping as vatable if no discount is applied.
    const totalVatableGross = form.apply_discount ? 0 : vatableItemsRawTotal + shipping;
    const totalExemptGross = form.apply_discount 
        ? (vatableItemsRawTotal / 1.12) + exemptItemsRawTotal + shipping // Net-of-VAT gross
        : exemptItemsRawTotal;

    const vatableSales = totalVatableGross / 1.12;
    const vatAmount = totalVatableGross - vatableSales;
    const vatExemptSales = totalExemptGross;

    return {
        vatableSales,
        vatAmount,
        vatExemptSales,
    };
});

// Form validation
const isFormValid = computed(() => {
    const hasFulfillment = !!form.fulfillment_method;
    const hasAddress =
        form.fulfillment_method === 'pickup' ||
        (!!selectedSavedAddress.value &&
        form.customer_name.length >= 2 &&
        form.delivery_address.length > 5);
    const hasPaymentMethod = !!form.payment_method;
    const tinOk =
        !form.tin || /^[0-9]{3}-[0-9]{3}-[0-9]{3}-[0-9]{3}$/.test(form.tin);

    const discountOk =
        !form.apply_discount ||
        (form.discount_type &&
            form.discount_id_number.length >= 2 &&
            form.discount_id_name.length >= 2 &&
            form.discount_id_image &&
            form.discount_terms);

    const prescriptionOk =
        !props.cart_has_prescription_items ||
        (form.prescription_patient_name.length >= 2 &&
            form.prescription_id_image &&
            form.prescription_image);

    const poOk =
        form.payment_method !== 'purchase_order' ||
        form.po_document;

    return (
        hasAddress &&
        hasPaymentMethod &&
        tinOk &&
        discountOk &&
        prescriptionOk &&
        poOk
    );
});

const submitOrder = () => {
    form.post("/orders", {
        preserveScroll: true,
        onSuccess: () => {
            // Redirected to confirmation page
        },
        onError: (errors) => {
            console.error("Order placement errors:", errors);
        },
    });
};
</script>
