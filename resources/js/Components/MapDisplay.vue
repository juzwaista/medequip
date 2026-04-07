<template>
    <div class="relative w-full rounded-xl overflow-hidden shadow-sm border border-gray-300">
        <!-- Container for leaflet map -->
        <div ref="mapContainer" :style="{ height: height }" class="w-full relative z-0"></div>

        <!-- Coordinates Overlay (Optional) -->
        <div class="absolute bottom-2 left-2 right-2 z-[400] flex justify-center pointer-events-none" v-if="lat && lng">
            <p class="text-[10px] sm:text-xs text-gray-700 bg-white/90 backdrop-blur px-2 py-1 rounded shadow-sm text-center font-mono">
                {{ Number(lat).toFixed(6) }}, {{ Number(lng).toFixed(6) }}
            </p>
        </div>

        <div v-if="!lat || !lng" class="absolute inset-0 bg-gray-100 flex items-center justify-center z-[500]">
            <p class="text-sm text-gray-500">Location coordinates unavailable.</p>
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
        required: true
    },
    lng: {
        type: [Number, String],
        required: true
    },
    height: {
        type: String,
        default: '250px'
    },
    markerColor: {
        type: String,
        default: 'blue' // In case we want custom icons later
    }
});

const mapContainer = ref(null);
let map = null;
let marker = null;

onMounted(() => {
    if (props.lat && props.lng) {
        initMap();
    }
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
    }
});

const initMap = () => {
    const latNum = parseFloat(props.lat);
    const lngNum = parseFloat(props.lng);

    map = L.map(mapContainer.value, {
        center: [latNum, lngNum],
        zoom: 16,
        scrollWheelZoom: false, // Prevent accidental scrolling while scrolling page
        dragging: !L.Browser.mobile // Disable dragging on mobile to avoid trapping scroll
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    marker = L.marker([latNum, lngNum]).addTo(map);
};

// Re-center if props wildly change
watch(() => [props.lat, props.lng], ([newLat, newLng]) => {
    if (newLat && newLng && map) {
        const latNum = parseFloat(newLat);
        const lngNum = parseFloat(newLng);
        marker.setLatLng([latNum, lngNum]);
        map.setView([latNum, lngNum], 16);
    } else if (newLat && newLng && !map) {
        initMap();
    }
});
</script>
