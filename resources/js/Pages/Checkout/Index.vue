<template>
    <MainLayout>
        <div
            class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 py-6 sm:py-8 pb-24 md:pb-8"
        >
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
                <p class="text-gray-600 mt-2">Complete your order</p>
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
                        class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4"
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
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">How would you like to get your order?</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <label
                                :class="[
                                    'flex flex-col items-center justify-center p-4 rounded-xl border-2 cursor-pointer transition-all',
                                    form.fulfillment_method === 'delivery'
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'border-gray-100 bg-gray-50 hover:border-gray-200'
                                ]"
                            >
                                <input type="radio" v-model="form.fulfillment_method" value="delivery" class="sr-only" />
                                <svg class="w-8 h-8 mb-2" :class="form.fulfillment_method === 'delivery' ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 011 1v2.5a.5.5 0 01-1 0V16zm-1.833 3.333H6.833a1.167 1.167 0 01-1.167-1.167V10H15v8.167a1.167 1.167 0 01-1.167 1.167z" />
                                </svg>
                                <span class="text-sm font-bold" :class="form.fulfillment_method === 'delivery' ? 'text-blue-700' : 'text-gray-700'">Courier Delivery</span>
                            </label>

                            <label
                                :class="[
                                    'flex flex-col items-center justify-center p-4 rounded-xl border-2 cursor-pointer transition-all',
                                    form.fulfillment_method === 'pickup'
                                        ? 'border-emerald-500 bg-emerald-50'
                                        : 'border-gray-100 bg-gray-50 hover:border-gray-200'
                                ]"
                            >
                                <input type="radio" v-model="form.fulfillment_method" value="pickup" class="sr-only" />
                                <svg class="w-8 h-8 mb-2" :class="form.fulfillment_method === 'pickup' ? 'text-emerald-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <span class="text-sm font-bold" :class="form.fulfillment_method === 'pickup' ? 'text-emerald-700' : 'text-gray-700'">Store Pick-up</span>
                            </label>
                        </div>
                    </div>

                    <!-- Delivery Information -->
                    <div v-if="form.fulfillment_method === 'delivery'" class="bg-white rounded-xl shadow-md p-6 animate-in fade-in duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-900">
                                Delivery Information
                            </h2>
                            <Link
                                href="/addresses"
                                class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                                >Manage Addresses</Link
                            >
                        </div>

                        <div class="space-y-4">
                            <!-- No Saved Addresses -->
                            <div
                                v-if="savedAddresses.length === 0"
                                class="bg-amber-50 rounded-lg p-6 text-center border border-amber-200"
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
                                    class="inline-block bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-700 transition"
                                    >Manage Addresses</a
                                >
                            </div>

                            <!-- Saved Addresses -->
                            <div
                                v-if="savedAddresses.length > 0"
                                class="mb-6 p-4 rounded-lg border border-gray-200 bg-gray-50"
                            >
                                <label
                                    class="block text-sm font-semibold text-gray-700 mb-2"
                                    >Select Delivery Address *</label
                                >
                                <select
                                    v-model="selectedSavedAddress"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white min-h-[44px] touch-manipulation"
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
                                class="mt-4 p-5 rounded-xl border-2 border-blue-500 bg-blue-50 relative overflow-hidden"
                            >
                                <div
                                    class="absolute top-0 right-0 pt-4 pr-4 text-blue-500"
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
                                            class="text-lg font-bold text-gray-900"
                                        >
                                            {{
                                                currentAddressObj.label ||
                                                "Delivery Address"
                                            }}
                                        </h3>
                                        <span
                                            v-if="currentAddressObj.is_default"
                                            class="bg-blue-200 text-blue-800 text-xs px-2.5 py-0.5 rounded-full font-semibold border border-blue-300"
                                        >
                                            Default
                                        </span>
                                    </div>
                                    <p class="font-bold text-gray-900">
                                        {{ currentAddressObj.recipient_name }}
                                    </p>
                                    <p class="text-gray-700 font-medium">
                                        {{ currentAddressObj.contact_number }}
                                    </p>
                                    <p class="text-gray-600 mt-2">
                                        {{ form.delivery_address }}
                                    </p>
                                </div>
                            </div>

                            <!-- Order Notes -->
                            <div
                                v-if="savedAddresses.length > 0"
                                class="pt-4 border-t border-gray-100"
                            >
                                <label
                                    class="block text-sm font-semibold text-gray-700 mb-2"
                                    >Order Notes (Optional)</label
                                >
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Special instructions for your order"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Pick-up Information -->
                    <div v-else class="bg-white rounded-xl shadow-md p-6 animate-in fade-in duration-300">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900">Pick-up Location</h2>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                                <p class="text-sm text-blue-800 leading-relaxed">
                                    <strong>How it works:</strong> Once your order is ready, you'll receive specific pick-up instructions and the store's exact location via notifications. No delivery fee will be charged.
                                </p>
                            </div>

                            <div class="space-y-4">
                                <div v-for="distributorId in Array.from(new Set(cartItems.map(i => i.product.distributor_id)))" :key="distributorId" class="p-4 rounded-xl border border-gray-100 bg-gray-50/50">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Seller</p>
                                    <p class="font-bold text-gray-900">{{ cartItems.find(i => i.product.distributor_id === distributorId).product.distributor_name }}</p>
                                    <div class="mt-3 flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        </svg>
                                        <p class="text-xs text-gray-700 font-medium">
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
                                            class="mt-2 inline-flex items-center text-[10px] font-bold text-blue-600 hover:text-blue-800 uppercase tracking-tighter"
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
                            <div class="pt-4 border-t border-gray-100">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pick-up Notes (Optional)</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., Someone else will pick it up for me"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-900">
                                Order Items
                            </h2>
                            <span
                                class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                                >{{ cartItems.length }} Product{{
                                    cartItems.length !== 1 ? "s" : ""
                                }}</span
                            >
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="item in cartItems"
                                :key="item.line_key"
                                class="flex justify-between items-start gap-4 p-4 rounded-xl border border-gray-50 bg-gray-50/50 hover:bg-gray-50 transition"
                            >
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="font-bold text-gray-900 truncate tracking-tight sm:text-lg"
                                    >
                                        {{ item.product.name }}
                                    </p>
                                    <p
                                        v-if="item.variation_label"
                                        class="text-xs text-blue-700 font-bold uppercase tracking-tighter mt-0.5"
                                    >
                                        {{ item.variation_label }}
                                    </p>
                                    <div class="flex flex-col gap-0.5 mt-1.5">
                                        <span
                                            class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"
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
                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-tight"
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
                                            class="text-xs text-gray-300 line-through font-medium"
                                            >₱{{
                                                Number(
                                                    item.product.base_price *
                                                        item.quantity,
                                                ).toLocaleString()
                                            }}</span
                                        >
                                        <span
                                            v-else
                                            class="text-xs text-gray-400 font-medium font-medium mt-0.5"
                                            >₱{{
                                                Number(
                                                    item.quantity *
                                                        item.unit_price,
                                                ).toLocaleString()
                                            }}</span
                                        >

                                        <!-- Final Price -->
                                        <span
                                            class="text-xl font-black text-gray-900 leading-none tabular-nums mt-1"
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
                                            class="text-[9px] font-bold text-amber-600 uppercase tracking-tighter mt-1"
                                        >
                                            VAT Exempt
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Discount / SC / PWD -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <h2 class="text-xl font-bold text-gray-900">
                                Discounts & Exemptions
                            </h2>
                        </div>

                        <div class="space-y-4">
                            <div
                                class="flex items-start gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50/50 group hover:border-blue-200 transition-colors"
                            >
                                <div class="flex-shrink-0 mt-0.5">
                                    <input
                                        v-model="form.apply_discount"
                                        type="checkbox"
                                        id="apply_discount"
                                        class="h-6 w-6 text-blue-600 rounded-md border-gray-300 focus:ring-blue-500 cursor-pointer"
                                    />
                                </div>
                                <div class="flex-1">
                                    <label
                                        for="apply_discount"
                                        class="block text-sm font-bold text-gray-900 cursor-pointer"
                                        >Apply Senior Citizen or PWD
                                        Exemption / Discount</label
                                    >
                                    <p class="text-xs text-gray-500 mt-1">
                                        Get 20% discount and VAT exemption on
                                        items for personal use.
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="form.apply_discount"
                                class="mt-4 p-4 rounded-xl bg-blue-50/50 border border-blue-100 space-y-4 animate-in fade-in slide-in-from-top-2 duration-300"
                            >
                                <!-- Discount Type -->
                                <div class="grid grid-cols-2 gap-3">
                                    <label
                                        :class="[
                                            'flex items-center justify-center gap-2 p-3 rounded-lg border-2 cursor-pointer transition',
                                            form.discount_type === 'senior'
                                                ? 'border-blue-500 bg-blue-50'
                                                : 'border-gray-200 bg-white',
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
                                            'flex items-center justify-center gap-2 p-3 rounded-lg border-2 cursor-pointer transition',
                                            form.discount_type === 'pwd'
                                                ? 'border-blue-500 bg-blue-50'
                                                : 'border-gray-200 bg-white',
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
                                            class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5"
                                            >Full Name (must match ID) *</label
                                        >
                                        <input
                                            v-model="form.discount_id_name"
                                            type="text"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                                            placeholder="e.g. JUAN DELA CRUZ"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5"
                                            >ID Number *</label
                                        >
                                        <input
                                            v-model="form.discount_id_number"
                                            type="text"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                                            placeholder="SC/PWD ID Number"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5"
                                        >ID Photo (Front/Back) *</label
                                    >
                                    <div
                                        class="flex items-center justify-center w-full"
                                    >
                                        <label
                                            class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100"
                                        >
                                            <div
                                                v-if="!form.discount_id_image"
                                                class="flex flex-col items-center justify-center pt-5 pb-6"
                                            >
                                                <svg
                                                    class="w-8 h-8 mb-4 text-gray-500"
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
                                                    class="mb-2 text-sm text-gray-500 font-semibold"
                                                >
                                                    Click to upload ID photo
                                                </p>
                                                <p
                                                    class="text-xs text-gray-400"
                                                >
                                                    PNG, JPG or WEBP (MAX. 8MB)
                                                </p>
                                            </div>
                                            <div
                                                v-else
                                                class="flex flex-col items-center justify-center p-4"
                                            >
                                                <svg
                                                    class="w-10 h-10 mb-2 text-emerald-500"
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
                                                    class="text-xs font-bold text-emerald-600 truncate max-w-xs"
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
                                                    class="mt-2 text-[10px] text-red-500 font-bold uppercase underline"
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
                                            <div v-if="scanningFields['discount_id_image']" class="absolute inset-0 bg-blue-50/50 flex flex-col items-center justify-center rounded-lg backdrop-blur-[1px]">
                                                <svg class="animate-spin h-8 w-8 text-blue-600 mb-2" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest italic animate-pulse">Scanning ID...</p>
                                            </div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2 pt-2">
                                    <input
                                        type="checkbox"
                                        v-model="form.discount_terms"
                                        id="discount_terms"
                                        required
                                        class="h-5 w-5 text-blue-600 rounded border-gray-300 mt-1 cursor-pointer"
                                    />
                                    <label
                                        for="discount_terms"
                                        class="text-xs text-gray-600 leading-snug cursor-pointer"
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
                        class="bg-white rounded-xl shadow-md p-6"
                    >
                        <div class="flex items-center gap-2 mb-4">
                            <h2 class="text-xl font-bold text-gray-900">
                                Prescription Verification
                            </h2>
                        </div>

                        <div class="space-y-4">
                            <div
                                class="bg-amber-50 border border-amber-100 rounded-lg p-3"
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
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5"
                                        >Patient Name (as per ID) *</label
                                    >
                                    <input
                                        v-model="form.prescription_patient_name"
                                        type="text"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                                        placeholder="Full name of the patient"
                                    />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5"
                                            >Patient's Valid ID *</label
                                        >
                                        <div
                                            class="flex items-center justify-center w-full"
                                        >
                                            <label
                                                class="flex flex-col items-center justify-center w-full h-36 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50/50 hover:bg-gray-100 hover:border-blue-300 transition-all group"
                                            >
                                                <div
                                                    v-if="!form.prescription_id_image"
                                                    class="flex flex-col items-center justify-center p-4 text-center"
                                                >
                                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mb-2 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                        </svg>
                                                    </div>
                                                    <p class="text-[11px] text-gray-600 font-bold mb-0.5">Upload ID Photo</p>
                                                    <p class="text-[9px] text-gray-400">PNG, JPG or WEBP</p>
                                                </div>
                                                <div
                                                    v-else
                                                    class="flex flex-col items-center justify-center p-4 text-center"
                                                >
                                                    <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mb-2">
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                    <p class="text-[10px] font-bold text-emerald-600 line-clamp-1 px-2">
                                                        {{ form.prescription_id_image.name }}
                                                    </p>
                                                    <button type="button" @click.prevent="form.prescription_id_image = null" class="mt-1 text-[9px] text-red-500 font-bold uppercase hover:underline">Change</button>
                                                </div>
                                                <input
                                                    type="file"
                                                    @change="e => onUploadFile('prescription_id_image', e.target.files[0])"
                                                    class="hidden"
                                                    accept="image/*"
                                                />
                                            </label>
                                            <div v-if="scanningFields['prescription_id_image']" class="absolute inset-0 bg-blue-50/50 flex flex-col items-center justify-center rounded-xl backdrop-blur-[1px]">
                                                <svg class="animate-spin h-6 w-6 text-blue-600 mb-1" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <p class="text-[8px] font-bold text-blue-600 uppercase tracking-widest italic animate-pulse">Scanning...</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5"
                                            >Prescription Photo *</label
                                        >
                                        <div
                                            class="flex items-center justify-center w-full"
                                        >
                                            <label
                                                class="flex flex-col items-center justify-center w-full h-36 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50/50 hover:bg-gray-100 hover:border-blue-300 transition-all group"
                                            >
                                                <div
                                                    v-if="!form.prescription_image"
                                                    class="flex flex-col items-center justify-center p-4 text-center"
                                                >
                                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mb-2 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </div>
                                                    <p class="text-[11px] text-gray-600 font-bold mb-0.5">Upload Prescription</p>
                                                    <p class="text-[9px] text-gray-400">PNG, JPG or WEBP</p>
                                                </div>
                                                <div
                                                    v-else
                                                    class="flex flex-col items-center justify-center p-4 text-center"
                                                >
                                                    <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mb-2">
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                    <p class="text-[10px] font-bold text-emerald-600 line-clamp-1 px-2">
                                                        {{ form.prescription_image.name }}
                                                    </p>
                                                    <button type="button" @click.prevent="form.prescription_image = null" class="mt-1 text-[9px] text-red-500 font-bold uppercase hover:underline">Change</button>
                                                </div>
                                                <input
                                                    type="file"
                                                    @change="e => onUploadFile('prescription_image', e.target.files[0])"
                                                    class="hidden"
                                                    accept="image/*"
                                                />
                                            </label>
                                            <div v-if="scanningFields['prescription_image']" class="absolute inset-0 bg-blue-50/50 flex flex-col items-center justify-center rounded-xl backdrop-blur-[1px]">
                                                <svg class="animate-spin h-6 w-6 text-blue-600 mb-1" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <p class="text-[8px] font-bold text-blue-600 uppercase tracking-widest italic animate-pulse">Scanning...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tax Information (B2B) -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <h2 class="text-xl font-bold text-gray-900">
                                Tax Information
                            </h2>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 mb-2"
                                    >Tax Identification Number (TIN)</label
                                >
                                <input
                                    v-model="form.tin"
                                    type="text"
                                    maxlength="15"
                                    @input="formatTIN"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono tracking-wider"
                                    placeholder="000-000-000-000"
                                />
                                <p class="mt-2 text-xs text-gray-500 italic">
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
                        class="bg-white rounded-xl shadow-md p-4 sm:p-6 lg:sticky lg:top-24"
                    >
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            Order Summary
                        </h2>

                        <div class="space-y-3 mb-6">
                            <div
                                class="flex justify-between items-center text-xs font-semibold text-gray-400 uppercase tracking-widest px-1"
                            >
                                <span>Order Summary</span>
                            </div>

                            <div
                                class="flex justify-between items-center text-sm font-medium text-gray-500 px-1 pt-1"
                            >
                                <span>Items Total</span>
                                <span class="text-gray-900 font-bold"
                                    >₱{{
                                        Number(
                                            originalSubtotal,
                                        ).toLocaleString()
                                    }}</span
                                >
                            </div>

                            <div
                                class="flex justify-between items-center text-sm font-medium text-gray-500 px-1"
                            >
                                <span>Delivery Fee</span>
                                <span class="text-gray-900 font-bold"
                                    >₱{{
                                        Number(
                                            form.fulfillment_method === 'pickup' ? 0 : (shipping_fee_total || 0),
                                        ).toLocaleString()
                                    }}</span
                                >
                            </div>

                            <div
                                v-if="totalSavings > 0"
                                class="flex justify-between items-center text-[10px] font-bold text-emerald-600 bg-emerald-50/50 px-2 py-1 rounded-md border border-emerald-100/50"
                            >
                                <span class="uppercase tracking-tight italic"
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
                                class="flex justify-between items-center text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded-md border border-blue-100"
                            >
                                <span class="uppercase tracking-tight"
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
                                class="pt-4 border-t border-gray-100 flex flex-col gap-1"
                            >
                                <span
                                    class="text-xs font-bold text-blue-600 uppercase tracking-widest"
                                    >Amount to Pay</span
                                >
                                <div class="flex items-baseline gap-2">
                                    <span
                                        v-if="
                                            totalSavings > 0 ||
                                            localDiscountAmount > 0
                                        "
                                        class="text-sm text-gray-300 line-through font-medium"
                                        >₱{{
                                            Number(
                                                originalSubtotal +
                                                    (shipping_fee_total || 0),
                                            ).toLocaleString()
                                        }}</span
                                    >
                                    <span
                                        class="text-3xl font-black text-gray-900 tabular-nums leading-none"
                                        >₱{{
                                            Number(grandTotal).toLocaleString()
                                        }}</span
                                    >
                                </div>
                            </div>

                            <!-- VAT Breakdown -->
                            <div
                                class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-100 space-y-1.5 grayscale opacity-70 hover:grayscale-0 hover:opacity-100 transition-all duration-300"
                            >
                                <div
                                    class="flex justify-between items-center text-[10px] font-bold uppercase tracking-tighter"
                                    :class="form.apply_discount ? 'text-gray-300' : 'text-gray-400'"
                                >
                                    <span>VATable Sales</span>
                                    <div class="flex items-center gap-1.5">
                                        <span v-if="form.apply_discount" class="line-through opacity-60">₱{{ 
                                            Number((props.subtotal + props.shipping_fee_total) / 1.12).toLocaleString(undefined, {
                                                minimumFractionDigits: 2, maximumFractionDigits: 2
                                            })
                                        }}</span>
                                        <span :class="{'text-emerald-600': form.apply_discount}">₱{{
                                            Number(vatBreakdown.vatableSales).toLocaleString(undefined, {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })
                                        }}</span>
                                    </div>
                                </div>
                                <div
                                    class="flex justify-between items-center text-[10px] font-bold uppercase tracking-tighter"
                                    :class="form.apply_discount ? 'text-gray-300' : 'text-gray-400'"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <span>VAT Amount (12%)</span>
                                        <span v-if="form.apply_discount" class="bg-emerald-100 text-emerald-800 text-[8px] px-1 rounded-sm tracking-widest font-black">EXEMPTED</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span v-if="form.apply_discount" class="line-through opacity-60">₱{{ 
                                            Number((props.subtotal + props.shipping_fee_total) - ((props.subtotal + props.shipping_fee_total) / 1.12)).toLocaleString(undefined, {
                                                minimumFractionDigits: 2, maximumFractionDigits: 2
                                            })
                                        }}</span>
                                        <span :class="{'text-emerald-600': form.apply_discount}">₱{{
                                            Number(vatBreakdown.vatAmount).toLocaleString(undefined, {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })
                                        }}</span>
                                    </div>
                                </div>
                                <div
                                    v-if="vatBreakdown.vatExemptSales > 0"
                                    class="flex justify-between items-center text-[10px] font-bold uppercase tracking-tighter"
                                    :class="form.apply_discount ? 'text-gray-900 border-t border-gray-100 pt-1 mt-1' : 'text-gray-400'"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <span>VAT-Exempt Sales</span>
                                        <span v-if="form.apply_discount" class="bg-blue-100 text-blue-800 text-[8px] px-1 rounded-sm tracking-widest font-black uppercase">
                                            {{ form.discount_type }} Applied
                                        </span>
                                    </div>
                                    <span :class="{'text-blue-700': form.apply_discount}">₱{{
                                        Number(
                                            vatBreakdown.vatExemptSales,
                                        ).toLocaleString(undefined, {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        })
                                    }}</span>
                                </div>
                                <p
                                    class="text-[9px] text-gray-400 italic leading-tight mt-1"
                                >
                                    Total amount is inclusive of 12% VAT where
                                    applicable.
                                </p>
                            </div>
                        </div>

                        <!-- Payment Method Selection -->
                        <div class="mb-6">
                            <h3
                                class="text-sm font-semibold text-gray-700 mb-3"
                            >
                                Payment Method *
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <!-- GCash -->
                                <label
                                    :class="[
                                        'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 cursor-pointer transition-all select-none',
                                        form.payment_method === 'gcash'
                                            ? 'border-blue-500 bg-blue-50 shadow-sm'
                                            : 'border-gray-200 hover:border-gray-300 bg-white',
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
                                                ? 'text-blue-700'
                                                : 'text-gray-700'
                                        "
                                        >GCash</span
                                    >
                                    <svg
                                        v-if="form.payment_method === 'gcash'"
                                        class="h-4 w-4 text-blue-500"
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
                                            ? 'border-green-500 bg-green-50 shadow-sm'
                                            : 'border-gray-200 hover:border-gray-300 bg-white',
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
                                                ? 'text-green-700'
                                                : 'text-gray-700'
                                        "
                                        >Maya</span
                                    >
                                    <svg
                                        v-if="form.payment_method === 'paymaya'"
                                        class="h-4 w-4 text-green-500"
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
                                            ? 'border-purple-500 bg-purple-50 shadow-sm'
                                            : 'border-gray-200 hover:border-gray-300 bg-white',
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
                                                ? 'text-purple-700'
                                                : 'text-gray-700'
                                        "
                                        >Card</span
                                    >
                                    <svg
                                        v-if="form.payment_method === 'card'"
                                        class="h-4 w-4 text-purple-500"
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

                                <!-- Wallet -->
                                <label
                                    :class="[
                                        'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 transition-all select-none',
                                        form.payment_method === 'wallet'
                                              ? 'border-emerald-500 bg-emerald-50 shadow-sm cursor-pointer'
                                              : 'border-gray-200 hover:border-gray-300 bg-white cursor-pointer',
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.payment_method"
                                        value="wallet"
                                        class="sr-only"
                                    />
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
                                            fill="#059669"
                                        />
                                        <path
                                            d="M4 8a2 2 0 012-2h12a2 2 0 012 2v2H4V8z"
                                            fill="white"
                                            fill-opacity="0.3"
                                        />
                                        <path
                                            d="M4 10h16v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6z"
                                            fill="white"
                                            fill-opacity="0.6"
                                        />
                                        <circle
                                            cx="16.5"
                                            cy="13.5"
                                            r="1.5"
                                            fill="#059669"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-semibold"
                                        :class="
                                            form.payment_method === 'wallet'
                                                ? 'text-emerald-700'
                                                : 'text-gray-700'
                                        "
                                        >Wallet</span
                                    >
                                </label>

                                <!-- Cash on Delivery (Disabled for now)
                                <label :class="[
                                    'flex items-center gap-2 px-3.5 py-2 rounded-full border-2 transition-all select-none',
                                    cod_available
                                        ? form.payment_method === 'cod'
                                            ? 'border-orange-400 bg-orange-50 shadow-sm cursor-pointer'
                                            : 'border-gray-200 hover:border-gray-300 bg-white cursor-pointer'
                                        : 'border-gray-100 bg-gray-100 cursor-not-allowed opacity-60'
                                ]">
                                    <input type="radio" v-model="form.payment_method" value="cod" class="sr-only" :disabled="!cod_available" />
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="24" height="24" rx="4" fill="#EA580C"/>
                                        <rect x="2" y="7" width="20" height="10" rx="1.5" fill="white" fill-opacity="0.25"/>
                                        <circle cx="12" cy="12" r="3" fill="white" fill-opacity="0.7"/>
                                        <path d="M4 9.5h2M18 9.5h2M4 14.5h2M18 14.5h2" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                    <span class="text-sm font-semibold" :class="form.payment_method === 'cod' ? 'text-orange-700' : 'text-gray-700'">Cash on Delivery</span>
                                    <svg v-if="form.payment_method === 'cod'" class="h-4 w-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </label>
                                -->
                            </div>
                            <p
                                v-if="form.payment_method === 'wallet'"
                                class="mt-2 text-xs text-gray-600"
                            >
                                Wallet Balance:
                                <span class="font-semibold"
                                    >₱{{
                                        Number(
                                            wallet_balance || 0,
                                        ).toLocaleString()
                                    }}</span
                                >
                            </p>
                            <p
                                v-if="
                                    form.payment_method === 'wallet' &&
                                    Number(wallet_balance || 0) < grandTotal
                                "
                                class="mt-1 text-xs text-red-600"
                            >
                                Insufficient wallet balance for this checkout
                                total.
                            </p>
                            <p
                                v-if="false && !cod_available"
                                class="mt-2 text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2"
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

                        <div
                            v-if="form.payment_method !== 'cod'"
                            class="bg-green-50 border border-green-200 rounded-lg p-3 mb-6"
                        >
                            <div class="flex items-start">
                                <svg
                                    class="h-5 w-5 text-green-600 mr-2 mt-0.5 flex-shrink-0"
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
                                <p class="text-xs text-green-800">
                                    <strong>Buyer protection:</strong> Your
                                    payment is held by the platform until you
                                    confirm receipt of your order.
                                </p>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing || !isFormValid"
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-xl hover:shadow-xl transition font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
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
                                    : `Pay ₱${Number(grandTotal).toLocaleString()} & Place Order`
                            }}
                        </button>

                        <a
                            href="/cart"
                            class="block w-full text-center text-blue-600 hover:text-blue-700 mt-4 font-medium"
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
    wallet_balance: {
        type: Number,
        default: 0,
    },
    cities: Object,
    barangays: Object,
    savedAddresses: {
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
        delete form.errors.discount_type;
        delete form.errors.discount_id_number;
        delete form.errors.discount_id_name;
        delete form.errors.discount_id_image;
        delete form.errors.discount_terms;
    }
});

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
    discount_type: "senior",
    discount_id_number: "",
    discount_id_name: "",
    discount_id_image: null,
    discount_terms: false,

    prescription_patient_name: "",
    prescription_id_image: null,
    prescription_image: null,
    ocr_results: {},
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
const paymentMethods = [
    { value: "gcash", label: "GCash" },
    { value: "paymaya", label: "Maya" },
    { value: "card", label: "Card" },
    { value: "wallet", label: "Wallet" },
];

const grandTotal = computed(() => {
    const shipping = form.fulfillment_method === 'pickup' ? 0 : Number(props.shipping_fee_total || 0);
    const base = Number(props.subtotal || 0) + shipping;
    return Math.max(0, base - localDiscountAmount.value);
});

const originalSubtotal = computed(() => {
    return props.cartItems.reduce(
        (sum, item) => sum + Number(item.product.base_price) * item.quantity,
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
    const walletOk =
        form.payment_method !== "wallet" ||
        Number(props.wallet_balance || 0) >= grandTotal.value;

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

    return (
        hasAddress &&
        hasPaymentMethod &&
        walletOk &&
        tinOk &&
        discountOk &&
        prescriptionOk
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
