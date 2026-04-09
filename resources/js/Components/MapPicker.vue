<template>
    <div class="relative w-full rounded-xl overflow-hidden shadow-sm border border-gray-300">
        <!-- Container for leaflet map -->
        <div ref="mapContainer" :style="{ height: height }" class="w-full relative z-0"></div>

        <!-- Overlays -->
        <div class="absolute top-2 left-1/2 -translate-x-1/2 z-[400] flex gap-2 w-11/12 max-w-sm">
            <button
                type="button"
                @click.prevent="getUserLocation"
                class="flex-1 bg-white text-gray-800 font-semibold text-sm px-4 py-2 rounded-lg shadow-md hover:bg-gray-50 flex items-center justify-center gap-2 transition"
                :disabled="isLocating || isGeocoding"
            >
                <svg v-if="isLocating || isGeocoding" class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                {{ isLocating ? 'Locating...' : isGeocoding ? 'Detecting...' : 'Use My Location' }}
            </button>
        </div>
        
        <div v-if="locationError" class="absolute top-14 left-1/2 -translate-x-1/2 z-[400] w-11/12 max-w-sm">
            <p class="text-xs text-red-700 bg-red-50/95 backdrop-blur px-3 py-2 rounded-lg shadow-lg border border-red-200 text-center font-bold animate-bounce">
                ⚠️ {{ locationError }}
            </p>
        </div>

        <div class="absolute bottom-2 left-2 right-2 z-[400]">
            <p v-if="isGeocoding" class="text-[11px] text-blue-700 bg-blue-50/90 backdrop-blur px-2 py-1 rounded shadow-sm text-center animate-pulse">
                📍 Detecting location...
            </p>
            <p v-else class="text-[11px] text-gray-700 bg-white/90 backdrop-blur px-2 py-1 rounded shadow-sm text-center">
                <strong>Cavite Service Area Only.</strong> Drag the marker or click inside the red border.
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Fix leaflet default icon issue with bundlers
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

const props = defineProps({
    lat: {
        type: [Number, String],
        default: null
    },
    lng: {
        type: [Number, String],
        default: null
    },
    height: {
        type: String,
        default: '300px'
    },
    defaultLat: {
        type: Number,
        default: 14.4333  // Cavite center
    },
    defaultLng: {
        type: Number,
        default: 120.8972
    },
    // When this changes, the map pans to the geocoded result (form → map)
    geocodeQuery: {
        type: String,
        default: null
    },
});

const emit = defineEmits(['update:lat', 'update:lng', 'update:address']);

const mapContainer = ref(null);
let map = null;
let marker = null;

const isLocating = ref(false);
const isGeocoding = ref(false);
const locationError = ref('');

onMounted(() => {
    initMap();
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
    }
});

const initMap = () => {
    const initialLat = props.lat ? parseFloat(props.lat) : props.defaultLat;
    const initialLng = props.lng ? parseFloat(props.lng) : props.defaultLng;

    const caviteBounds = [
        [14.08, 120.62], // Southwest (refined for land-alignment)
        [14.52, 121.08]  // Northeast (refined for land-alignment)
    ];

    // Looser view bounds to avoid feeling "trapped"
    const viewBounds = [
        [13.50, 120.00], 
        [15.00, 121.50]
    ];

    map = L.map(mapContainer.value, {
        center: [initialLat, initialLng],
        zoom: props.lat ? 16 : 11,
        minZoom: 9,
        maxBounds: viewBounds,
        maxBoundsViscosity: 0.5, 
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Visual boundary - thick red dashed line
    L.rectangle(caviteBounds, {
        color: "#ef4444", 
        weight: 5,
        fill: true,
        fillOpacity: 0.02,
        dashArray: '10, 10',
        interactive: false
    }).addTo(map);

    if (props.lat && props.lng) {
        setMarker(initialLat, initialLng);
    }

    map.on('click', (e) => {
        const wasSuccessful = setMarker(e.latlng.lat, e.latlng.lng);
        if (wasSuccessful) {
            emitCoordinates(e.latlng.lat, e.latlng.lng);
            reverseGeocode(e.latlng.lat, e.latlng.lng);
        }
    });
};

const setMarker = (lat, lng) => {
    // Validate bounds (Lenient)
    const isInside = lat >= 14.08 && lat <= 14.52 && lng >= 120.62 && lng <= 121.08;
    
    if (!isInside) {
        // Find the last valid position or use default
        const resetLat = props.lat ? parseFloat(props.lat) : props.defaultLat;
        const resetLng = props.lng ? parseFloat(props.lng) : props.defaultLng;
        
        if (marker) {
            marker.setLatLng([resetLat, resetLng]);
        }
        
        locationError.value = "SERVICE AREA VIOLATION: Delivery is only available within Cavite province.";
        if (window.errorTimeout) clearTimeout(window.errorTimeout);
        window.errorTimeout = setTimeout(() => { locationError.value = ''; }, 5000);
        return false;
    }

    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', (e) => {
            const pos = marker.getLatLng();
            const wasSuccessful = setMarker(pos.lat, pos.lng);
            if (wasSuccessful) {
                emitCoordinates(pos.lat, pos.lng);
                reverseGeocode(pos.lat, pos.lng);
            }
        });
    }
    return true;
};

