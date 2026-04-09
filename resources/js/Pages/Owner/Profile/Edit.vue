<template>
    <OwnerLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Business Profile</h2>
        </template>

        <div class="py-6">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        <form @submit.prevent="submit" class="space-y-8">

                            <!-- Basic Information -->
                            <section class="border-b pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Company Name</label>
                                        <input v-model="form.company_name" type="text" disabled class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-600 shadow-sm cursor-not-allowed" />
                                        <p class="text-xs text-gray-500 mt-1">Verified against business permits. Contact support to change.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Profile URL</label>
                                        <div class="flex mt-1">
                                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">/seller/</span>
                                            <input v-model="form.slug" @input="checkSlug" type="text" required class="block w-full rounded-none rounded-r-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                        </div>
                                        <p v-if="slugStatus" :class="slugStatus.available ? 'text-green-600' : 'text-red-600'" class="text-xs mt-1">{{ slugStatus.message }}</p>
                                        <p v-if="form.errors.slug" class="text-red-500 text-xs mt-1">{{ form.errors.slug }}</p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea v-model="form.description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Tell customers about your business..." />
                                    <p v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</p>
                                </div>
                            </section>

                            <!-- Contact Information -->
                            <section class="border-b pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                                        <input v-model="form.phone" @input="sanitizePhoneNumber" type="tel" inputmode="numeric" pattern="09[0-9]{9}" maxlength="11" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                        <p v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="shop@example.com" />
                                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Website</label>
                                        <input v-model="form.website" type="url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Maximum COD Amount (₱)</label>
                                        <input v-model="form.max_cod_amount" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="0.00 (Leave empty for no limit)" />
                                        <p class="text-xs text-gray-500 mt-1">Orders exceeding this amount will not have COD as a payment option.</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                            </svg>
                                            Pick-up Instructions
                                        </label>
                                        <p class="text-xs text-gray-500 mb-2 italic">Provide clear details for customers who choose the "Store Pick-up" option (e.g., precise directions, look for a specific person/stall, or items required like an ID/receipt).</p>
                                        <textarea 
                                            v-model="form.pickup_instructions" 
                                            rows="4" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm" 
                                            placeholder="Example: We are located on the 2nd floor, beside the staircase. Please present your Order ID to the cashier." 
                                        />
                                        <p v-if="form.errors.pickup_instructions" class="text-red-500 text-xs mt-1">{{ form.errors.pickup_instructions }}</p>
                                    </div>
                                </div>
                            </section>

                            <!-- Business Address -->
                            <section class="border-b pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-1">Business Address</h3>
                                <p class="text-sm text-gray-500 mb-4">Shown publicly on your shop profile. Used for delivery routing.</p>

                                <div class="space-y-4">
                                    <!-- Street -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Street / Building / Unit</label>
                                        <input v-model="streetAddress" type="text" placeholder="e.g. Blk 5 Lot 10 Sampaguita St., Golden Meadows" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                    </div>

                                    <!-- City + Barangay -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">City / Municipality</label>
                                            <select v-model="selectedCity" @change="onCityChange" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                <option value="">Select city</option>
                                                <option v-for="(data, city) in cities" :key="city" :value="city">{{ city }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Barangay</label>
                                            <select v-if="availableBarangays.length > 0" v-model="selectedBarangay" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                <option value="">Select barangay</option>
                                                <option v-for="brgy in availableBarangays" :key="brgy" :value="brgy">{{ brgy }}</option>
                                                <option value="other">Other (type manually)</option>
                                            </select>
                                            <input v-else v-model="manualBarangay" type="text" placeholder="Enter barangay name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                        </div>
                                    </div>

                                    <!-- Manual barangay input if 'other' selected -->
                                    <div v-if="selectedBarangay === 'other' && availableBarangays.length > 0">
                                        <label class="block text-sm font-medium text-gray-700">Barangay Name</label>
                                        <input v-model="manualBarangay" type="text" placeholder="Type your barangay name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                    </div>

                                    <!-- Zip code (auto-filled) -->
                                    <div class="max-w-[180px]">
                                        <label class="block text-sm font-medium text-gray-700">Zip Code</label>
                                        <input v-model="zipCode" type="text" readonly placeholder="Auto-filled" class="mt-1 block w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 sm:text-sm" />
                                    </div>

                                    <!-- Composed full address preview -->
                                    <div v-if="form.address" class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-xs text-slate-600">
                                        <span class="font-semibold text-slate-500 uppercase tracking-wider mr-1">Full address:</span>{{ form.address }}
                                    </div>
                                    <p v-if="form.errors.address" class="text-red-500 text-xs">{{ form.errors.address }}</p>

                                    <!-- Map pin -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                            Pin Exact Location
                                            <span class="text-xs font-normal text-gray-500 italic">(Drop a pin to auto-fill City &amp; Barangay)</span>
                                        </label>
                                        <MapPicker 
                                            v-model:lat="form.latitude" 
                                            v-model:lng="form.longitude"
                                            :geocodeQuery="geocodeQuery"
                                            height="250px"
                                            @update:address="onMapAddressPicked"
                                        />
                                        <transition
                                            enter-active-class="transition ease-out duration-200"
                                            enter-from-class="opacity-0 -translate-y-1"
                                            enter-to-class="opacity-100 translate-y-0"
                                            leave-active-class="transition ease-in duration-150"
                                            leave-from-class="opacity-100 translate-y-0"
                                            leave-to-class="opacity-0 -translate-y-1"
                                        >
                                            <p v-if="detectedLocation" class="mt-2 text-xs text-blue-800 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 flex items-center gap-1.5">
                                                <svg class="h-3.5 w-3.5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                                {{ detectedLocation }}
                                            </p>
                                        </transition>
                                        <p v-if="form.errors.latitude" class="text-red-500 text-xs mt-1">{{ form.errors.latitude }}</p>
                                    </div>
                                </div>
                            </section>

                            <!-- Documents & Compliance -->
                            <section class="border-b pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Documents & Compliance</h3>
                                <p class="text-sm text-gray-500 mb-4">Manage your business documents and track expiration dates for compliance.</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="doc in distributorDocuments" :key="doc.key" 
                                        class="rounded-xl border p-4 bg-gray-50 flex flex-col justify-between"
                                        :class="{'border-amber-200 bg-amber-50': isNearExpiry(doc.expiry)}"
                                    >
                                        <div class="flex items-start justify-between mb-3">
                                            <div>
                                                <h4 class="text-sm font-semibold text-gray-800">{{ doc.label }}</h4>
                                                <p v-if="doc.expiry" class="text-xs mt-0.5" :class="isNearExpiry(doc.expiry) ? 'text-amber-700 font-bold' : 'text-gray-500'">
                                                    Expires: {{ formatDate(doc.expiry) }}
                                                </p>
                                                <p v-else class="text-xs text-red-500 mt-0.5 italic">No expiration date set</p>
                                            </div>
                                            <div v-if="isNearExpiry(doc.expiry)" class="text-amber-500">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>

                                        <div class="space-y-3">
                                            <div v-if="scanningFields[doc.key]" class="text-[10px] text-blue-600 font-bold italic animate-pulse flex items-center gap-1">
                                                <svg class="animate-spin h-3 w-3" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Scanning new document...
                                            </div>
                                            
                                            <div class="flex items-center gap-2">
                                                <label :for="`update-${doc.key}`" class="flex-1 text-center bg-white border border-gray-300 text-gray-700 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-gray-50 transition cursor-pointer">
                                                    Update Document
                                                </label>
                                                <input :id="`update-${doc.key}`" type="file" class="sr-only" @change="e => onUpdateDoc(doc.key, e.target.files[0])" accept="image/*" />
                                                
                                                <a :href="`/storage/${doc.path}`" target="_blank" class="p-1.5 text-gray-400 hover:text-blue-600 transition" title="View Current">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </a>
                                            </div>
                                            
                                            <div v-if="form[doc.updateKey]" class="animate-in fade-in slide-in-from-top-1">
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">New Expiry (from OCR)</label>
                                                <input type="date" v-model="form[doc.expiryKey]" class="w-full px-2 py-1 border border-blue-200 rounded text-xs bg-blue-50 focus:ring-1 focus:ring-blue-500" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Social Links -->
                            <section class="border-b pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Social Media</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Facebook</label>
                                        <input v-model="form.social_links.facebook" type="url" placeholder="https://facebook.com/..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Instagram</label>
                                        <input v-model="form.social_links.instagram" type="url" placeholder="https://instagram.com/..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">TikTok</label>
                                        <input v-model="form.social_links.tiktok" type="url" placeholder="https://tiktok.com/@..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                                    </div>
                                </div>
                            </section>

                            <!-- Business Hours -->
                            <section class="border-b pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Business Hours</h3>
                                <p class="text-sm text-gray-500 mb-4">Set your regular operating hours. These are shown on your public shop profile.</p>
                                <div class="space-y-3">
                                    <div v-for="(entry, idx) in form.business_hours" :key="idx" class="flex items-center gap-3">
                                        <span class="w-24 text-sm font-medium text-gray-700">{{ entry.day }}</span>
                                        <label class="flex items-center gap-1.5 text-sm text-gray-500 cursor-pointer">
                                            <input type="checkbox" v-model="entry.closed" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                            Closed
                                        </label>
                                        <template v-if="!entry.closed">
                                            <input v-model="entry.open" type="time" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                                            <span class="text-gray-400">to</span>
                                            <input v-model="entry.close" type="time" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                                        </template>
                                        <span v-else class="text-sm text-gray-400 italic">Closed</span>
                                    </div>
                                </div>
                            </section>

                            <!-- Images -->
                            <section class="border-b pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Profile Images</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                                        <div class="flex items-center space-x-4">
                                            <div class="h-24 w-24 rounded-full overflow-hidden bg-gray-100 border-2 border-gray-200">
                                                <img v-if="logoPreview" :src="logoPreview" class="h-full w-full object-cover" />
                                                <img v-else-if="distributor.logo_path" :src="`/storage/${distributor.logo_path}`" class="h-full w-full object-cover" />
                                                <div v-else class="h-full w-full flex items-center justify-center text-gray-400">
                                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                </div>
                                            </div>
                                            <input type="file" @change="handleLogoUpload" accept="image/*" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Cover Photo</label>
                                        <div class="w-full h-32 rounded-lg overflow-hidden bg-gray-100 border-2 border-gray-200 mb-2">
                                            <img v-if="coverPreview" :src="coverPreview" class="h-full w-full object-cover" />
                                            <img v-else-if="distributor.cover_photo_path" :src="`/storage/${distributor.cover_photo_path}`" class="h-full w-full object-cover" />
                                            <div v-else class="h-full w-full flex items-center justify-center text-gray-400"><span class="text-sm">No cover photo</span></div>
                                        </div>
                                        <input type="file" @change="handleCoverUpload" accept="image/*" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                    </div>
                                </div>
                            </section>

                            <!-- Featured Products -->
                            <section class="border-b pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Featured Products</h3>
                                <p class="text-sm text-gray-500 mb-4">Pick up to 8 products to highlight in the "Recommended" section of your shop page. If none are selected, we'll show your highest-rated products. Only <strong>active</strong> items appear on your public shop.</p>
                                <div v-if="products.length" class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto border rounded-lg p-3">
                                    <label
                                        v-for="p in products"
                                        :key="p.id"
                                        :class="[
                                            'flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer transition text-sm',
                                            form.featured_product_ids.includes(p.id) ? 'bg-blue-50 border border-blue-200' : 'hover:bg-gray-50 border border-transparent'
                                        ]"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="p.id"
                                            v-model="form.featured_product_ids"
                                            :disabled="!form.featured_product_ids.includes(p.id) && form.featured_product_ids.length >= 8"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="truncate flex-1 min-w-0">{{ p.name }}</span>
                                        <span v-if="p.is_active === false" class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded">Inactive</span>
                                    </label>
                                </div>
                                <p v-else class="text-sm text-gray-400 italic">No products in your catalog yet. Add products under Inventory first.</p>
                                <p class="text-xs text-gray-500 mt-2">{{ form.featured_product_ids.length }}/8 selected</p>
                            </section>

                            <!-- Automated Messages -->
                            <section class="border-b pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Automated Messages</h3>

                                <!-- Shop greeting -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700">Shop greeting</label>
                                    <p class="text-xs text-gray-500 mb-2">Sent automatically when a customer starts a new conversation with your shop.</p>
                                    <textarea
                                        v-model="form.chat_auto_reply"
                                        rows="3"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                        placeholder="Custom greeting (optional)"
                                    />
                                    <p v-if="form.errors.chat_auto_reply" class="text-red-500 text-xs mt-1">{{ form.errors.chat_auto_reply }}</p>
                                    <div class="mt-2 rounded-md border border-gray-200 bg-gray-50 p-3">
                                        <p class="text-xs font-medium text-gray-700 mb-1">If you leave this empty, we use:</p>
                                        <p class="text-xs text-gray-600 whitespace-pre-wrap break-words leading-relaxed">{{ previewShopGreeting }}</p>
                                        <p class="text-xs text-gray-500 mt-2">You can use <code class="bg-white px-1 rounded border">{shop_name}</code> and <code class="bg-white px-1 rounded border">{customer_name}</code> in your message.</p>
                                    </div>
                                </div>

                                <!-- Order chat templates -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 mb-1">Order chat templates</h4>
                                    <p class="text-xs text-gray-500 mb-3">
                                        Posted into the order thread when you accept or ship an order.
                                        Placeholders: <code class="bg-gray-100 px-1 rounded">{shop_name}</code>,
                                        <code class="bg-gray-100 px-1 rounded">{order_number}</code>,
                                        <code class="bg-gray-100 px-1 rounded">{customer_name}</code>
                                    </p>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm text-gray-600">After order is accepted</label>
                                            <textarea v-model="form.chat_template_order_accepted" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Custom message (optional)" />
                                            <div class="mt-2 rounded-md border border-gray-200 bg-gray-50 p-3">
                                                <p class="text-xs font-medium text-gray-700 mb-1">If empty, default (example):</p>
                                                <p class="text-xs text-gray-600 whitespace-pre-wrap break-words leading-relaxed">{{ previewOrderAccepted }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-600">When order is shipped</label>
                                            <textarea v-model="form.chat_template_order_shipped" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Custom message (optional)" />
                                            <div class="mt-2 rounded-md border border-gray-200 bg-gray-50 p-3">
                                                <p class="text-xs font-medium text-gray-700 mb-1">If empty, default (example):</p>
                                                <p class="text-xs text-gray-600 whitespace-pre-wrap break-words leading-relaxed">{{ previewOrderShipped }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Actions -->
                            <div class="flex justify-end gap-4">
                                <Link
                                    :href="`/seller/${form.slug || distributor.slug}`"
                                    target="_blank"
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm"
                                >
                                    View Public Profile
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 text-sm font-medium"
                                >
                                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import MapPicker from '@/Components/MapPicker.vue';
import axios from 'axios';
import { debounce } from 'lodash';
import { useOCR } from '@/Composables/useOCR';

const { scanImage, extractExpirationDate } = useOCR();
const scanningFields = ref({});

const props = defineProps({
    distributor: Object,
    products: { type: Array, default: () => [] },
    defaultTemplates: { type: Object, default: () => ({}) },
    cities: Object,
    barangays: Object,
});

const distributorDocuments = computed(() => [
    { key: 'valid_id', label: 'Primary Government ID', path: props.distributor.valid_id_path, expiry: props.distributor.valid_id_expires_at, updateKey: 'valid_id_new', expiryKey: 'valid_id_expires_at' },
    { key: 'business_license', label: 'Business Permit', path: props.distributor.business_license_path, expiry: props.distributor.business_license_expires_at, updateKey: 'business_license_new', expiryKey: 'business_license_expires_at' },
    { key: 'dti_sec', label: 'DTI Certificate / SEC', path: props.distributor.dti_sec_path, expiry: props.distributor.dti_sec_expires_at, updateKey: 'dti_sec_new', expiryKey: 'dti_sec_expires_at' },
    { key: 'bir_form', label: 'BIR Form 2303', path: props.distributor.bir_form_path, expiry: props.distributor.bir_form_expires_at, updateKey: 'bir_form_new', expiryKey: 'bir_form_expires_at' },
    { key: 'fda_license', label: 'FDA License to Operate', path: props.distributor.fda_license_path, expiry: props.distributor.fda_license_expires_at, updateKey: 'fda_license_new', expiryKey: 'fda_license_expires_at' },
    { key: 'prc_id', label: 'PRC ID', path: props.distributor.prc_id_path, expiry: props.distributor.prc_id_expires_at, updateKey: 'prc_id_new', expiryKey: 'prc_id_expires_at' },
]);

const onUpdateDoc = async (key, file) => {
    if (!file) return;
    const doc = distributorDocuments.value.find(d => d.key === key);
    form[doc.updateKey] = file;

    if (file.type.match('image.*')) {
        scanningFields.value[key] = true;
        try {
            const text = await scanImage(file);
            const date = extractExpirationDate(text);
            if (date) {
                form[doc.expiryKey] = date;
            }
        } catch (error) {
            console.error('OCR failed', error);
        } finally {
            scanningFields.value[key] = false;
        }
    }
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
};

const isNearExpiry = (date) => {
    if (!date) return false;
    const expiry = new Date(date);
    const now = new Date();
    const diff = expiry.getTime() - now.getTime();
    return diff < (30 * 24 * 60 * 60 * 1000); // 30 days
};

// ─── Fuzzy match helpers (moved to top to avoid ReferenceError on init) ──────
const normalize = (str) =>
    String(str)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s]/g, '')
        .replace(/\s+/g, ' ')
        .trim();

