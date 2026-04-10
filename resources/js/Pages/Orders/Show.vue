<template>
    <MainLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Order Details</h1>
                    <p class="text-gray-600 mt-2">{{ order.order_number }}</p>
                </div>
                <Link href="/my-orders" class="text-blue-600 hover:text-blue-700 font-medium flex items-center">
                    <svg class="h-5 w-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to My Orders
                </Link>
            </div>

            <div
                v-if="order.prescription_status === 'awaiting_upload'"
                class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
            >
                <div>
                    <p class="font-semibold text-amber-900">Prescription required</p>
                    <p class="text-sm text-amber-800 mt-1">Upload a clear photo of your prescription. Payment is available after the distributor approves it.</p>
                </div>
                <Link
                    :href="`/orders/${order.id}/prescription`"
                    class="inline-flex justify-center px-4 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 shadow-sm"
                >
                    Upload prescription
                </Link>
            </div>
            <div
                v-else-if="order.prescription_status === 'pending_review'"
                class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4"
            >
                <p class="font-semibold text-blue-900">Prescription under review</p>
                <p class="text-sm text-blue-800 mt-1">The distributor is verifying your prescription. You can pay once it’s approved.</p>
            </div>
            <div
                v-else-if="order.prescription_status === 'rejected'"
                class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4"
            >
                <p class="font-semibold text-red-900">Prescription not accepted</p>
                <p v-if="order.prescription_review_note" class="text-sm text-red-800 mt-1">{{ order.prescription_review_note }}</p>
            </div>

            <!-- Discount Status -->
            <template v-if="order.discount_status !== 'none'">
                <div
                    v-if="order.discount_status === 'pending'"
                    class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <p class="font-semibold text-blue-900 capitalize">{{ order.discount_type }} Discount requested</p>
                    </div>
                    <p class="text-sm text-blue-800 mt-1 ml-7">The distributor is reviewing your ID for the 20% discount and VAT exemption.</p>
                </div>
                <div
                    v-else-if="order.discount_status === 'approved'"
                    class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <p class="font-semibold text-emerald-900 capitalize">{{ order.discount_type }} Discount Approved</p>
                    </div>
                    <p class="text-sm text-emerald-800 mt-1 ml-7">Your 20% discount and VAT exemption have been applied to this order.</p>
                </div>
                <div
                    v-else-if="order.discount_status === 'rejected'"
                    class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        <p class="font-semibold text-red-900 capitalize">{{ order.discount_type }} Discount Rejected</p>
                    </div>
                    <p v-if="order.discount_review_note" class="text-sm text-red-800 mt-1 ml-7">{{ order.discount_review_note }}</p>
                </div>
            </template>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Order Timeline -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <OrderTimeline 
                            :current-status="order.status"
                            :created-at="order.created_at"
                            :approved-at="order.approved_at"
                            :packed-at="order.packed_at"
                            :shipped-at="order.shipped_at"
                            :delivered-at="order.delivered_at"
                            :cancelled-at="order.cancelled_at"
                            :rejected-at="order.rejected_at"
                        />
                    </div>

                    <!-- Confirm receipt (standalone — required before ratings / seller payout) -->
                    <div
                        v-if="canConfirmReceived"
                        class="rounded-xl border-2 border-emerald-200 bg-emerald-50 p-5 sm:p-6 shadow-sm"
                    >
                        <h2 class="text-lg font-bold text-emerald-950">Confirm order received</h2>
                        <p class="text-sm text-emerald-900 mt-2 leading-relaxed">
                            Mark this order as received after your items arrive. You can rate products and delivery only after you confirm. For online payments, this also allows the seller to receive their payout.
                        </p>
                        <button
                            type="button"
                            class="mt-4 w-full sm:w-auto inline-flex justify-center items-center gap-2 px-6 py-3 rounded-xl bg-emerald-700 text-white text-sm font-bold hover:bg-emerald-800 shadow-sm min-h-[48px]"
                            @click="confirmReceived"
                        >
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            I have received my order
                        </button>
                    </div>

                    <!-- Order Info -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-start mb-6">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">Order Information</h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    Placed on <DateFormat :date="order.created_at" format="datetime" />
                                </p>
                            </div>
                            <div class="sm:text-right sm:max-w-sm">
                                <StatusBadge :status="order.status" type="order" />
                                <p
                                    v-if="statusMessage(order.status)"
                                    class="text-sm text-gray-600 mt-2 leading-relaxed sm:ml-auto sm:text-right"
                                >
                                    {{ statusMessage(order.status) }}
                                </p>
                                <div v-if="order.is_fragile" class="mt-3 sm:ml-auto flex items-center justify-end">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-[10px] font-black uppercase tracking-widest shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Fragile Package
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Distributor</p>
                                <Link 
                                    v-if="order.distributor.slug"
                                    :href="`/seller/${order.distributor.slug}`"
                                    class="font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                                >
                                    {{ order.distributor.company_name }}
                                </Link>
                                <p v-else class="font-semibold text-gray-900">{{ order.distributor.company_name }}</p>
                                <p class="text-sm text-gray-600">{{ order.distributor.email }}</p>
                                <p v-if="order.distributor.user?.phone_number" class="text-sm text-gray-600 mt-1">
                                    <span class="font-medium text-gray-900">Contact:</span> {{ order.distributor.user.phone_number }}
                                </p>
                            </div>
                             <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">
                                    {{ order.fulfillment_method === 'pickup' ? 'Pick-up Location' : 'Delivery Address' }}
                                </p>
                                <p class="text-gray-900">{{ order.fulfillment_method === 'pickup' ? order.distributor.address : order.delivery_address }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Contact Number</p>
                                <p class="font-semibold text-gray-900">{{ order.contact_number }}</p>
                            </div>
                            <div v-if="order.tin">
                                <p class="text-sm font-medium text-gray-600 mb-1">TIN</p>
                                <p class="font-mono font-semibold text-gray-900">{{ order.tin }}</p>
                            </div>
                             <div v-if="order.notes" :class="{'md:col-span-2': !order.tin}">
                                <p class="text-sm font-medium text-gray-600 mb-1">Notes</p>
                                <p class="text-sm text-gray-700">{{ order.notes }}</p>
                            </div>
                            <!-- Pickup Instructions -->
                            <div v-if="order.fulfillment_method === 'pickup' && order.status !== 'pending' && order.status !== 'rejected' && order.status !== 'cancelled'" class="md:col-span-2 mt-4 p-4 rounded-xl border-2 border-emerald-100 bg-emerald-50">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 011 1v2.5a.5.5 0 01-1 0V16zm-1.833 3.333H6.833a1.167 1.167 0 01-1.167-1.167V10H15v8.167a1.167 1.167 0 01-1.167 1.167z" />
                                    </svg>
                                    <h3 class="font-bold text-emerald-900">Pick-up Instructions</h3>
                                </div>
                                <p v-if="order.pickup_instructions" class="text-sm text-emerald-800 whitespace-pre-wrap">{{ order.pickup_instructions }}</p>
                                <p v-else class="text-sm text-emerald-800 italic">Distributor has not provided specific instructions. Please contact them through chat for details.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Messages</h2>
                            <p class="text-sm text-gray-600 mt-1">Chat with the seller in your MedEquip inbox — same thread as from the shop or product page.</p>
                        </div>
                        <Link
                            :href="orderMessaging.href"
                            class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 shadow-sm shrink-0"
                        >
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            {{ orderMessaging.label }}
                        </Link>
                    </div>

                    <div
                        v-if="reviewState?.eligible"
                        class="bg-white rounded-xl shadow-md border border-indigo-100 p-5 sm:p-6 mb-6"
                    >
                        <h2 class="text-lg font-bold text-gray-900">Rate this order</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Let us know how your experience was.
                        </p>

                        <div v-if="reviewState.product_rows?.length" class="mt-5 space-y-5">
                            <div
                                v-for="row in reviewState.product_rows"
                                :key="row.product_id"
                                class="border border-gray-100 rounded-xl p-4"
                            >
                                <p class="font-semibold text-gray-900">{{ row.name }}</p>
                                <div class="flex flex-wrap gap-1 mt-2" role="group" aria-label="Star rating">
                                    <button
                                        v-for="s in 5"
                                        :key="s"
                                        type="button"
                                        class="leading-none min-w-[44px] min-h-[44px] rounded-lg transition p-1"
                                        :class="s <= (productRatings[row.product_id]?.stars ?? 5) ? 'text-amber-400' : 'text-gray-200'"
                                        @click="setProductStar(row.product_id, s)"
                                    >
                                        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    </button>
                                </div>
                                <textarea
                                    v-model="productRatings[row.product_id].body"
                                    rows="2"
                                    maxlength="2000"
                                    placeholder="Optional review (visible on the product)"
                                    class="mt-2 w-full rounded-lg border-gray-300 text-sm"
                                />
                                <p v-if="row.reviewed" class="text-xs text-emerald-700 font-semibold mt-2">Saved — you can update anytime.</p>
                            </div>
                        </div>

                        <div v-if="reviewState.delivery?.eligible" class="mt-8 pt-6 border-t border-gray-100">
                            <h3 class="font-bold text-gray-900">Delivery experience</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Courier: {{ reviewState.delivery.courier_label }}</p>
                            <div class="flex flex-wrap gap-1 mt-2">
                                <button
                                    v-for="s in 5"
                                    :key="'d-' + s"
                                    type="button"
                                    class="leading-none min-w-[44px] min-h-[44px] rounded-lg transition p-1"
                                    :class="s <= deliveryStars ? 'text-sky-500' : 'text-gray-200'"
                                    @click="deliveryStars = s"
                                >
                                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </button>
                            </div>
                            <textarea
                                v-model="deliveryBody"
                                rows="2"
                                maxlength="2000"
                                placeholder="Optional — delays, handling, region notes…"
                                class="mt-2 w-full rounded-lg border-gray-300 text-sm"
                            />
                        </div>
                        <div v-else-if="reviewState.delivery?.submitted" class="mt-8 pt-6 border-t border-gray-100 text-sm text-gray-600">
                            <span class="font-semibold text-gray-800">Delivery rated</span>
                            <span class="inline-flex items-center gap-0.5 ml-1">
                                <svg class="w-3.5 h-3.5 text-sky-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                {{ reviewState.delivery.stars }}
                            </span>
                            <span v-if="reviewState.delivery.body" class="block mt-1 text-gray-500">{{ reviewState.delivery.body }}</span>
                        </div>

                        <div
                            v-if="reviewState.product_rows?.length || reviewState.delivery?.eligible"
                            class="mt-8 pt-6 border-t border-gray-100"
                        >
                            <button
                                type="button"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 min-h-[44px]"
                                @click="submitAllRatings"
                            >
                                Save all ratings
                            </button>
                            <p class="text-xs text-gray-500 mt-2">Saves product reviews and your delivery rating together when both apply.</p>
                        </div>
                    </div>

                    <!-- Order Items with Images -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Order Items</h2>
                        
                        <div class="space-y-4">
                            <div 
                                v-for="item in order.items" 
                                :key="item.id"
                                class="flex gap-4 pb-4 border-b last:border-0 hover:bg-gray-50 rounded-lg p-3 -m-3 transition"
                            >
                                <!-- Product Image -->
                                <div class="w-24 h-24 flex-shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg overflow-hidden">
                                    <img 
                                        v-if="item.product.image_path"
                                        :src="`/storage/${item.product.image_path}`" 
                                        :alt="item.product.name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Product Details -->
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 text-lg">{{ item.product.name }}</h3>
                                    <p v-if="item.product_variation" class="text-sm text-blue-700 font-medium mt-0.5">
                                        {{ item.product_variation.display_label || `${item.product_variation.option_name}: ${item.product_variation.option_value}` }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-3 mt-3">
                                        <span class="text-sm text-gray-600">Quantity: <span class="font-semibold">{{ item.quantity }}</span></span>
                                        <span class="text-gray-300">•</span>
                                        <div class="flex items-center gap-1">
                                            <PriceDisplay :amount="item.unit_price" size="small" />
                                            <span class="text-sm text-gray-600">each</span>
                                        </div>
                                        <span 
                                            v-if="item.is_wholesale" 
                                            class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-700 text-xs px-3 py-1 rounded-full font-semibold"
                                        >
                                            Wholesale Price
                                        </span>
                                    </div>
                                </div>

                                <!-- Subtotal -->
                                <div class="text-right self-center">
                                    <p class="text-xs text-gray-600 mb-1">Subtotal</p>
                                    <PriceDisplay :amount="item.total_price" />
                                </div>
                            </div>
                        </div>

                        <!-- Totals -->
                        <div class="mt-6 pt-6 border-t-2 border-gray-200">
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">Items Subtotal</span>
                                    <PriceDisplay :amount="orderSubtotal" />
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">Shipping Fee</span>
                                    <PriceDisplay :amount="orderShippingFee" />
                                </div>
                                <div v-if="order.discount > 0" class="flex justify-between items-center text-sm text-emerald-600 font-medium italic">
                                    <span>{{ order.discount_type === 'senior' ? 'Senior' : 'PWD' }} Discount</span>
                                    <span>- <PriceDisplay :amount="order.discount" color="emerald" /></span>
                                </div>

                                <!-- VAT Breakdown -->
                                <div class="pt-3 pb-1 border-t border-gray-100 space-y-1 mt-2">
                                    <div class="flex justify-between text-[10px] font-bold text-gray-400 uppercase tracking-tighter">
                                        <span>VATable Sales</span>
                                        <span>₱{{ Number(order.vatable_sales || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                                    </div>
                                    <div class="flex justify-between text-[10px] font-bold text-gray-400 uppercase tracking-tighter">
                                        <span>VAT Amount (12%)</span>
                                        <span>₱{{ Number(order.vat_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                                    </div>
                                    <div v-if="order.vat_exempt_sales > 0" class="flex justify-between text-[10px] font-bold text-gray-400 uppercase tracking-tighter">
                                        <span>VAT-Exempt Sales</span>
                                        <span>₱{{ Number(order.vat_exempt_sales || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center pt-2 border-t">
                                    <span class="text-xl font-bold text-gray-900">Total Amount</span>
                                    <PriceDisplay :amount="orderGrandTotal" size="large" color="blue" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Tracking (Only if not pickup) -->
                    <div v-if="order.delivery && order.fulfillment_method !== 'pickup'" class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Delivery Tracking</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Tracking Number</p>
                                <p class="font-mono font-semibold text-gray-900 text-lg">{{ order.delivery.tracking_number }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Carrier</p>
                                <p class="font-semibold text-gray-900">
                                    MedEquip Express
                                    <span v-if="order.delivery.courier?.user?.name" class="text-gray-500 font-normal">
                                        ({{ order.delivery.courier.user.name }})
                                    </span>
                                </p>
                                <p v-if="order.delivery.courier?.user?.phone_number" class="text-sm text-gray-600 mt-1">
                                    <span class="font-medium text-gray-900">Driver Contact:</span> {{ order.delivery.courier.user.phone_number }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Delivery Status</p>
                                <StatusBadge :status="order.delivery.status" type="delivery" />
                            </div>
                            <div v-if="order.delivery.actual_delivery_at">
                                <p class="text-sm font-medium text-gray-600 mb-1">Delivered On</p>
                                <p class="font-semibold text-gray-900">
                                    <DateFormat :date="order.delivery.actual_delivery_at" format="datetime" />
                                </p>
                            </div>
                            
                            <!-- Proof of Delivery Image -->
                            <div v-if="order.delivery.proof_of_delivery_path" class="md:col-span-2 pt-4 border-t mt-2">
                                <p class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-3">Proof of Delivery</p>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-2 inline-block">
                                    <img 
                                        :src="`/storage/${order.delivery.proof_of_delivery_path}`" 
                                        alt="Proof of Delivery Photo" 
                                        class="max-w-xs md:max-w-md rounded-lg shadow-sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Invoice -->
                    <div v-if="order.invoice" class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-md p-6 border border-gray-100">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Invoice
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-600 mb-1">Invoice Number</p>
                                <p class="font-mono text-sm font-semibold text-gray-900">{{ order.invoice.invoice_number }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 mb-1">Payment Status</p>
                                <span
                                    :class="{
                                        'bg-green-100 text-green-800': order.customer_payment_status?.state === 'paid',
                                        'bg-amber-100 text-amber-800': order.customer_payment_status?.state === 'pending_verification' || order.customer_payment_status?.state === 'cod_pending',
                                        'bg-red-100 text-red-800': order.customer_payment_status?.state === 'payment_failed' || order.customer_payment_status?.state === 'cancelled',
                                        'bg-yellow-100 text-yellow-800': !order.customer_payment_status || order.customer_payment_status?.state === 'unpaid',
                                    }"
                                    class="inline-block px-3 py-1 rounded-full text-xs font-semibold"
                                >
                                    {{ order.customer_payment_status?.label || 'Unpaid' }}
                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 mb-1">Due Date</p>
                                <p class="text-sm font-semibold text-gray-900">
                                    <DateFormat :date="order.invoice.due_date" format="short" />
                                </p>
                            </div>
                            <div v-if="order.invoice_payment_display?.label">
                                <p class="text-xs text-gray-600 mb-1">Payment method</p>
                                <p class="text-sm font-semibold text-gray-900">{{ order.invoice_payment_display.label }}</p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="!order.invoice && order.prescription_status && order.prescription_status !== 'not_required'"
                        class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-md p-6 border border-gray-100"
                    >
                        <h3 class="font-bold text-gray-900 mb-3">Payment</h3>
                        <p class="text-xs text-gray-600 mb-2">Prescription workflow</p>
                        <span
                            :class="{
                                'bg-amber-100 text-amber-900': order.customer_payment_status?.state === 'rx_upload',
                                'bg-blue-100 text-blue-900': order.customer_payment_status?.state === 'rx_review',
                                'bg-red-100 text-red-900': order.customer_payment_status?.state === 'rx_rejected',
                            }"
                            class="inline-block px-3 py-1 rounded-full text-xs font-semibold"
                        >
                            {{ order.customer_payment_status?.label || 'Pending' }}
                        </span>
                        <p class="text-xs text-gray-500 mt-3">An invoice is created after your prescription is approved.</p>
                    </div>

                    <!-- Actions -->
                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl shadow-lg p-6 text-white">
                        <h3 class="font-bold mb-4 text-lg">Quick Actions</h3>
                        <div class="space-y-3">
                            <button 
                                v-if="order.status === 'pending' || order.status === 'approved'"
                                @click="cancelOrder"
                                class="w-full bg-white/20 hover:bg-white/30 backdrop-blur-sm transition px-4 py-3 rounded-lg font-medium text-left flex items-center"
                            >
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Cancel Order
                            </button>
                            <button
                                v-if="canPayNow"
                                @click="payNow"
                                class="w-full bg-white/20 hover:bg-white/30 backdrop-blur-sm transition px-4 py-3 rounded-lg font-medium text-left flex items-center"
                            >
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8a2 2 0 012-2zm8 4h.01" />
                                </svg>
                                Pay Now
                            </button>
                            <Link 
                                href="/products"
                                class="w-full bg-white/20 hover:bg-white/30 backdrop-blur-sm transition px-4 py-3 rounded-lg font-medium text-left flex items-center"
                            >
                                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Continue Shopping
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import OrderTimeline from '@/Components/OrderTimeline.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DateFormat from '@/Components/DateFormat.vue';
import PriceDisplay from '@/Components/PriceDisplay.vue';
import { customerOrderStatusMessage } from '@/utils/customerOrderStatusMessage.js';
import { computed, reactive, ref, watch } from 'vue';

const statusMessage = (s) => customerOrderStatusMessage(s);

const props = defineProps({
    order: Object,
    orderMessaging: {
        type: Object,
        required: true,
    },
    reviewState: { type: Object, default: null },
});

const productRatings = reactive({});
const deliveryStars = ref(5);
const deliveryBody = ref('');

watch(
    () => props.reviewState,
    (rs) => {
        if (!rs?.product_rows) {
            return;
        }
        for (const row of rs.product_rows) {
            productRatings[row.product_id] = {
                stars: row.stars ?? productRatings[row.product_id]?.stars ?? 5,
                body: row.body ?? productRatings[row.product_id]?.body ?? '',
            };
        }
    },
    { immediate: true, deep: true }
);

function setProductStar(productId, stars) {
    if (!productRatings[productId]) {
        productRatings[productId] = { stars: 5, body: '' };
    }
    productRatings[productId].stars = stars;
}

function submitAllRatings() {
    const orderId = props.order.id;
    const hasProducts = props.reviewState?.product_rows?.length;
    const deliveryEligible = props.reviewState?.delivery?.eligible;

    const postDelivery = () => {
        if (deliveryEligible) {
            router.post(`/orders/${orderId}/reviews/delivery`, {
                stars: deliveryStars.value,
                body: deliveryBody.value || null,
            }, { preserveScroll: true });
        }
    };

    if (hasProducts) {
        const reviews = props.reviewState.product_rows.map((row) => ({
            product_id: row.product_id,
            stars: productRatings[row.product_id]?.stars ?? 5,
            body: productRatings[row.product_id]?.body || null,
        }));
        router.post(`/orders/${orderId}/reviews/products`, { reviews }, {
            preserveScroll: true,
            onSuccess: () => postDelivery(),
        });
        return;
    }

    postDelivery();
}

const cancelOrder = () => {
    if (confirm(`Are you sure you want to cancel order ${props.order.order_number}? This action cannot be undone.`)) {
        router.post(`/orders/${props.order.id}/cancel`, {}, {
            onSuccess: () => {
            },
            onError: (errors) => {
                console.error('[OrderShow] Cancel failed', errors);
            }
        });
    }
};

const confirmReceived = () => {
    if (confirm(`Confirm that you received order ${props.order.order_number}? This completes the order and releases payment held by the platform to the seller.`)) {
        router.post(`/orders/${props.order.id}/confirm-received`, {}, {
            onSuccess: () => {
            },
            onError: (errors) => {
                console.error('[OrderShow] Confirm failed', errors);
            }
        });
    }
};

const canPayNow = computed(() => {
    return !!props.order?.can_pay_now;
});

const canConfirmReceived = computed(() => {
    if (props.order?.received_at) return false;
    if (props.order?.fulfillment_method === 'pickup') {
        return props.order?.status === 'ready_for_pickup';
    }
    return props.order?.status === 'delivered';
});

const orderSubtotal = computed(() => Number(props.order?.subtotal || 0));
const orderShippingFee = computed(() => Number(props.order?.shipping_fee || 0));
const orderGrandTotal = computed(() => {
    const total = Number(props.order?.total_amount || 0);
    if (total > 0) return total;
    return orderSubtotal.value + orderShippingFee.value;
});

const payNow = () => {
    router.post(`/orders/${props.order.id}/pay-now`);
};
</script>