const emitCoordinates = (lat, lng) => {
    emit('update:lat', lat.toFixed(8));
    emit('update:lng', lng.toFixed(8));
};

// ─── Reverse Geocode: pin → form ──────────────────────────────────────────────
const reverseGeocode = async (lat, lng) => {
    isGeocoding.value = true;
    locationError.value = '';
    try {
        const res = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&zoom=16`,
            { headers: { 'Accept-Language': 'en' } }
        );
        if (!res.ok) return;
        const data = await res.json();
        const addr = data.address || {};
        
        // Final strict check: Must be in Cavite province (not sea or nearby towns)
        const province = addr.province || '';
        const state = addr.state || '';
        const county = addr.county || '';
        
        const isOfficialCavite = 
            province.toLowerCase().includes('cavite') || 
            state.toLowerCase().includes('cavite') ||
            county.toLowerCase().includes('cavite');

        if (!isOfficialCavite) {
            locationError.value = "OFFICIAL LIMIT: This specific location is outside Cavite province.";
            // Reset to last valid or default
            const resetLat = props.lat ? parseFloat(props.lat) : props.defaultLat;
            const resetLng = props.lng ? parseFloat(props.lng) : props.defaultLng;
            if (marker) marker.setLatLng([resetLat, resetLng]);
            if (window.errorTimeout) clearTimeout(window.errorTimeout);
            window.errorTimeout = setTimeout(() => { locationError.value = ''; }, 5000);
            return;
        }

        // Barangay: OSM uses various field names for PH barangays
        const barangay = addr.suburb
            || addr.village
            || addr.quarter
            || addr.neighbourhood
            || addr.hamlet
            || '';

        // City / Municipality
        const city = addr.city
            || addr.town
            || addr.municipality
            || addr.county
            || '';

        emit('update:address', { city: city.trim(), barangay: barangay.trim() });
    } catch {
        // Silent fail — form stays as-is
    } finally {
        isGeocoding.value = false;
    }
};

// ─── Forward Geocode: form → map (pan only, no new pin) ───────────────────────
const forwardGeocode = async (query) => {
    if (!query || !map) return;
    try {
        const res = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=ph&viewbox=120.62,14.52,121.08,14.08&bounded=1`,
            { headers: { 'Accept-Language': 'en' } }
        );
        if (!res.ok) return;
        const data = await res.json();
        if (data.length > 0) {
            const { lat, lon } = data[0];
            map.flyTo([parseFloat(lat), parseFloat(lon)], 14, { duration: 0.8 });
        }
    } catch {
        // Silent fail
    }
};

// Debounce for form → map panning
let geocodeDebounceTimer = null;
watch(() => props.geocodeQuery, (query) => {
    if (!query) return;
    clearTimeout(geocodeDebounceTimer);
    geocodeDebounceTimer = setTimeout(() => forwardGeocode(query), 600);
});

const getUserLocation = () => {
    locationError.value = '';
    if (!navigator.geolocation) {
        locationError.value = "Geolocation is not supported by your browser.";
        return;
    }
    
    isLocating.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            isLocating.value = false;
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // Validate if inside Cavite bounds
            if (lat < 14.08 || lat > 14.52 || lng < 120.62 || lng > 121.08) {
                locationError.value = "Your current location is outside the supported service area (Cavite).";
                return;
            }

            map.flyTo([lat, lng], 17);
            setMarker(lat, lng);
            emitCoordinates(lat, lng);
            reverseGeocode(lat, lng);
        },
        (error) => {
            isLocating.value = false;
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    locationError.value = "Permission denied.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    locationError.value = "Position unavailable.";
                    break;
                case error.TIMEOUT:
                    locationError.value = "Request timed out.";
                    break;
                default:
                    locationError.value = "Unknown error.";
                    break;
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
};

watch(() => props.lat, (newVal) => {
    if (newVal && props.lng && map) {
        const parsedLat = parseFloat(newVal);
        const parsedLng = parseFloat(props.lng);
        
        const currentMarkerPos = marker ? marker.getLatLng() : null;
        if (!currentMarkerPos || 
            (currentMarkerPos.lat.toFixed(6) !== parsedLat.toFixed(6) || 
             currentMarkerPos.lng.toFixed(6) !== parsedLng.toFixed(6))) 
        {
            setMarker(parsedLat, parsedLng);
            map.setView([parsedLat, parsedLng], 16);
        }
    }
});
</script>
