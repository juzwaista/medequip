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
                                    'bg-indigo-100 text-indigo-800': order.status === 'shipped',
                                    'bg-green-100 text-green-800': order.status === 'delivered',
                                    'bg-red-100 text-red-800': order.status === 'rejected' || order.status === 'cancelled',
                                }"
                                class="px-4 py-2 rounded-full text-sm font-semibold capitalize self-start shrink-0"
                            >
                                {{ order.status }}
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
                            </div>
                            <div class="sm:col-span-2 min-w-0">
                                <p class="text-sm text-gray-600">Delivery Address</p>
                                <p class="font-semibold text-gray-900 break-words">{{ order.delivery_address }}</p>
                            </div>
                            <div v-if="order.notes" class="sm:col-span-2 min-w-0">
                                <p class="text-sm text-gray-600">Customer Notes</p>
                                <p class="text-sm text-gray-700">{{ order.notes }}</p>
                            </div>
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
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Prescription review</h2>
                        <p class="text-sm text-gray-600 mb-4">
                            Approve if the prescription is valid. Rejecting cancels the order and releases reserved stock.
                        </p>
                        <a
                            :href="`/storage/${order.prescription_image_path}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-block mb-4"
                        >
                            <img
                                :src="`/storage/${order.prescription_image_path}`"
                                alt="Prescription upload"
                                class="max-w-full sm:max-w-md rounded-xl border border-gray-200 shadow-sm"
                            />
                            <span class="text-sm text-blue-600 font-medium mt-2 block">Open full size</span>
                        </a>
                        <div class="flex flex-col lg:flex-row gap-4 lg:items-start">
                            <button
                                type="button"
                                @click="approvePrescription"
                                :disabled="rxProcessing"
                                class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 disabled:opacity-50"
                            >
                                Approve prescription
                            </button>
                            <form class="flex-1 space-y-2 max-w-lg" @submit.prevent="rejectPrescription">
                                <label class="block text-sm font-semibold text-gray-700">Reject with reason</label>
                                <textarea
                                    v-model="rejectReason"
                                    required
                                    rows="2"
                                    placeholder="e.g. Illegible, expired, wrong patient name…"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                />
                                <button
                                    type="submit"
                                    :disabled="rxProcessing"
                                    class="px-5 py-2.5 rounded-xl border-2 border-red-300 text-red-700 font-semibold hover:bg-red-50 disabled:opacity-50"
                                >
                                    Reject prescription
                                </button>
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
                                class="w-full px-4 py-3 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-3 min-h-[48px] text-base sm:text-sm touch-manipulation"
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
                            <button 
                                type="submit"
                                :disabled="statusForm.status === order.status || updating"
                                class="w-full bg-blue-600 text-white px-4 py-3.5 sm:py-2 rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed min-h-[48px] touch-manipulation"
                            >
                                {{ updating ? 'Updating...' : 'Update Status' }}
                            </button>
                        </form>
                    </div>

                    <!-- Delivery Info -->
                    <div v-if="order.delivery" class="bg-white rounded-xl shadow-md p-4 sm:p-6 overflow-hidden">
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
const transitionMap = {
    pending:   ['approved', 'rejected', 'cancelled'],
    approved:  ['packed', 'cancelled'],
    packed:    [],
    shipped:   [],
    delivered: [],
    cancelled: [],
    rejected:  [],
};

const validNextStatuses = computed(() => transitionMap[props.order.status] ?? []);

const statusForm = reactive({
    status: validNextStatuses.value[0] ?? props.order.status,
});

const updating = ref(false);
const showWaybill = ref(false);
const rxProcessing = ref(false);
const rejectReason = ref('');
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
const orderSubtotal = computed(() => Number(props.order?.subtotal || 0));
const orderShippingFee = computed(() => Number(props.order?.shipping_fee || 0));
const orderGrandTotal = computed(() => {
    const total = Number(props.order?.total_amount || 0);
    if (total > 0) return total;
    return orderSubtotal.value + orderShippingFee.value;
});

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
    const oldStatus = statusForm.status;
    const newStatus = statusForm.status;

    // Confirm destructive actions
    if (['rejected', 'cancelled'].includes(newStatus)) {
        if (!confirm(`Are you sure you want to ${newStatus} this order? This will release reserved inventory.`)) {
            return;
        }
    }

    updating.value = true;
    
    router.patch(`/owner/orders/${props.order.id}/status`, statusForm, {
        preserveScroll: true,
        onSuccess: () => {
        },
        onError: (errors) => {
            console.error('[OwnerOrderShow] Status update failed', {
                order_id: props.order.id,
                errors: errors
            });
        },
        onFinish: () => {
            updating.value = false;
        }
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
