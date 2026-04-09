<template>
    <OwnerLayout>
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 py-6 pb-28 sm:pb-10 min-w-0">
            <!-- Header -->
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-start">
                <div class="min-w-0">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Order Details</h1>
                    <p class="text-gray-600 mt-1 text-sm sm:text-base break-all">{{ order.order_number }}</p>
                </div>
                <Link href="/owner/orders" class="text-blue-600 hover:text-blue-700 font-medium text-sm sm:text-base py-2 sm:py-0 inline-flex items-center min-h-[44px] sm:min-h-0 touch-manipulation shrink-0">
                    ← Orders
                </Link>
            </div>

            <!-- Return to Sender Alert -->
            <div v-if="order.delivery?.is_return_to_sender" class="mb-6 bg-rose-50 border-2 border-rose-200 rounded-xl p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15l-4 4m0 0l-4-4m4 4V9m0 0a2 2 0 012-2h2m-4 0a2 2 0 00-2 2v2m-6 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-rose-900 uppercase tracking-tight">Return To Sender</h3>
                        <p class="text-sm text-rose-800 mt-1 font-medium italic">"{{ order.delivery.failure_note || 'Package could not be delivered after 2 attempts.' }}"</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="bg-rose-100 text-rose-700 text-[10px] font-black px-2 py-1 rounded-md border border-rose-200 uppercase">Reason: {{ order.delivery.failure_reason.replace('_', ' ') }}</span>
                            <span class="bg-rose-100 text-rose-700 text-[10px] font-black px-2 py-1 rounded-md border border-rose-200 uppercase">Attempts: {{ order.delivery.attempts_count }}</span>
                            <span class="bg-rose-100 text-rose-700 text-[10px] font-black px-2 py-1 rounded-md border border-rose-200 uppercase">Last Effort: {{ formatDate(order.delivery.last_attempt_at) }}</span>
                        </div>
                        <div v-if="order.delivery.proof_of_attempt_path" class="mt-4">
                            <p class="text-[10px] font-bold text-rose-400 uppercase tracking-widest mb-1.5">Latest Attempt Evidence</p>
                            <a :href="`/storage/${order.delivery.proof_of_attempt_path}`" target="_blank" class="inline-block group relative">
                                <img :src="`/storage/${order.delivery.proof_of_attempt_path}`" class="max-w-[200px] rounded-lg border-2 border-rose-200 shadow-sm group-hover:opacity-90 transition object-cover h-32 w-48" />
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                    <span class="bg-black/50 text-white px-2 py-1 rounded-full text-[10px] font-bold uppercase">View Proof</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Messages -->
            <div v-if="$page.props.errors && Object.keys($page.props.errors).length > 0" class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-red-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-red-800 mb-1">Unable to update order status</h3>
                        <ul class="text-sm text-red-700 space-y-1">
                            <li v-for="(error, key) in $page.props.errors" :key="key">{{ error }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                <!-- Order Details -->
                <div class="lg:col-span-2 space-y-4 sm:space-y-6 min-w-0 order-2 lg:order-1">
                    <!-- Order Info Card -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 overflow-hidden">
                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-start mb-4 sm:mb-6">
                            <div class="min-w-0">
                                <h2 class="text-lg sm:text-xl font-bold text-gray-900">Order Information</h2>
                                <p class="text-sm text-gray-600 mt-1">Placed on {{ formatDate(order.created_at) }}</p>
                            </div>
                            <span 
                                :class="{
                                    'bg-yellow-100 text-yellow-800': order.status === 'pending',
                                    'bg-blue-100 text-blue-800': order.status === 'approved',
                                    'bg-purple-100 text-purple-800': order.status === 'packed',
                                    'bg-emerald-100 text-emerald-800 border border-emerald-200': order.status === 'ready_for_pickup',
                                    'bg-indigo-100 text-indigo-800': order.status === 'shipped',
                                    'bg-green-100 text-green-800': order.status === 'delivered',
                                    'bg-red-100 text-red-800': order.status === 'rejected' || order.status === 'cancelled',
                                }"
                                class="px-4 py-2 rounded-full text-sm font-semibold capitalize self-start shrink-0"
                            >
                                {{ order.status.replace(/_/g, ' ') }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-600">Customer</p>
                                <p class="font-semibold text-gray-900 break-words">{{ order.customer.name }}</p>
                                <p class="text-sm text-gray-600 break-all">{{ order.customer.email }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-600">Contact Number</p>
                                <p class="font-semibold text-gray-900">{{ order.contact_number }}</p>
                                <p v-if="order.customer?.phone_number && order.customer.phone_number !== order.contact_number" class="text-xs text-blue-600 font-medium">
                                    Profile: {{ order.customer.phone_number }}
                                </p>
                            </div>
                            <div class="sm:col-span-2 min-w-0">
                                <p class="text-sm text-gray-600">
                                    {{ order.fulfillment_method === 'pickup' ? 'Pick-up Location (Store)' : 'Delivery Address' }}
                                </p>
                                <p class="font-semibold text-gray-900 break-words">
                                    {{ order.fulfillment_method === 'pickup' ? order.distributor.address : order.delivery_address }}
                                </p>
                                <p v-if="order.fulfillment_method === 'pickup'" class="text-xs text-emerald-600 font-bold mt-1 uppercase tracking-tighter">
                                    Customer has chosen Store Pick-up
                                </p>
                            </div>
                            <div v-if="order.notes" class="sm:col-span-2 min-w-0">
                                <p class="text-sm text-gray-600">Customer Notes</p>
                                <p class="text-sm text-gray-700">{{ order.notes }}</p>
                            </div>
                        </div>
                    </div>



                    <!-- Discount Review -->
                    <div
                        v-if="order.discount_status === 'pending'"
                        class="bg-white rounded-xl shadow-md p-6 border border-blue-200"
                    >
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Discount Request Review</h2>
                        <div class="bg-blue-50 rounded-lg p-3 mb-4">
                            <p class="text-sm font-bold text-blue-900 capitalize">{{ order.discount_type }} Discount Requested</p>
                            <p class="text-xs text-blue-700 mt-1">Verify that the ID photo matches the name and valid ID number below.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Name on ID</p>
                                <p class="text-sm font-bold text-gray-900 uppercase flex items-center gap-2">
                                    {{ order.discount_id_name }}
                                    <span v-if="order.ocr_results?.discount_id_image?.extracted_name" 
                                          class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-tighter"
                                          :class="order.ocr_results.discount_id_image.extracted_name.toLowerCase() === order.discount_id_name.toLowerCase() 
                                              ? 'bg-emerald-100 text-emerald-700' 
                                              : 'bg-rose-100 text-rose-700 border border-rose-200'"
                                    >
                                        {{ order.ocr_results.discount_id_image.extracted_name.toLowerCase() === order.discount_id_name.toLowerCase() ? 'OCR Match' : 'OCR Mismatch' }}
                                    </span>
                                </p>
                                <p v-if="order.ocr_results?.discount_id_image?.extracted_name && order.ocr_results.discount_id_image.extracted_name.toLowerCase() !== order.discount_id_name.toLowerCase()" 
                                   class="text-[9px] text-rose-600 font-bold mt-1 italic">
                                    OCR Extracted: "{{ order.ocr_results.discount_id_image.extracted_name }}"
                                </p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">ID Number</p>
                                <p class="text-sm font-bold text-gray-900 font-mono">{{ order.discount_id_number }}</p>
                            </div>
                        </div>

                        <div v-if="order.discount_id_image_path" class="mb-4">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">ID Photo (Click to enlarge)</p>
                            <a :href="`/storage/${order.discount_id_image_path}`" target="_blank" class="inline-block group relative">
                                <img :src="`/storage/${order.discount_id_image_path}`" class="max-w-xs rounded-lg border shadow-sm group-hover:opacity-90 transition" />
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                    <span class="bg-black/50 text-white px-3 py-1 rounded-full text-xs font-bold">Open Full Size</span>
                                </div>
                            </a>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <button
                                @click="approveDiscount"
                                :disabled="discountProcessing"
                                class="px-5 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition disabled:opacity-50"
                            >
                                Approve Discount
                            </button>
                            <form @submit.prevent="rejectDiscount" class="flex-1 flex gap-2">
                                <input v-model="discountRejectReason" required placeholder="Reason for rejection" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
                                <button
                                    type="submit"
                                    :disabled="discountProcessing"
                                    class="px-4 py-2 border-2 border-red-300 text-red-600 font-bold rounded-xl hover:bg-red-50 text-xs transition"
                                >
                                    Reject & Cancel
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Prescription (Rx) review -->
                    <div
                        v-if="order.prescription_status === 'awaiting_upload'"
                        class="bg-amber-50 border border-amber-200 rounded-xl p-6"
                    >
                        <h2 class="text-lg font-bold text-amber-900 mb-1">Prescription pending</h2>
                        <p class="text-sm text-amber-800">The customer has not uploaded a prescription photo yet.</p>
                    </div>
                    <div
                        v-else-if="order.prescription_status === 'pending_review' && order.prescription_image_path"
                        class="bg-white rounded-xl shadow-md p-6 border border-indigo-200"
                    >
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Prescription Review</h2>
                        <div class="bg-indigo-50 rounded-lg p-3 mb-4">
                            <p class="text-sm font-bold text-indigo-900">Patient Verification Required</p>
                            <p class="text-xs text-indigo-700 mt-1">Verify that the patient name on ID matches the prescription.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Patient Name</p>
                                <p class="text-sm font-bold text-gray-900 uppercase flex items-center gap-2">
                                    {{ order.prescription_patient_name }}
                                    <span v-if="order.ocr_results?.prescription_id_image?.extracted_name" 
                                          class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-tighter"
                                          :class="order.ocr_results.prescription_id_image.extracted_name.toLowerCase() === order.prescription_patient_name.toLowerCase() 
                                              ? 'bg-emerald-100 text-emerald-700' 
                                              : 'bg-rose-100 text-rose-700 border border-rose-200'"
                                    >
                                        {{ order.ocr_results.prescription_id_image.extracted_name.toLowerCase() === order.prescription_patient_name.toLowerCase() ? 'OCR Match' : 'OCR Mismatch' }}
                                    </span>
                                </p>
                                <p v-if="order.ocr_results?.prescription_id_image?.extracted_name && order.ocr_results.prescription_id_image.extracted_name.toLowerCase() !== order.prescription_patient_name.toLowerCase()" 
                                   class="text-[9px] text-rose-600 font-bold mt-1 italic">
                                    OCR Extracted: "{{ order.ocr_results.prescription_id_image.extracted_name }}"
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 items-start">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Prescription Photo</p>
                                <a
                                    :href="`/storage/${order.prescription_image_path}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-block group relative w-full"
                                >
                                    <img
                                        :src="`/storage/${order.prescription_image_path}`"
                                        alt="Prescription upload"
                                        class="w-full rounded-xl border border-gray-200 shadow-sm group-hover:opacity-90 transition"
                                    />
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                        <span class="bg-black/50 text-white px-3 py-1 rounded-full text-xs font-bold">Open Full Size</span>
                                    </div>
                                </a>
                            </div>
                            <div v-if="order.prescription_id_image_path">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Patient ID Photo</p>
                                <a
                                    :href="`/storage/${order.prescription_id_image_path}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-block group relative w-full"
                                >
                                    <img
                                        :src="`/storage/${order.prescription_id_image_path}`"
                                        alt="Patient ID upload"
                                        class="w-full rounded-xl border border-gray-200 shadow-sm group-hover:opacity-90 transition"
                                    />
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                        <span class="bg-black/50 text-white px-3 py-1 rounded-full text-xs font-bold">Open Full Size</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="flex flex-col lg:flex-row gap-4 lg:items-start pt-2 border-t border-gray-100 mt-4">
                            <button
                                type="button"
                                @click="approvePrescription"
                                :disabled="rxProcessing"
                                class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-black hover:bg-blue-700 transition shadow-md disabled:opacity-50"
                            >
                                Approve Prescription
                            </button>
                            <form class="flex-1 space-y-2 max-w-lg" @submit.prevent="rejectPrescription">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Reject with reason</label>
                                <div class="flex gap-2">
                                    <textarea
                                        v-model="rejectReason"
                                        required
                                        rows="1"
                                        placeholder="e.g. Expired, wrong name..."
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                                    />
                                    <button
                                        type="submit"
                                        :disabled="rxProcessing"
                                        class="px-5 py-2 rounded-xl border-2 border-red-200 text-red-600 font-bold hover:bg-red-50 transition disabled:opacity-50 text-sm"
                                    >
                                        Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 overflow-hidden">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4">Order Items</h2>
                        
                        <div class="space-y-4">
                            <div 
                                v-for="item in order.items" 
                                :key="item.id"
                                class="flex gap-3 pb-4 border-b last:border-0"
                            >
                                <div class="w-14 h-14 sm:w-20 sm:h-20 flex-shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center">
                                    <svg class="h-6 w-6 sm:h-8 sm:w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 text-sm sm:text-base break-words">{{ item.product.name }}</h3>
                                    <p v-if="item.product_variation" class="text-xs sm:text-sm text-blue-700 font-medium mt-0.5 break-words">
                                        {{ item.product_variation.display_label || `${item.product_variation.option_name}: ${item.product_variation.option_value}` }}
                                    </p>
                                    <p class="text-xs sm:text-sm text-gray-600">{{ item.product.brand || 'Generic' }}</p>
                                    <p class="text-xs sm:text-sm text-gray-500 mt-1 break-words">
                                        Branch: {{ item.inventory?.branch?.location ?? 'Main Warehouse' }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-2 sm:gap-4 mt-2">
                                        <span class="text-xs sm:text-sm text-gray-600">Qty: {{ item.quantity }}</span>
                                        <span class="text-xs sm:text-sm text-gray-600">₱{{ Number(item.unit_price).toLocaleString() }} each</span>
                                        <span v-if="item.is_wholesale" class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-semibold">
                                            Wholesale
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 self-start">
                                    <p class="font-bold text-gray-900 text-sm sm:text-base tabular-nums">₱{{ Number(item.total_price).toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t">
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">Items Subtotal</span>
                                    <span class="font-semibold text-gray-900">₱{{ Number(orderSubtotal).toLocaleString() }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">Shipping Fee</span>
                                    <span class="font-semibold text-gray-900">₱{{ Number(orderShippingFee).toLocaleString() }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t">
                                    <span class="text-lg font-semibold text-gray-900">Total Amount</span>
                                    <span class="text-2xl font-bold text-blue-600">₱{{ Number(orderGrandTotal).toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Reviews & Disputes -->
                    <div v-if="order.product_reviews && order.product_reviews.length > 0" class="bg-white rounded-xl shadow-md p-4 sm:p-6 overflow-hidden">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900">Customer Reviews</h2>
                            <div class="flex items-center gap-1 bg-amber-50 px-3 py-1 rounded-full border border-amber-100">
                                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <span class="text-xs font-bold text-amber-700">Customer Feedback</span>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div v-for="review in order.product_reviews" :key="review.id" class="border-b last:border-0 pb-6 last:pb-0">
                                <div class="flex flex-col sm:flex-row sm:justify-between items-start gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <div class="flex">
                                                <svg v-for="n in 5" :key="n" class="w-4 h-4" :class="n <= review.stars ? 'text-amber-400' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            </div>
                                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ review.product?.name }}</span>
                                        </div>
                                        <p class="text-gray-700 text-sm italic">"{{ review.body || 'No comment provided.' }}"</p>
                                        <p class="text-[10px] text-gray-400 mt-2">Received on {{ formatDate(review.created_at) }}</p>
                                    </div>

                                    <!-- Dispute Status / Action -->
                                    <div class="w-full sm:w-auto mt-3 sm:mt-0">
                                        <div v-if="review.dispute_status === 'none'" class="flex justify-end">
                                            <button 
                                                @click="initiateDispute(review)"
                                                class="text-xs font-bold text-rose-600 px-3 py-1.5 rounded-lg border border-rose-200 hover:bg-rose-50 transition flex items-center gap-1.5"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                Dispute Review
                                            </button>
                                        </div>
                                        <div v-else class="flex flex-col items-end gap-1">
                                            <span 
                                                class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter"
                                                :class="{
                                                    'bg-amber-100 text-amber-800 border border-amber-200': review.dispute_status === 'pending',
                                                    'bg-green-100 text-green-800 border border-green-200': review.dispute_status === 'resolved',
                                                    'bg-gray-100 text-gray-800 border border-gray-200': review.dispute_status === 'rejected',
                                                }"
                                            >
                                                Dispute: {{ review.dispute_status }}
                                            </span>
                                            <p v-if="review.is_hidden" class="text-[9px] font-bold text-emerald-600 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26a4 4 0 015.486 5.486L8.146 6.783s-.012-.007-.054-.03a.5.5 0 00-.123-.054c-.053-.016-.134-.029-.23-.045zm1.512 5.097l-.027-.027L4.78 6.94A11.025 11.025 0 002.613 10c1.274 4.057 5.064 7 9.542 7 .847 0 1.666-.105 2.446-.302l-2.435-2.435a4 4 0 01-2.711-2.712z" clip-rule="evenodd"/></svg>
                                                Review hidden by admin
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="review.dispute_reason && review.dispute_status !== 'none'" class="mt-3 bg-gray-50 rounded-lg p-3 border border-gray-100 italic">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Your Dispute Reason</p>
                                    <p class="text-xs text-gray-600 leading-relaxed">{{ review.dispute_reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Info -->
                    <div v-if="order.invoice" class="bg-white rounded-xl shadow-md p-4 sm:p-6 overflow-hidden">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4">Invoice</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Invoice Number</p>
                                <p class="font-semibold text-gray-900 font-mono">{{ order.invoice.invoice_number }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Invoice Record</p>
                                <span
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': order.invoice.status === 'unpaid',
                                        'bg-orange-100 text-orange-800': order.invoice.status === 'partial',
                                        'bg-green-100 text-green-800': order.invoice.status === 'paid',
                                        'bg-red-100 text-red-800': order.invoice.status === 'overdue' || order.invoice.status === 'cancelled',
                                    }"
                                    class="inline-block px-3 py-1 rounded-full text-xs font-semibold capitalize"
                                >
                                    {{ order.invoice.status }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Payment Status</p>
                                <span
                                    :class="{
                                        'bg-amber-100 text-amber-800': paymentSettlement.state === 'pending_release' || paymentSettlement.state === 'pending_verification',
                                        'bg-green-100 text-green-800': paymentSettlement.state === 'released',
                                        'bg-red-100 text-red-800': paymentSettlement.state === 'refunded',
                                        'bg-gray-100 text-gray-800': paymentSettlement.state === 'unpaid',
                                    }"
                                    class="inline-block px-3 py-1 rounded-full text-xs font-semibold capitalize"
                                >
                                    {{ paymentSettlement.label }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Payment Method</p>
                                <span
                                    v-if="order.payment_method === 'cod'"
                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-200"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8a2 2 0 012-2z"/></svg>
                                    {{ paymentSettlement.payment_method_label }}
                                </span>
                                <p v-else class="font-semibold text-gray-900 text-sm">{{ paymentSettlement.payment_method_label }}</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Actions Sidebar: first on mobile so status isn’t buried -->
                <div class="lg:col-span-1 space-y-4 sm:space-y-6 order-1 lg:order-2 min-w-0">
                    <!-- Update Status -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 lg:sticky lg:top-4">
                        <h3 class="font-bold text-gray-900 mb-4">Update Status</h3>
                        <form @submit.prevent="updateStatus">
                            <select 
                                v-model="statusForm.status"
                                class="w-full px-4 py-3 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-4 min-h-[48px] text-base sm:text-sm touch-manipulation"
                            >
                                <option :value="order.status" disabled>{{ order.status }} (current)</option>
                                <option
                                    v-for="next in validNextStatuses"
                                    :key="next"
                                    :value="next"
                                >
                                    {{ next.charAt(0).toUpperCase() + next.slice(1) }}
                                </option>
                            </select>

                            <!-- Packaging Photo Uploads -->
                            <div v-if="statusForm.status === 'packed'" class="mb-5 space-y-4 p-4 rounded-xl border-2 border-dashed border-blue-200 bg-blue-50/50">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="text-xs font-bold text-blue-800 uppercase tracking-widest">Evidence Required</span>
                                </div>
                                <p class="text-[10px] text-blue-700 leading-tight mb-3">To protect your shop, please upload photos of the items before and after packing. These will be sent to the customer.</p>

                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Items Condition (Before Packing) <span class="text-red-500">*</span></label>
                                    <input 
                                        type="file" 
                                        accept="image/*" 
                                        required
                                        @change="e => statusForm.packaging_before = e.target.files[0]"
                                        class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Ready Package (After Packed) <span class="text-red-500">*</span></label>
                                    <input 
                                        type="file" 
                                        accept="image/*" 
                                        required
                                        @change="e => statusForm.packaging_after = e.target.files[0]"
                                        class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700"
                                    />
                                </div>
                                <div class="flex items-center gap-2 mt-2">
                                    <input 
                                        type="checkbox" 
                                        id="is_fragile"
                                        v-model="statusForm.is_fragile"
                                        class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 h-4 w-4"
                                    />
                                    <label for="is_fragile" class="text-xs font-bold text-rose-700 flex items-center gap-1.5 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Fragile Package (Handle with care)
                                    </label>
                                </div>
                            </div>

                            <button 
                                type="submit"
                                :disabled="statusForm.status === order.status || updating"
                                class="w-full bg-blue-600 text-white px-4 py-3.5 sm:py-2 rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed min-h-[48px] touch-manipulation shadow-md"
                            >
                                <span v-if="updating" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Updating...
                                </span>
                                <span v-else>Update Status</span>
                            </button>
                        </form>
                    </div>

                    <!-- Fragile Badge -->
                    <div v-if="order.is_fragile" class="bg-rose-50 border-2 border-rose-200 rounded-xl p-4 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-rose-800 uppercase tracking-widest leading-none mb-0.5">Handle with Care</p>
                            <p class="text-sm font-bold text-rose-900">Fragile Contents Flagged</p>
                        </div>
                    </div>

                    <!-- Display existing packaging photos if available -->
                    <div v-if="order.packaging_before_image_path || order.packaging_after_image_path" class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                        <h3 class="font-bold text-gray-900 mb-4">Packaging Evidence</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div v-if="order.packaging_before_image_path">
                                <p class="text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-1">Before Packing</p>
                                <a :href="`/storage/${order.packaging_before_image_path}`" target="_blank" class="block group relative">
                                    <img :src="`/storage/${order.packaging_before_image_path}`" class="w-full h-24 object-cover rounded-lg border group-hover:opacity-90 transition" />
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                        <span class="bg-black/50 text-white px-2 py-0.5 rounded-full text-[8px] font-bold">View</span>
                                    </div>
                                </a>
                            </div>
                            <div v-if="order.packaging_after_image_path">
                                <p class="text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-1">After Packed</p>
                                <a :href="`/storage/${order.packaging_after_image_path}`" target="_blank" class="block group relative">
                                    <img :src="`/storage/${order.packaging_after_image_path}`" class="w-full h-24 object-cover rounded-lg border group-hover:opacity-90 transition" />
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                        <span class="bg-black/50 text-white px-2 py-0.5 rounded-full text-[8px] font-bold">View</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Info (Only if not pickup) -->
                    <div v-if="order.delivery && order.fulfillment_method !== 'pickup'" class="bg-white rounded-xl shadow-md p-4 sm:p-6 overflow-hidden">
                        <h3 class="font-bold text-gray-900 mb-4">Delivery</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Tracking Number</p>
                                <p class="font-semibold text-gray-900">{{ order.delivery.tracking_number }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Carrier</p>
                                <p class="font-semibold text-gray-900">
                                    MedEquip Express
                                    <span v-if="order.delivery.courier?.user?.name" class="text-gray-500 font-normal">
                                        ({{ order.delivery.courier.user.name }})
                                    </span>
                                </p>
                                <p v-if="order.delivery.courier?.user?.phone_number" class="text-sm text-blue-600 font-medium mt-1 uppercase">
                                    📞 {{ order.delivery.courier.user.phone_number }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Status</p>
                                <span class="inline-block bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-xs font-semibold capitalize">
                                    {{ order.delivery.status.replace('_', ' ') }}
                                </span>
                            </div>
                            <div v-if="order.delivery.proof_of_delivery_path" class="pt-3 border-t mt-2">
                                <p class="text-sm text-gray-600 mb-2">Proof of Delivery</p>
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-2 inline-block relative">
                                    <img 
                                        :src="`/storage/${order.delivery.proof_of_delivery_path}`" 
                                        alt="Proof of Delivery Photo" 
                                        class="max-w-[200px] rounded shadow-sm"
                                    />
                                    <div v-if="order.delivery.is_location_flagged" class="absolute top-4 left-4 right-4 bg-red-100/90 text-red-700 text-[10px] font-bold px-2 py-1 rounded shadow border border-red-200 text-center backdrop-blur-sm">
                                        Location mismatch detected
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COD Remittance Panel (for delivered COD orders) -->
                    <div v-if="order.payment_method === 'cod' && order.delivery && order.delivery.cod_collected_at"
                        class="rounded-xl shadow-md p-6 mb-6 border-2"
                        :class="order.delivery.cod_remitted_at
                            ? 'bg-green-50 border-green-200'
                            : order.delivery.cod_remittance_sent_at
                                ? 'bg-blue-50 border-blue-300'
                                : 'bg-amber-50 border-amber-300'">

                        <!-- Header -->
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                :class="order.delivery.cod_remitted_at ? 'bg-green-100' : order.delivery.cod_remittance_sent_at ? 'bg-blue-100' : 'bg-amber-100'">
                                <!-- SVG icon shifts based on state -->
                                <svg v-if="order.delivery.cod_remitted_at" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <svg v-else-if="order.delivery.cod_remittance_sent_at" class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <svg v-else class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8a2 2 0 012-2z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-sm">Cash on Delivery — Remittance</h3>
                                <p class="text-xs mt-0.5"
                                    :class="order.delivery.cod_remitted_at ? 'text-green-700' : order.delivery.cod_remittance_sent_at ? 'text-blue-700' : 'text-amber-700'">
                                    {{ order.delivery.cod_remitted_at
                                        ? 'Remittance confirmed. Order complete.'
                                        : order.delivery.cod_remittance_sent_at
                                            ? 'Courier has marked cash as sent — awaiting your confirmation'
                                            : 'Courier has collected cash from customer. Awaiting remittance.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">COD Amount</span>
                                <span class="font-bold text-gray-900">₱{{ Number(order.delivery.cod_amount || order.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Cash Collected</span>
                                <span class="text-gray-700">{{ order.delivery.cod_collected_at ? new Date(order.delivery.cod_collected_at).toLocaleString('en-PH') : '—' }}</span>
                            </div>
                            <div v-if="order.delivery.cod_remittance_sent_at" class="flex justify-between text-sm">
                                <span class="text-gray-600">Remittance Sent</span>
                                <span class="text-blue-700 font-semibold">{{ new Date(order.delivery.cod_remittance_sent_at).toLocaleString('en-PH') }}</span>
                            </div>
                            <div v-if="order.delivery.cod_remitted_at" class="flex justify-between text-sm">
                                <span class="text-gray-600">Confirmed Received</span>
                                <span class="text-green-700 font-semibold">{{ new Date(order.delivery.cod_remitted_at).toLocaleString('en-PH') }}</span>
                            </div>
                        </div>

                        <!-- Courier payout notice -->
                        <div v-if="!order.delivery.cod_remitted_at" class="bg-white/70 rounded-lg p-3 mb-4 text-xs text-gray-600 border border-gray-200 flex items-start gap-2">
                            <svg class="w-3.5 h-3.5 text-gray-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>The courier's shipping fee payout is <strong>held</strong> until you confirm receiving the cash. Confirming releases their payment.</span>
                        </div>

                        <!-- Action: Confirm remittance -->
                        <button v-if="order.delivery.cod_remittance_sent_at && !order.delivery.cod_remitted_at"
                            type="button"
                            @click="confirmRemittance"
                            :disabled="confirmingRemittance"
                            class="w-full bg-green-600 text-white font-bold text-sm sm:text-base py-3.5 sm:py-3 rounded-xl hover:bg-green-700 transition disabled:opacity-50 flex items-center justify-center gap-2 text-center leading-snug min-h-[52px] touch-manipulation px-2">
                            <svg v-if="confirmingRemittance" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <svg v-else class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Confirm Cash Received — Release Courier Payout
                        </button>

                        <!-- Waiting state (courier hasn't sent yet) -->
                        <div v-else-if="!order.delivery.cod_remittance_sent_at && !order.delivery.cod_remitted_at"
                            class="w-full bg-amber-100 text-amber-800 font-semibold py-3 px-4 rounded-xl text-sm text-center border border-amber-200 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Waiting for courier to remit cash...
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl shadow-md p-4 sm:p-6 text-white">
                        <h3 class="font-bold mb-4">Quick Actions</h3>
                        <div class="space-y-2 sm:space-y-3">
                            <button type="button" @click="showWaybill = true" class="w-full bg-white/20 hover:bg-white/30 transition px-4 py-3.5 sm:py-2 rounded-lg font-medium text-left flex justify-between items-center min-h-[48px] touch-manipulation">
                                Print Waybill
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </button>
                            <Link
                                :href="orderMessaging.href"
                                class="w-full bg-white/20 hover:bg-white/30 transition px-4 py-3.5 sm:py-2 rounded-lg font-medium text-left min-h-[48px] touch-manipulation flex items-center"
                            >
                                {{ orderMessaging.label }}
                            </Link>
                            <Link 
                                href="/owner/inventory"
                                class="w-full bg-white/20 hover:bg-white/30 transition px-4 py-3.5 sm:py-2 rounded-lg font-medium text-left min-h-[48px] touch-manipulation flex items-center"
                            >
                                Check Inventory
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Waybill Modal -->
        <Teleport to="body">
            <div v-if="showWaybill" class="fixed inset-0 bg-black/75 z-[110] flex items-end sm:items-center justify-center p-0 sm:p-4">
                <div class="bg-white rounded-t-2xl sm:rounded-xl shadow-xl w-full max-w-lg max-h-[95dvh] overflow-y-auto">
                    <!-- Modal Header (screen only) -->
                    <div class="px-4 py-3 border-b flex justify-between items-center bg-gray-50 print:hidden sticky top-0 z-10">
                        <h3 class="font-bold text-gray-900">Delivery Waybill</h3>
                        <div class="flex gap-2">
                            <button @click="printWaybill" class="text-blue-600 hover:text-blue-800 font-bold px-3 py-1.5 rounded-lg bg-blue-50 text-sm flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print
                            </button>
                            <button @click="showWaybill = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Waybill Content -->
                    <div id="waybill-content" class="p-6 bg-white">

                        <!-- Header -->
                        <div class="text-center pb-4 border-b-2 border-black mb-5">
                            <h1 class="text-2xl font-black tracking-tight text-black uppercase">MedEquip</h1>
                            <p class="text-xs text-gray-500 mt-0.5">medequip.shop</p>
                        </div>

                        <!-- Order + Invoice + Payment row -->
                        <div class="grid grid-cols-3 gap-3 mb-5 text-center border border-black rounded-lg overflow-hidden">
                            <div class="px-3 py-2.5 border-r border-black">
                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">Order</p>
                                <p class="font-mono font-bold text-black text-xs">{{ order.order_number }}</p>
                            </div>
                            <div class="px-3 py-2.5 border-r border-black">
                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">Invoice</p>
                                <p class="font-mono font-bold text-black text-xs">{{ order.invoice?.invoice_number ?? '—' }}</p>
                            </div>
                            <div class="px-3 py-2.5">
                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">Payment</p>
                                <p class="font-bold text-black text-xs">{{ paymentSettlement.payment_method_label }}</p>
                            </div>
                        </div>

                        <!-- COD Banner -->
                        <div v-if="order.payment_method === 'cod'" class="border-2 border-black rounded-lg text-center py-2 mb-5">
                            <p class="text-sm font-black uppercase tracking-widest text-black">CASH ON DELIVERY</p>
                            <p class="text-xs text-gray-600 mt-0.5">Collect ₱{{ Number(orderGrandTotal).toLocaleString('en-PH', {minimumFractionDigits:2}) }} upon delivery</p>
                        </div>

                        <!-- QR Code -->
                        <div class="flex justify-center mb-5">
                            <div class="p-2 border-4 border-black rounded-lg inline-block">
                                <qrcode-vue :value="order.order_number" :size="waybillQrSize" level="H" />
                            </div>
                        </div>

                        <!-- Fragile Flag (Waybill) -->
                        <div v-if="order.is_fragile" class="border-2 border-rose-600 rounded-lg text-center py-2 mb-5 bg-rose-50">
                            <p class="text-sm font-black uppercase tracking-[.25em] text-rose-600">⚠ FRAGILE ⚠</p>
                            <p class="text-[10px] text-rose-500 font-bold uppercase mt-0.5">Handle with extreme care</p>
                        </div>

                        <!-- Sender / Recipient -->
                        <div class="grid grid-cols-2 gap-0 border border-black rounded-lg overflow-hidden mb-5">
                            <div class="p-3 border-r border-black">
                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">From</p>
                                <p class="font-bold text-black text-sm leading-snug">{{ order.distributor?.company_name }}</p>
                                <p v-if="order.distributor?.address" class="text-xs text-gray-700 mt-1 leading-snug">{{ order.distributor.address }}</p>
                                <p v-if="order.distributor?.contact_number" class="text-xs text-gray-600 mt-0.5">{{ order.distributor.contact_number }}</p>
                            </div>
                            <div class="p-3">
                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">To</p>
                                <p class="font-bold text-black text-sm leading-snug">{{ order.customer.name }}</p>
                                <p class="text-xs text-gray-700 mt-1 leading-snug">{{ order.delivery_address }}</p>
                                <p class="text-xs text-gray-600 mt-0.5">{{ order.contact_number }}</p>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="order.notes" class="border border-gray-300 rounded-lg p-3 mb-5">
                            <p class="text-[9px] font-bold uppercase tracking-wider text-gray-500 mb-1">Notes</p>
                            <p class="text-xs text-gray-800 italic">"{{ order.notes }}"</p>
                        </div>

                        <!-- Total (only for non-COD — COD has its own banner) -->
                        <div v-if="order.payment_method !== 'cod'" class="border-t-2 border-black pt-3 flex justify-between items-center mb-5">
                            <span class="text-sm font-bold text-black uppercase tracking-wide">Total Amount</span>
                            <span class="text-base font-black text-black">₱{{ Number(orderGrandTotal).toLocaleString('en-PH', {minimumFractionDigits:2}) }}</span>
                        </div>

                        <!-- Footer -->
                        <div class="border-t border-dashed border-gray-300 pt-3 text-center">
                            <p class="text-[9px] text-gray-400">{{ new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                        </div>

                    </div><!-- end waybill-content -->

                </div>
            </div>
        </Teleport>

    </OwnerLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    order: Object,
    paymentSettlement: {
        type: Object,
        default: () => ({ state: 'unpaid', label: 'Unpaid' }),
    },
    orderMessaging: {
        type: Object,
        required: true,
    },
});

const page = usePage();

// Valid status transitions (mirrors server-side state machine)
const transitionMap = computed(() => {
    const isPickup = props.order.fulfillment_method === 'pickup';
    
    return {
        pending:   ['approved', 'rejected', 'cancelled'],
        approved:  [isPickup ? 'ready_for_pickup' : 'packed', 'cancelled'],
        packed:    [],
        ready_for_pickup: [],
        shipped:   [],
        delivered: [],
        cancelled: [],
        rejected:  [],
    };
});

const validNextStatuses = computed(() => transitionMap.value[props.order.status] ?? []);

const statusForm = reactive({
    status: validNextStatuses.value[0] ?? props.order.status,
    is_fragile: props.order.is_fragile || false,
});

const updating = ref(false);
const showWaybill = ref(false);
const rxProcessing = ref(false);
const rejectReason = ref('');
const discountProcessing = ref(false);
const discountRejectReason = ref('');
const confirmingRemittance = ref(false);

const waybillQrSize = ref(200);
const syncWaybillQrSize = () => {
    waybillQrSize.value = window.matchMedia('(min-width: 640px)').matches ? 200 : 168;
};

const confirmRemittance = () => {
    if (!confirm('Confirm that you have received the cash from the courier?\n\nThis will release the courier\'s shipping fee payout and mark the order as complete.')) return;
    confirmingRemittance.value = true;
    router.post(`/owner/orders/${props.order.id}/confirm-cod-remittance`, {}, {
        preserveScroll: true,
        onFinish: () => { confirmingRemittance.value = false; },
    });
};

const approvePrescription = () => {
    if (!confirm('Approve this prescription and allow the customer to pay for this order?')) return;
    rxProcessing.value = true;
    router.post(`/owner/orders/${props.order.id}/prescription/approve`, {}, {
        preserveScroll: true,
        onFinish: () => { rxProcessing.value = false; },
    });
};

const rejectPrescription = () => {
    if (!confirm('Reject this prescription? The order will be cancelled and stock reservations released.')) return;
    rxProcessing.value = true;
    router.post(`/owner/orders/${props.order.id}/prescription/reject`, { reason: rejectReason.value }, {
        preserveScroll: true,
        onFinish: () => { rxProcessing.value = false; },
    });
};

const approveDiscount = () => {
    if (!confirm('Approve this SC/PWD discount request?')) return;
    discountProcessing.value = true;
    router.post(`/owner/orders/${props.order.id}/discount/approve`, {}, {
        preserveScroll: true,
        onFinish: () => { discountProcessing.value = false; },
    });
};

const rejectDiscount = () => {
    if (!confirm('Reject this discount? This will cancel the order as per system requirements.')) return;
    discountProcessing.value = true;
    router.post(`/owner/orders/${props.order.id}/discount/reject`, { reason: discountRejectReason.value }, {
        preserveScroll: true,
        onFinish: () => { discountProcessing.value = false; },
    });
};
const orderSubtotal = computed(() => Number(props.order?.subtotal || 0));
const orderShippingFee = computed(() => Number(props.order?.shipping_fee || 0));
const orderGrandTotal = computed(() => {
    const total = Number(props.order?.total_amount || 0);
    if (total > 0) return total;
    return orderSubtotal.value + orderShippingFee.value;
});

const initiateDispute = (review) => {
    const reason = prompt('Why are you disputing this review? (e.g., Unfair rating despite perfect condition shown in packaging photos, malicious intent, etc.)');
    if (!reason || reason.trim() === '') return;
    
    router.post(`/owner/reviews/${review.id}/dispute`, { reason }, {
        preserveScroll: true,
        onSuccess: () => {
            alert('Your dispute has been submitted and will be reviewed by our admin team.');
        }
    });
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const printWaybill = () => {
    window.print();
};

onMounted(() => {
    syncWaybillQrSize();
    window.addEventListener('resize', syncWaybillQrSize);

    // Check for errors from previous request
    if (page.props.errors && Object.keys(page.props.errors).length > 0) {
        console.error('[OwnerOrderShow] Errors detected on page load', page.props.errors);
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', syncWaybillQrSize);
});

const updateStatus = () => {
    const newStatus = statusForm.status;

    // Confirm destructive actions
    if (['rejected', 'cancelled'].includes(newStatus)) {
        if (!confirm(`Are you sure you want to ${newStatus} this order? This will release reserved inventory.`)) {
            return;
        }
    }

    if (newStatus === 'packed' && (!statusForm.packaging_before || !statusForm.packaging_after)) {
        alert('Please upload both "Before Packing" and "After Packed" photos to protect your shop.');
        return;
    }

    updating.value = true;
    
    router.post(`/owner/orders/${props.order.id}/status`, {
        _method: 'patch',
        ...statusForm
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Reset files
            delete statusForm.packaging_before;
            delete statusForm.packaging_after;
        },
        onError: (errors) => {
            console.error('[OwnerOrderShow] Status update failed', errors);
        },
        onFinish: () => {
            updating.value = false;
        }
    });
};
</script>
