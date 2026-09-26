<template>
    <OnboardingLayout title="Distributor Application — MedEquip">
        <div class="w-full max-w-2xl">

            <!-- Step indicator -->
            <div class="flex items-center justify-center gap-0 mb-4">
                <template v-for="(s, i) in steps" :key="i">
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 border-2"
                            :class="currentStep > i
                                ? 'bg-brand border-brand text-white'
                                : currentStep === i
                                    ? 'bg-white border-brand text-brand'
                                    : 'bg-white border-line text-ink-faint'">
                            <svg v-if="currentStep > i" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span v-else>{{ i + 1 }}</span>
                        </div>
                        <p class="text-xs font-semibold mt-1 text-center max-w-[60px] leading-tight"
                            :class="currentStep === i ? 'text-brand' : 'text-ink-faint'">
                            {{ s.label }}
                        </p>
                    </div>
                    <div v-if="i < steps.length - 1"
                        class="flex-1 h-0.5 mx-1 mb-5 transition-all duration-300"
                        :class="currentStep > i ? 'bg-brand' : 'bg-line'">
                    </div>
                </template>
            </div>

            <!-- Draft Status -->
            <div v-if="lastSaved" class="flex justify-center mb-6">
                <div class="flex items-center gap-1.5 px-3 py-1 bg-brand-tint text-brand rounded-full text-xs font-bold border border-brand-soft">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Draft Saved: {{ lastSaved }}
                </div>
            </div>

            <!-- Flash errors -->
            <div v-if="Object.keys(form.errors).length" class="mb-5 bg-red-50 border border-red-200 rounded-card p-4">
                <p class="text-sm font-semibold text-red-700 mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <li v-for="(err, key) in form.errors" :key="key" class="text-sm text-red-600">{{ err }}</li>
                </ul>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-card shadow-lg border border-line overflow-hidden">

                <!-- ========= STEP 1: Welcome ========= -->
                <div v-if="currentStep === 0" class="p-8">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-brand-tint rounded-card flex items-center justify-center mx-auto mb-4 shadow-inner">
                            <svg class="w-8 h-8 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-ink mb-2">Become a MedEquip Distributor</h1>
                        <p class="text-ink-soft text-sm max-w-md mx-auto leading-relaxed">
                            Join our network of trusted medical equipment distributors. The process takes about 5 minutes and requires a few business documents.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                        <div v-for="item in requirements" :key="item.title" class="bg-mist rounded-card p-4 text-center border border-line">
                            <div class="w-10 h-10 mx-auto mb-2 flex items-center justify-center bg-brand-tint rounded-card">
                                <!-- document -->
                                <svg v-if="item.icon === 'document'" class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <!-- shield -->
                                <svg v-else-if="item.icon === 'shield'" class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <!-- id -->
                                <svg v-else class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                            </div>
                            <p class="text-xs font-bold text-ink">{{ item.title }}</p>
                            <p class="text-[11px] text-ink-soft mt-0.5">{{ item.desc }}</p>
                        </div>
                    </div>

                    <button @click="currentStep = 1"
                        class="w-full bg-brand text-white font-bold py-3.5 rounded-card hover:bg-brand-dark transition shadow-md flex items-center justify-center gap-2">
                        Get Started
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </button>
                </div>

                <!-- ========= STEP 2: Business Info ========= -->
                <div v-else-if="currentStep === 1" class="p-8">
                    <h2 class="text-xl font-bold text-ink mb-1">Business Information</h2>
                    <p class="text-sm text-ink-soft mb-6">Tell us about your company.</p>

                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-ink mb-1.5">Company / Business Name <span class="text-red-500">*</span></label>
                            <input v-model="form.company_name" type="text" placeholder="e.g. Acme Medical Supplies Co."
                                class="w-full px-4 py-3 border rounded-card text-sm focus:ring-2 focus:ring-brand focus:border-transparent transition"
                                :class="form.errors.company_name ? 'border-red-300 bg-red-50' : 'border-line'"/>
                            <p v-if="form.errors.company_name" class="text-red-500 text-xs mt-1">{{ form.errors.company_name }}</p>
                        </div>

                        <!-- Structured Address -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-ink mb-1.5">Street / Building / Unit <span class="text-red-500">*</span></label>
                                <input v-model="form.address_line" type="text" placeholder="e.g. Blk 5 Lot 10 Sampaguita St."
                                    class="w-full px-4 py-3 border rounded-card text-sm focus:ring-2 focus:ring-brand focus:border-transparent transition"
                                    :class="form.errors.address_line ? 'border-red-300 bg-red-50' : 'border-line'"/>
                                <p v-if="form.errors.address_line" class="text-red-500 text-xs mt-1">{{ form.errors.address_line }}</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-ink mb-1.5">City / Municipality <span class="text-red-500">*</span></label>
                                    <select v-model="selectedCity" @change="onCityChange"
                                        class="w-full px-4 py-3 border border-line rounded-card text-sm focus:ring-2 focus:ring-brand focus:border-transparent transition bg-white"
                                        :class="form.errors.city ? 'border-red-300 bg-red-50' : ''">
                                        <option value="">Select city</option>
                                        <option v-for="(data, city) in cities" :key="city" :value="city">{{ city }}</option>
                                    </select>
                                    <p v-if="form.errors.city" class="text-red-500 text-xs mt-1">{{ form.errors.city }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-ink mb-1.5">Barangay <span class="text-red-500">*</span></label>
                                    <select v-if="availableBarangays.length > 0" v-model="selectedBarangay"
                                        class="w-full px-4 py-3 border border-line rounded-card text-sm focus:ring-2 focus:ring-brand focus:border-transparent transition bg-white"
                                        :class="form.errors.barangay ? 'border-red-300 bg-red-50' : ''">
                                        <option value="">Select barangay</option>
                                        <option v-for="brgy in availableBarangays" :key="brgy" :value="brgy">{{ brgy }}</option>
                                        <option value="other">Other (type manually)</option>
                                    </select>
                                    <input v-else v-model="manualBarangay" type="text" placeholder="Enter barangay name"
                                        class="w-full px-4 py-3 border border-line rounded-card text-sm focus:ring-2 focus:ring-brand focus:border-transparent transition"
                                        :class="form.errors.barangay ? 'border-red-300 bg-red-50' : ''"/>
                                    <p v-if="form.errors.barangay" class="text-red-500 text-xs mt-1">{{ form.errors.barangay }}</p>
                                </div>
                            </div>

                            <div v-if="selectedBarangay === 'other' && availableBarangays.length > 0">
                                <label class="block text-sm font-semibold text-ink mb-1.5">Barangay Name <span class="text-red-500">*</span></label>
                                <input v-model="manualBarangay" type="text" placeholder="Type your barangay name"
                                    class="w-full px-4 py-3 border border-line rounded-card text-sm focus:ring-2 focus:ring-brand focus:border-transparent transition" />
                            </div>

                            <div class="max-w-[150px]">
                                <label class="block text-sm font-semibold text-ink mb-1.5">Zip Code</label>
                                <input v-model="zipCode" type="text" readonly placeholder="Auto-filled"
                                    class="w-full px-4 py-3 border border-line rounded-card bg-mist text-ink-soft text-sm cursor-not-allowed"/>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-sm font-semibold text-ink mb-1.5">Contact Number <span class="text-red-500">*</span></label>
                                <input v-model="form.contact_number" @input="sanitizeContactNumber"
                                    type="tel" inputmode="numeric" maxlength="11" placeholder="09XXXXXXXXX"
                                    class="w-full px-4 py-3 border rounded-card text-sm focus:ring-2 focus:ring-brand focus:border-transparent transition"
                                    :class="form.errors.contact_number ? 'border-red-300 bg-red-50' : 'border-line'"/>
                                <p v-if="form.errors.contact_number" class="text-red-500 text-xs mt-1">{{ form.errors.contact_number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-ink mb-1.5">Business Email <span class="text-red-500">*</span></label>
                                <input v-model="form.email" type="email" placeholder="contact@yourbusiness.com"
                                    class="w-full px-4 py-3 border rounded-card text-sm focus:ring-2 focus:ring-brand focus:border-transparent transition"
                                    :class="form.errors.email ? 'border-red-300 bg-red-50' : 'border-line'"/>
                                <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-ink mb-1.5 flex items-center gap-2">
                                Business Location Pin <span class="text-red-500">*</span>
                                <span class="text-xs font-normal text-ink-faint italic">(Drop a pin to detect address)</span>
                            </label>
                            <p class="text-[11px] text-ink-soft mb-2 italic">Drag the marker or click on your exact business location on the map.</p>
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
                                <p v-if="detectedLocation" class="mt-2 text-xs text-brand-dark bg-brand-tint border border-brand-soft rounded-control px-3 py-2 flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 text-brand flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                    {{ detectedLocation }}
                                </p>
                            </transition>
                            <p v-if="form.errors.latitude" class="text-red-500 text-xs mt-1">{{ form.errors.latitude }}</p>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-8">
                        <button @click="currentStep = 0" class="flex-none px-5 py-3 border border-line text-ink font-semibold rounded-card hover:bg-mist transition text-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                            Back
                        </button>
                        <button @click="validateStep1" class="flex-1 bg-brand text-white font-bold py-3 rounded-card hover:bg-brand-dark transition text-sm flex items-center justify-center gap-1.5">
                            Continue
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </button>
                    </div>
                </div>

                <!-- ========= STEP 3: Documents ========= -->
                <div v-else-if="currentStep === 2" class="p-8">
                    <h2 class="text-xl font-bold text-ink mb-1">Upload Documents</h2>
                    <p class="text-sm text-ink-soft mb-6">PDF, JPG, or PNG — max 10 MB per file.</p>

                    <div class="space-y-4">
                        <template v-for="doc in docFields" :key="doc.key">
                            <div class="rounded-card border p-4 transition"
                                :class="form[doc.key] ? 'border-brand-soft bg-brand-tint/50' : (form.errors[doc.key] ? 'border-red-200 bg-red-50' : 'border-line hover:border-line')">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <label :for="doc.key" class="block text-sm font-semibold text-ink mb-0.5 cursor-pointer">
                                            {{ doc.label }}
                                            <span v-if="!doc.optional" class="text-red-500"> *</span>
                                            <span v-else class="text-ink-faint font-normal"> (optional)</span>
                                        </label>
                                        <p v-if="doc.hint" class="text-xs text-ink-soft">{{ doc.hint }}</p>
                                        <p v-if="form.errors[doc.key]" class="text-xs text-red-600 mt-1">{{ form.errors[doc.key] }}</p>

                                        <!-- Expiration Date Field -->
                                        <div v-if="form[doc.key] && doc.expiryKey" class="mt-3   ">
                                            <label class="block text-xs font-bold text-ink-faint   mb-1">Expiration Date</label>
                                            <div class="relative">
                                                <input type="date" v-model="form[doc.expiryKey]"
                                                    class="w-full px-3 py-1.5 border border-line rounded-control text-xs focus:ring-1 focus:ring-brand bg-white"
                                                    :class="{' bg-brand-tint border-brand-soft': scanningFields[doc.key]}"/>
                                                <div v-if="scanningFields[doc.key]" class="absolute right-2 top-1.5 flex items-center gap-1.5">
                                                    <span class="text-xs text-brand font-semibold italic">Scanning...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 flex items-center gap-2">
                                        <template v-if="serverHasDocs[doc.key]">
                                            <span class="inline-flex items-center gap-1 text-xs font-bold text-brand-dark bg-brand-tint px-2.5 py-1 rounded-full">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                Already uploaded
                                            </span>
                                            <button type="button" @click="replaceDoc(doc.key)" class="bg-white border border-line text-ink text-xs font-semibold px-3 py-1.5 rounded-control hover:bg-mist transition">
                                                Replace
                                            </button>
                                        </template>
                                        <template v-else>
                                            <span v-if="form[doc.key]" class="inline-flex items-center gap-1 text-xs font-semibold text-brand bg-brand-tint px-2 py-0.5 rounded-full truncate max-w-[100px]">
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                {{ form[doc.key].name }}
                                            </span>
                                            <label :for="doc.key" class="cursor-pointer bg-white border border-line text-ink text-xs font-semibold px-3 py-1.5 rounded-control hover:bg-mist transition flex-shrink-0">
                                                {{ form[doc.key] ? 'Change' : 'Upload' }}
                                            </label>
                                            <input :id="doc.key" type="file" accept=".jpg,.jpeg,.png"
                                                @change="e => handleFileChange(doc.key, e.target.files[0])"
                                                class="sr-only"/>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex gap-3 mt-8">
                        <button @click="currentStep = 1" class="flex-none px-5 py-3 border border-line text-ink font-semibold rounded-card hover:bg-mist transition text-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                            Back
                        </button>
                        <button @click="validateStep2" class="flex-1 bg-brand text-white font-bold py-3 rounded-card hover:bg-brand-dark transition text-sm flex items-center justify-center gap-1.5">
                            Review &amp; Submit
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </button>
                    </div>
                </div>

                <!-- ========= STEP 4: Review (compact) ========= -->
                <div v-else-if="currentStep === 3" class="p-8">
                    <h2 class="text-xl font-bold text-ink mb-1">Ready to submit?</h2>
                    <p class="text-sm text-ink-soft mb-5">Double-check below. Use Back to edit any step.</p>

                    <div class="rounded-card border border-line bg-white p-4 mb-5 text-sm space-y-3">
                        <div>
                            <p class="text-xs font-bold text-ink-faint  ">Business</p>
                            <p class="font-semibold text-ink mt-0.5">{{ form.company_name }}</p>
                            <p class="text-ink-soft text-xs mt-1 line-clamp-3">{{ form.address }}</p>
                            <p class="text-ink-soft text-xs mt-1">{{ form.contact_number }} · {{ form.email }}</p>
                            <p class="text-xs text-brand font-bold mt-1  ">
                                📍 {{ form.latitude }}, {{ form.longitude }}
                            </p>
                        </div>
                        <div class="pt-2 border-t border-line">
                            <p class="text-xs font-bold text-ink-faint   mb-1.5">Documents</p>
                            <p class="text-ink">
                                <span class="text-brand font-semibold">{{ uploadedDocCount }}</span> of {{ requiredDocCount }} required files attached
                                <span v-if="form.authorization_letter" class="text-ink-soft"> + authorization letter</span>
                            </p>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-card p-4 mb-6 flex gap-3">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <p class="text-xs text-amber-700 leading-relaxed">By submitting, you confirm that all information is accurate and all documents are authentic. False submissions may result in permanent account termination.</p>
                    </div>

                    <div class="flex gap-3">
                        <button @click="currentStep = 2" class="flex-none px-5 py-3 border border-line text-ink font-semibold rounded-card hover:bg-mist transition text-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                            Back
                        </button>
                        <button @click="submit" :disabled="form.processing"
                            class="flex-1 bg-brand text-white font-bold py-3 rounded-card hover:bg-brand-dark transition text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                            <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ form.processing ? 'Submitting...' : 'Submit Application' }}
                        </button>
                    </div>
                </div>

            </div>

            <!-- Support note -->
            <p class="text-center text-xs text-ink-faint mt-5">
                Need help? Email us at <a href="mailto:support@medequip.ph" class="text-brand hover:underline">support@medequip.ph</a>
            </p>
        </div>
    </OnboardingLayout>
</template>

<script setup>
import { computed, ref, watch, onMounted, reactive, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import MapPicker from '@/Components/MapPicker.vue';
import { useOCR } from '@/Composables/useOCR';

const props = defineProps({
    ownerEmail: String,
    ownerPhone: String,
    cities: Object,
    barangays: Object,
    existingDistributor: { type: Object, default: null },
});

const { scanImage, extractExpirationDate } = useOCR();
const currentStep = ref(0);
const scanningFields = ref({});

const steps = [
    { label: 'Welcome' },
    { label: 'Business Info' },
    { label: 'Documents' },
    { label: 'Review' },
];

const requirements = [
    { icon: 'document', title: 'Business Documents', desc: 'DTI/SEC, Business Permit, BIR Form' },
    { icon: 'shield',   title: 'FDA Compliance',     desc: 'License to Operate (LTO) & PRC ID' },
    { icon: 'id',       title: 'Personal ID',         desc: 'Valid government-issued ID' },
];

const docFields = [
    { key: 'dti_sec',              label: 'DTI Certificate or SEC Registration', short: 'DTI/SEC',         hint: null,             optional: false, expiryKey: 'dti_sec_expires_at' },
    { key: 'business_license',     label: 'Business Permit',                      short: 'Business Permit', hint: null,             optional: false, expiryKey: 'business_license_expires_at' },
    { key: 'bir_form',             label: 'BIR Form (e.g. 2303)',                 short: 'BIR Form',        hint: null,             optional: false, expiryKey: 'bir_form_expires_at' },
    { key: 'fda_license',          label: 'FDA License to Operate (LTO)',         short: 'FDA LTO',         hint: null,             optional: false, expiryKey: 'fda_license_expires_at' },
    { key: 'prc_id',               label: 'Pharmacist / Qualified Person PRC ID', short: 'PRC ID',          hint: null,             optional: false, expiryKey: 'prc_id_expires_at' },
    { key: 'valid_id',             label: 'Primary Valid Government ID',           short: 'Gov\'t ID',       hint: 'Driver\'s License, Passport, UMID, etc.', optional: false, expiryKey: 'valid_id_expires_at' },
    { key: 'authorization_letter', label: 'Authorization Letter',                  short: 'Auth Letter',     hint: 'Required only if you are not the business owner.', optional: true },
];

// Structured Address Refs
const selectedCity = ref('');
const selectedBarangay = ref('');
const manualBarangay = ref('');
const zipCode = ref('');

// For map <-> form sync
const geocodeQuery = ref('');
const detectedLocation = ref('');
let detectedTimer = null;
let isProgrammaticChange = false;

// Persistence state
const STORAGE_KEY = 'distributor_application_draft';
const lastSaved = ref(null);

const ex = props.existingDistributor;

const form = useForm({
    company_name: ex?.company_name || '',
    address: '',        // Final composed string
    address_line: ex?.address_line || '',   // Street/Unit
    city: ex?.city || '',
    barangay: ex?.barangay || '',
    contact_number: ex?.contact_number || props.ownerPhone || '',
    email: ex?.email || props.ownerEmail || '',
    latitude: ex?.latitude || '',
    longitude: ex?.longitude || '',
    valid_id: null,
    business_license: null,
    dti_sec: null,
    bir_form: null,
    fda_license: null,
    prc_id: null,
    authorization_letter: null,
    // Expiration dates
    valid_id_expires_at: ex?.valid_id_expires_at || '',
    business_license_expires_at: ex?.business_license_expires_at || '',
    dti_sec_expires_at: ex?.dti_sec_expires_at || '',
    bir_form_expires_at: ex?.bir_form_expires_at || '',
    fda_license_expires_at: ex?.fda_license_expires_at || '',
    prc_id_expires_at: ex?.prc_id_expires_at || '',
});

// Track which docs the server already has (from previous approved/uploaded steps)
// Keys match the docField key names above
const serverHasDocs = reactive({
    valid_id:             !!ex?.has_valid_id,
    business_license:     !!ex?.has_business_license,
    dti_sec:              !!ex?.has_dti_sec,
    bir_form:             !!ex?.has_bir_form,
    fda_license:          !!ex?.has_fda_license,
    prc_id:               !!ex?.has_prc_id,
    authorization_letter: !!ex?.has_authorization_letter,
});

// When user clicks "Replace" on an already-uploaded doc, we clear the server flag
const replaceDoc = (key) => {
    serverHasDocs[key] = false;
};;

// A rejected application always carries a `rejection_reason` key; a first-time applicant only
// gets prefilled registration details (company name, saved address).
const isReapplication = !!ex && 'rejection_reason' in ex;

onMounted(() => {
    // A first-time applicant's in-progress draft beats the registration prefill.
    const hasDraft = !isReapplication && !!localStorage.getItem(STORAGE_KEY);

    // Pre-sync address fields if re-applying with existing data (or prefilling from registration)
    if (ex?.city && !hasDraft) {
        // Setting the city/barangay below would otherwise trigger the geocoder, which replaces the
        // exact pin from registration (or the rejected application) with the barangay's centre.
        isProgrammaticChange = true;
        nextTick(() => { isProgrammaticChange = false; });
        selectedCity.value = ex.city;
        _applyCityChange(ex.city);
        if (props.barangays?.[ex.city]) {
            const list = props.barangays[ex.city];
            if (list.includes(ex.barangay)) {
                selectedBarangay.value = ex.barangay;
            } else if (ex.barangay) {
                selectedBarangay.value = 'other';
                manualBarangay.value = ex.barangay;
            }
        }
        // Skip loading draft — server data takes precedence on re-application
        return;
    }

    const draft = localStorage.getItem(STORAGE_KEY);
    if (draft) {
        try {
            const parsed = JSON.parse(draft);
            Object.keys(parsed).forEach(key => {
                // Don't load file objects
                if (['valid_id', 'business_license', 'dti_sec', 'bir_form', 'fda_license', 'prc_id', 'authorization_letter'].includes(key)) return;
                if (parsed[key]) form[key] = parsed[key];
            });

            // Re-sync local refs
            selectedCity.value = form.city;
            _applyCityChange(form.city);
            
            if (form.city && props.barangays?.[form.city]) {
                const list = props.barangays[form.city];
                if (list.includes(form.barangay)) {
                    selectedBarangay.value = form.barangay;
                } else if (form.barangay) {
                    selectedBarangay.value = 'other';
                    manualBarangay.value = form.barangay;
                }
            }
        } catch (e) {
            console.error('Failed to load application draft', e);
        }
    }
});

// Auto-save draft
watch(() => ({
    company_name: form.company_name,
    address_line: form.address_line,
    city: form.city,
    barangay: form.barangay,
    contact_number: form.contact_number,
    email: form.email,
    latitude: form.latitude,
    longitude: form.longitude,
    valid_id_expires_at: form.valid_id_expires_at,
    business_license_expires_at: form.business_license_expires_at,
    dti_sec_expires_at: form.dti_sec_expires_at,
    bir_form_expires_at: form.bir_form_expires_at,
    fda_license_expires_at: form.fda_license_expires_at,
    prc_id_expires_at: form.prc_id_expires_at,
}), (newVal) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
    lastSaved.value = new Date().toLocaleTimeString();
}, { deep: true });

const requiredDocCount = computed(() => docFields.filter((d) => !d.optional).length);
const uploadedDocCount = computed(() => docFields.filter((d) => !d.optional && form[d.key]).length);

// ─── Fuzzy match helper ──────────────────────────────────────────────────────
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
    // Super-string: input contains the list item (e.g., "City of Imus" contains "Imus")
    const superStr = list.find(it => nInput.includes(normalize(it)));
    if (superStr) return superStr;
    const subStr = list.find(it => normalize(it).includes(nInput));
    if (subStr) return subStr;
    return null;
};

// ─── Internal city-change helper ─────────────────────────────────────────────
const _applyCityChange = (city) => {
    selectedBarangay.value = '';
    manualBarangay.value = '';
    if (city && props.cities[city]) {
        zipCode.value = props.cities[city].zip;
        if ((props.barangays?.[city] || []).length === 0) {
            selectedBarangay.value = 'other';
        }
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

// ─── User changes barangay — pan map ─────────────────────────────────────────
watch(selectedBarangay, (brgy) => {
    if (!isProgrammaticChange && brgy && brgy !== 'other' && selectedCity.value) {
        geocodeQuery.value = `Barangay ${brgy}, ${selectedCity.value}, Cavite, Philippines`;
    }
});

// ─── Sync structured fields to form.address ───────────────────────────────
watch([() => form.address_line, selectedCity, selectedBarangay, manualBarangay], () => {
    const b = selectedBarangay.value === 'other' ? manualBarangay.value : selectedBarangay.value;
    const parts = [];
    if (form.address_line) parts.push(form.address_line);
    if (b) parts.push(`Brgy. ${b}`);
    if (selectedCity.value) parts.push(selectedCity.value);
    parts.push('Cavite');
    
    form.address = parts.join(', ');
    form.city = selectedCity.value;
    form.barangay = b;
});

const availableBarangays = computed(() => {
    if (!selectedCity.value || !props.barangays) return [];
    return props.barangays[selectedCity.value] || [];
});

// ─── Map pin → form ────────────────────────────────────────────────────────
const onMapAddressPicked = ({ city, barangay, lat, lng }) => {
    if (!city && !barangay && !lat) return;
    isProgrammaticChange = true;

    if (lat && lng) {
        form.latitude = lat;
        form.longitude = lng;
    }

    const cityKeys = Object.keys(props.cities || {});
    const matchedCity = fuzzyMatch(city, cityKeys);
    let matchedBrgy = null;

    if (matchedCity) {
        selectedCity.value = matchedCity;
        _applyCityChange(matchedCity);

        if (barangay) {
            const brgys = props.barangays?.[matchedCity] || [];
            matchedBrgy = fuzzyMatch(barangay, brgys);
            if (matchedBrgy) {
                selectedBarangay.value = matchedBrgy;
            } else {
                selectedBarangay.value = 'other';
                manualBarangay.value = barangay;
            }
        }
    }

    const cityLabel = matchedCity || city || '';
    const brgyLabel = matchedBrgy || (barangay ? `${barangay} (unmatched)` : '');
    if (cityLabel) {
        detectedLocation.value = `Detected: ${cityLabel}${brgyLabel ? ' · Brgy. ' + brgyLabel : ''}`;
        clearTimeout(detectedTimer);
        detectedTimer = setTimeout(() => { detectedLocation.value = ''; }, 5000);
    }
    isProgrammaticChange = false;
};

const onMapMoved = ({ lat, lng }) => {
    form.latitude = lat;
    form.longitude = lng;
};

// ─── External Geocoding ───────────────────────────────────────────────────
watch(geocodeQuery, async (query) => {
    if (!query || isProgrammaticChange) return;
    
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`);
        const data = await response.json();
        if (data && data[0]) {
            form.latitude = parseFloat(data[0].lat);
            form.longitude = parseFloat(data[0].lon);
            
            // Also try to get city/brgy from the results if possible
            const res = data[0].display_name.split(',');
            // Simple heuristic
            const cityGuess = res[0].trim();
            const matched = fuzzyMatch(cityGuess, Object.keys(props.cities || {}));
            if (matched) {
                selectedCity.value = matched;
                _applyCityChange(matched);
            }
        }
    } catch (e) {
        console.error('Geocoding failed', e);
    }
});

const sanitizeContactNumber = () => {
    form.contact_number = String(form.contact_number || '').replace(/\D/g, '').slice(0, 11);
};

const handleFileChange = async (key, file) => {
    if (!file) {
        form[key] = null;
        return;
    }
    
    // 10MB Limit for client-side check
    if (file.size > 10 * 1024 * 1024) {
        form.errors[key] = 'File is too large (max 10MB).';
        form[key] = null;
        return;
    }
    
    delete form.errors[key];
    form[key] = file;

    // Trigger OCR for image files
    const field = docFields.find(d => d.key === key);
    if (field && field.expiryKey && file.type.match('image.*')) {
        scanningFields.value[key] = true;
        try {
            const text = await scanImage(file);
            const date = extractExpirationDate(text);
            if (date) {
                form[field.expiryKey] = date;
            }
        } catch (error) {
            console.error('OCR failed for', key, error);
        } finally {
            scanningFields.value[key] = false;
        }
    }
};

const validateStep1 = () => {
    if (!form.company_name.trim())    { form.errors.company_name = 'Company name is required.'; return; }
    if (!form.address_line.trim())    { form.errors.address_line = 'Street address is required.'; return; }
    if (!selectedCity.value)          { form.errors.city = 'City is required.'; return; }
    if (!form.barangay)               { form.errors.barangay = 'Barangay is required.'; return; }
    if (!/^09[0-9]{9}$/.test(form.contact_number)) { form.errors.contact_number = 'Must be 11 digits starting with 09.'; return; }
    if (!form.email.trim())           { form.errors.email = 'Email is required.'; return; }
    if (!form.latitude || !form.longitude) { form.errors.latitude = 'Please pin your business location on the map.'; return; }
    
    // Clear manual errors
    delete form.errors.company_name;
    delete form.errors.address_line;
    delete form.errors.city;
    delete form.errors.barangay;
    delete form.errors.contact_number;
    delete form.errors.email;
    delete form.errors.latitude;
    
    currentStep.value = 2;
};

const validateStep2 = () => {
    const required = docFields.filter(d => !d.optional);
    const missing = required.find(d => !form[d.key] && !serverHasDocs[d.key]);
    if (missing) {
        form.errors[missing.key] = `${missing.label} is required.`;
        return;
    }
    // Check expiration dates
    const expiryMissing = required.find(d => d.expiryKey && !form[d.expiryKey]);
    if (expiryMissing) {
        form.errors[expiryMissing.key] = `Please provide expiration date for ${expiryMissing.short}.`;
        return;
    }
    currentStep.value = 3;
};

const submit = () => {
    form.post('/owner/distributor/store', {
        forceFormData: true,
        onSuccess: () => {
            localStorage.removeItem(STORAGE_KEY);
        },
        onError: () => {
            // If there are validation errors from server, jump to the relevant step
            const step1Keys = ['company_name', 'address_line', 'city', 'barangay', 'contact_number', 'email'];
            const hasStep1Err = step1Keys.some(k => form.errors[k]);
            if (hasStep1Err) { currentStep.value = 1; return; }
            const hasDocErr = docFields.some(d => form.errors[d.key]);
            if (hasDocErr) { currentStep.value = 2; }
        },
    });
};
</script>