const fuzzyMatch = (input, list) => {
    if (!input || !list || !list.length) return null;
    const nInput = normalize(input);
    if (!nInput) return null;
    const exact = list.find(it => normalize(it) === nInput);
    if (exact) return exact;
    const startsWith = list.find(it => normalize(it).startsWith(nInput + ' ') || normalize(it) === nInput);
    if (startsWith) return startsWith;
    const superStr = list.find(it => nInput.includes(normalize(it)));
    if (superStr) return superStr;
    const subStr = list.find(it => normalize(it).includes(nInput));
    if (subStr) return subStr;
    return null;
};

function initBusinessHours() {
    const saved = props.distributor.business_hours;
    if (Array.isArray(saved) && saved.length === 7) {
        return saved.map((e, i) => ({
            day: DAYS[i],
            open: e.open || '08:00',
            close: e.close || '17:00',
            closed: !!e.closed,
        }));
    }
    return DAYS.map(d => ({ day: d, open: '08:00', close: '17:00', closed: false }));
}


const logoPreview = ref(null);
const coverPreview = ref(null);
const slugStatus = ref(null);

// Structured address fields
const selectedCity = ref('');
const selectedBarangay = ref('');
const manualBarangay = ref('');
const zipCode = ref('');
const streetAddress = ref('');

