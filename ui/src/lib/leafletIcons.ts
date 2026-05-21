import L from 'leaflet'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

let configured = false

/**
 * Leaflet defaults to /marker-icon.png at the site root. Vite must import assets
 * so they land in /assets/* with hashed names (fixes 404s behind prod nginx).
 */
export function configureLeafletIcons(): void {
  if (configured) {
    return
  }

  // Leaflet 1.x internal hook that overrides mergeOptions if present.
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const proto = L.Icon.Default.prototype as any
  if (proto._getIconUrl) {
    delete proto._getIconUrl
  }

  L.Icon.Default.mergeOptions({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIcon2x,
    shadowUrl: markerShadow,
  })

  configured = true
}
