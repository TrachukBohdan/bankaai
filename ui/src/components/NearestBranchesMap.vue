<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import type { Branch } from '@/types/api'

const props = defineProps<{
  branches: Branch[]
  center?: { lat: number; lng: number }
}>()

const mapEl = ref<HTMLElement | null>(null)
let map: L.Map | null = null
let markers: L.LayerGroup | null = null

onMounted(() => {
  if (!mapEl.value) return
  const lat = props.center?.lat ?? 50.45
  const lng = props.center?.lng ?? 30.52
  map = L.map(mapEl.value).setView([lat, lng], 12)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
  }).addTo(map)
  markers = L.layerGroup().addTo(map)
  renderMarkers()
})

onUnmounted(() => {
  map?.remove()
  map = null
})

watch(() => props.branches, renderMarkers, { deep: true })

function renderMarkers(): void {
  if (!map || !markers) return
  markers.clearLayers()
  props.branches.forEach((b) => {
    const m = L.marker([b.lat, b.lng]).bindPopup(
      `<strong>${b.name}</strong><br>${b.address}<br>${b.distance_m ? Math.round(b.distance_m) + ' m' : ''}`,
    )
    markers!.addLayer(m)
  })
  if (props.branches.length > 0) {
    const bounds = L.latLngBounds(props.branches.map((b) => [b.lat, b.lng] as [number, number]))
    map.fitBounds(bounds.pad(0.2))
  }
}
</script>

<template>
  <div ref="mapEl" class="map-container" />
</template>