// For map <-> form sync
const geocodeQuery = ref(null);
const detectedLocation = ref('');
let detectedTimer = null;
let isProgrammaticChange = false;

// Attempt to pre-fill structured fields from existing flat address if possible
const savedAddr = props.distributor.address || '';
if (savedAddr) {
    const parts = savedAddr.split(',').map(p => p.trim());
    // Cavite is usually the last part, City the 2nd to last, Barangay the 3rd to last
    // format: Street, Brgy. Name, City Name, Cavite
    if (parts.length >= 3) {
        const cityPart = parts[parts.length - 2];
        const brgyPart = parts[parts.length - 3]?.replace(/^Brgy\.\s+/i, '');
        
        const cityKeys = Object.keys(props.cities || {});
        const mCity = fuzzyMatch(cityPart, cityKeys);
        if (mCity) {
            selectedCity.value = mCity;
            zipCode.value = props.cities[mCity]?.zip || '';
            
            const brgys = props.barangays?.[mCity] || [];
            const mBrgy = fuzzyMatch(brgyPart, brgys);
            if (mBrgy) {
                selectedBarangay.value = mBrgy;
            } else if (brgyPart) {
                selectedBarangay.value = 'other';
                manualBarangay.value = brgyPart;
            }
        }
        // Everything before barangay is streetAddress
        streetAddress.value = parts.slice(0, parts.length - 3).join(', ');
    } else {
        streetAddress.value = savedAddr;
    }
}

