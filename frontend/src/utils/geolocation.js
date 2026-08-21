export function getCurrentPosition(options = { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }) {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation is not supported by this browser.'))
      return
    }

    navigator.geolocation.getCurrentPosition(
      (position) => resolve({ latitude: position.coords.latitude, longitude: position.coords.longitude }),
      (error) => reject(error),
      options
    )
  })
}

export function formatAddress(data) {
  if (!data || !data.address) {
    return data?.display_name || 'Address unavailable'
  }

  const address = data.address

  const parts = [
    address.house_number,
    address.road,
    address.neighbourhood,
    address.suburb,
    address.village,
    address.town,
    address.city,
    address.city_district,
    address.state,
    address.country
  ]

  return parts
    .filter(Boolean)
    .filter((value, index, array) => array.indexOf(value) === index)
    .join(', ')
}

export async function reverseGeocode(latitude, longitude) {
  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`,
      { headers: { Accept: 'application/json' } }
    )

    if (!response.ok) {
      throw new Error('Reverse geocoding failed')
    }

    const data = await response.json()
    return formatAddress(data)
  } catch (error) {
    console.error('Address lookup failed:', error)
    return 'Address unavailable'
  }
}
