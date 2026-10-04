// Leaflet's default marker icon resolves its image path from Leaflet's own script URL at runtime, which a production build breaks, leaving a broken image where the pin should be.
// Importing this module points the default icon at the bundled image files. The same URLs are exported for maps that build their own L.icon, so no map depends on a CDN being reachable.
import L from 'leaflet'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

// The default icon overrides _getIconUrl to prefix its own guessed image path onto each URL, which would corrupt the hashed paths the bundler emits.
// Deleting the override falls back to the base Icon lookup, so the URLs below are used exactly as they are.
delete L.Icon.Default.prototype._getIconUrl

L.Icon.Default.mergeOptions({
  iconUrl: markerIcon,
  iconRetinaUrl: markerIcon2x,
  shadowUrl: markerShadow
})

export const markerIconUrl = markerIcon
export const markerIcon2xUrl = markerIcon2x
export const markerShadowUrl = markerShadow