const form = useForm({
    company_name: props.distributor.company_name,
    slug: props.distributor.slug,
    description: props.distributor.description || '',
    phone: props.distributor.phone || '',
    email: props.distributor.email || '',
    website: props.distributor.website || '',
    address: props.distributor.address || '',
    city: props.distributor.city || '',
    province: props.distributor.province || 'Cavite',
    barangay: props.distributor.barangay || '',
    zip_code: props.distributor.zip_code || '',
    social_links: {
        facebook: props.distributor.social_links?.facebook || '',
        instagram: props.distributor.social_links?.instagram || '',
        tiktok: props.distributor.social_links?.tiktok || '',
    },
    latitude: props.distributor.latitude || null,
    longitude: props.distributor.longitude || null,
    business_hours: initBusinessHours(),
    chat_template_order_accepted: props.distributor.chat_template_order_accepted || '',
    chat_template_order_shipped: props.distributor.chat_template_order_shipped || '',
    chat_auto_reply: props.distributor.chat_auto_reply || '',
    featured_product_ids: props.products.filter(p => p.is_featured).map(p => p.id),
    max_cod_amount: props.distributor.max_cod_amount,
    pickup_instructions: props.distributor.pickup_instructions || '',
    logo: null,
    cover_photo: null,
    // Expiration Dates
    valid_id_expires_at: props.distributor.valid_id_expires_at || '',
    business_license_expires_at: props.distributor.business_license_expires_at || '',
    dti_sec_expires_at: props.distributor.dti_sec_expires_at || '',
    bir_form_expires_at: props.distributor.bir_form_expires_at || '',
    fda_license_expires_at: props.distributor.fda_license_expires_at || '',
    prc_id_expires_at: props.distributor.prc_id_expires_at || '',
    // Update files
    valid_id_new: null,
    business_license_new: null,
    dti_sec_new: null,
    bir_form_new: null,
    fda_license_new: null,
    prc_id_new: null,
    _method: 'PUT',
});



