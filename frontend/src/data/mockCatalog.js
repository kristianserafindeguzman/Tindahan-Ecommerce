// Shared mock catalog — replace with real /products and /stores endpoints once they exist.
// Used by ConsumerProducts.vue, ConsumerStores.vue, ConsumerSearch.vue, and SiteHeader.vue's search suggestions.

export const MOCK_ALL_PRODUCTS = [
  { id: 1, name: 'Coca-Cola 1.5L', category: 'Beverages', price: 75, distance: '2 m', store: 'Leslie Store', inStock: true },
  { id: 2, name: 'Lucky Me Pancit Canton Kalamansi 80g', category: 'Cooking Essentials', price: 12, distance: '3 m', store: 'Jmzhai Sari Sari Store', inStock: true },
  { id: 3, name: 'Del Monte Tuna 155g', category: 'Cooking Essentials', price: 38, distance: '3 m', store: 'Leslie Store', inStock: true },
  { id: 4, name: 'Gardenia Bread', category: 'Snacks & Sweets', price: 46, distance: '4 m', store: 'Sol A Sari Sari Store', inStock: true },
  { id: 5, name: 'Surf Powder Detergent', category: 'Laundry & Cleaning', price: 65, distance: '4 m', store: 'Jmzhai Sari Sari Store', inStock: false },
  { id: 6, name: 'Alaska Evaporada 370ml', category: 'Cooking Essentials', price: 25, distance: '2 m', store: 'Leslie Store', inStock: true },
  { id: 7, name: 'Kopiko Blanca Twin Pack', category: 'Beverages', price: 22, distance: '5 m', store: 'Sol A Sari Sari Store', inStock: true },
  { id: 8, name: 'Sanicare Bath Soap', category: 'Personal Care', price: 15, distance: '3 m', store: 'Jmzhai Sari Sari Store', inStock: true },
  { id: 9, name: 'Jasmine Rice 1kg', category: 'Cooking Essentials', price: 52, distance: '2 m', store: 'Leslie Store', inStock: true },
  { id: 10, name: 'Selecta Ice Cream 1.3L', category: 'Snacks & Sweets', price: 99, distance: '5 m', store: 'Sol A Sari Sari Store', inStock: true },
  { id: 11, name: 'Piattos Sour Cream & Onion', category: 'Snacks & Sweets', price: 15, distance: '4 m', store: 'Jmzhai Sari Sari Store', inStock: true },
  { id: 12, name: 'Century Tuna Flakes in Oil 155g', category: 'Cooking Essentials', price: 35, distance: '3 m', store: 'Leslie Store', inStock: true }
]

export const MOCK_STORES = [
  { id: 1, name: 'Leslie Store', isOpen: true, closesAt: '10:00 pm', distance: '5 m', distanceMeters: 5 },
  { id: 2, name: 'Jmzhai Sari Sari Store', isOpen: true, closesAt: '9:00 pm', distance: '3 m', distanceMeters: 3 },
  { id: 3, name: 'Sol A Sari Sari Store', isOpen: false, closesAt: '8:00 pm', distance: '4 m', distanceMeters: 4 },
  { id: 4, name: "David's Sari-Sari Store", isOpen: true, closesAt: '9:30 pm', distance: '6 m', distanceMeters: 6 },
  { id: 5, name: "Ate Liza's Store", isOpen: true, closesAt: '9:00 pm', distance: '2 m', distanceMeters: 2 },
  { id: 6, name: 'Kuya Pedro Store', isOpen: true, closesAt: '8:30 pm', distance: '4 m', distanceMeters: 4 },
  { id: 7, name: 'Mang Dindo Store', isOpen: false, closesAt: '7:00 pm', distance: '7 m', distanceMeters: 7 },
  { id: 8, name: "Nena's Store", isOpen: true, closesAt: '10:00 pm', distance: '8 m', distanceMeters: 8 }
]