// ─── Internal city-change helper ─────────────────────────────────────────────
const _applyCityChange = (city) => {
    selectedBarangay.value = '';
    manualBarangay.value = '';
    if (city && props.cities[city]) {
        zipCode.value = props.cities[city].zip;
    } else {
        zipCode.value = '';
    }
};

const onCityChange = () => {
    _applyCityChange(selectedCity.value);
    if (!isProgrammaticChange && selectedCity.value) {
        geocodeQuery.value = `${selectedCity.value}, Cavite, Philippines`;
    }
};

// ─── Sync structured fields into form.address ───────────────────────────────

watch([streetAddress, selectedCity, selectedBarangay, manualBarangay], () => {
    const b = selectedBarangay.value === 'other' ? manualBarangay.value : selectedBarangay.value;
    const parts = [];
    if (streetAddress.value) parts.push(streetAddress.value);
    if (b) parts.push(`Brgy. ${b}`);
    if (selectedCity.value) parts.push(selectedCity.value);
    parts.push('Cavite');
    form.address = parts.join(', ');
    
    // Also sync to new structured fields
    form.city = selectedCity.value;
    form.barangay = b;
    form.zip_code = zipCode.value;
});

const availableBarangays = computed(() => {
    if (!selectedCity.value || !props.barangays) return [];
    return props.barangays[selectedCity.value] || [];
});

// ─── Map pin → form (same logic as Registration) ─────────────────────────────
const onMapAddressPicked = ({ city, barangay }) => {
    if (!city && !barangay) return;
    isProgrammaticChange = true;

    const cityKeys = Object.keys(props.cities || {});
    const mCity = fuzzyMatch(city, cityKeys);
    let mBrgy = null;

    if (mCity) {
        selectedCity.value = mCity;
        _applyCityChange(mCity);

        if (barangay) {
            const brgys = props.barangays?.[mCity] || [];
            mBrgy = fuzzyMatch(barangay, brgys);
            if (mBrgy) {
                selectedBarangay.value = mBrgy;
            } else {
                selectedBarangay.value = 'other';
                manualBarangay.value = barangay;
            }
        }
    }

    const cLabel = mCity || city || '';
    const bLabel = mBrgy || (barangay ? `${barangay} (unmatched)` : '');
    if (cLabel) {
        detectedLocation.value = `Detected: ${cLabel}${bLabel ? ' · Brgy. ' + bLabel : ''}`;
        clearTimeout(detectedTimer);
        detectedTimer = setTimeout(() => { detectedLocation.value = ''; }, 5000);
    }
    isProgrammaticChange = false;
};

function fillTemplatePlaceholders(template) {
    if (!template) return '';
    return template
        .replaceAll('{shop_name}', props.distributor.company_name || 'Your shop')
        .replaceAll('{order_number}', 'ME-12345')
        .replaceAll('{customer_name}', 'Customer');
}

const previewShopGreeting = computed(() =>
    fillTemplatePlaceholders(
        props.defaultTemplates?.shop_greeting ||
            "Hi! Welcome to {shop_name}. We'll get back to you as soon as possible. Thank you for reaching out!"
    )
);

const previewOrderAccepted = computed(() =>
    fillTemplatePlaceholders(
        props.defaultTemplates?.order_accepted ||
            'Hello, this is {shop_name}. Your order {order_number} has been received. Please allow 1–3 business days for preparation. Thank you for your order!'
    )
);

const previewOrderShipped = computed(() =>
    fillTemplatePlaceholders(
        props.defaultTemplates?.order_shipped ||
            'Your order {order_number} has been shipped. For COD orders, please prepare the exact amount for the courier. Thank you!'
    )
);

const sanitizePhoneNumber = () => {
    form.phone = String(form.phone || '').replace(/\D/g, '').slice(0, 11);
};

const handleLogoUpload = (e) => {
    const file = e.target.files[0];
    if (file) { form.logo = file; logoPreview.value = URL.createObjectURL(file); }
};

const handleCoverUpload = (e) => {
    const file = e.target.files[0];
    if (file) { form.cover_photo = file; coverPreview.value = URL.createObjectURL(file); }
};

const checkSlug = debounce(async () => {
    if (!form.slug) return;
    try {
        const response = await axios.post(route('owner.profile.checkSlug'), { slug: form.slug });
        slugStatus.value = {
            available: response.data.available,
            message: response.data.available ? 'URL is available' : 'URL is already taken',
        };
    } catch { /* noop */ }
}, 500);

const submit = () => {
    form.post(route('owner.profile.update'), { preserveScroll: true });
};
</script>
