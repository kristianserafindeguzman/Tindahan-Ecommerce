import { ref } from 'vue'
import { api } from '@/boot/axios'
import { useLanguage } from './useLanguage'

// The categories table has no icon column, so each category name maps to a filled icon and tone here, with a generic fallback.
const CATEGORY_STYLES = {
  'Cooking Essentials': { icon: 'restaurant', tone: 'amber' },
  'Beverages': { icon: 'local_drink', tone: 'blue' },
  'Snacks & Sweets': { icon: 'fastfood', tone: 'orange' },
  'Personal Care': { icon: 'spa', tone: 'rose' },
  'Laundry & Cleaning': { icon: 'local_laundry_service', tone: 'teal' },
  'Others': { icon: 'category', tone: 'brand' },
  'Rice & Grains': { icon: 'rice_bowl', tone: 'amber' },
  'Canned & Packaged Foods': { icon: 'inventory_2', tone: 'teal' },
  'Instant Noodles & Pasta': { icon: 'ramen_dining', tone: 'orange' },
  'Condiments & Sauces': { icon: 'soup_kitchen', tone: 'amber' },
  'Chilled & Processed Foods': { icon: 'kitchen', tone: 'blue' },
  'Coffee, Milk & Breakfast Drinks': { icon: 'local_cafe', tone: 'amber' },
  'Bread & Bakery': { icon: 'bakery_dining', tone: 'amber' },
  'Candies & Chocolates': { icon: 'cake', tone: 'rose' },
  'Oral Care': { icon: 'clean_hands', tone: 'blue' },
  'Baby Care': { icon: 'child_friendly', tone: 'rose' },
  'OTC Medicine & First Aid': { icon: 'medical_services', tone: 'teal' },
  'School & Office Supplies': { icon: 'school', tone: 'blue' },
  'Household Supplies': { icon: 'home', tone: 'teal' },
  'Batteries, Lighting & Electrical': { icon: 'battery_full', tone: 'orange' },
  'Mobile Load & E-Services': { icon: 'smartphone', tone: 'brand' },
  'Cigarettes & Tobacco': { icon: 'smoking_rooms', tone: 'brand' }
}

const DEFAULT_STYLE = { icon: 'category', tone: 'brand' }

// A category's icon and tone, shared with the vendor Categories page so both sides show a category the same way.
export const categoryStyle = name => CATEGORY_STYLES[name] || DEFAULT_STYLE

// Shared like useProducts, so the header and the page no longer fetch the same categories twice.
const categories = ref([])
const loading = ref(false)

// No coordinates in this request, so concurrent callers always share.
let inFlight = null

const load = async () => {
  try {
    const { data } = await api.get('/categories')
    const mapped = (data || []).map((category) => ({
      id: category.category_id,
      label: category.category_name,
      // The description lists what belongs in the category ("Softdrinks & Water, Coffee & Tea, ..."),
      // which is what search logging matches a query against when no product name does.
      description: category.description || '',
      icon: categoryStyle(category.category_name).icon,
      tone: categoryStyle(category.category_name).tone
    }))

    // Others is a catch-all, so it always sorts last instead of alphabetically.
    categories.value = mapped.sort((a, b) => {
      if (a.label === 'Others') return 1
      if (b.label === 'Others') return -1
      return 0
    })
  } catch (error) {
    console.error('Failed to load categories', error)
    categories.value = []
  }
}

export function useCategories() {
  const fetchCategories = () => {
    if (inFlight) return inFlight

    loading.value = true

    const promise = load().finally(() => {
      if (inFlight === promise) {
        inFlight = null
        loading.value = false
      }
    })
    inFlight = promise

    return promise
  }

  return { categories, loading, fetchCategories }
}

// ---------------------------------------------------------------------------
// Display translations for the categories this app seeds.
//
// The database keeps category_name in English as the stable identifier used for icons, routing,
// filtering and the API. Only what a person reads is translated, and only for seeded categories:
// a category a vendor created shows exactly the name and description they typed.
//
// Shaped for useLanguage(), the vendor side's existing translation mechanism, so the Categories
// page and the Add/Edit Product selectors all read from this one dictionary.
// ---------------------------------------------------------------------------
export const SEEDED_CATEGORY_KEYS = {
  'Cooking Essentials': 'CookingEssentials',
  'Beverages': 'Beverages',
  'Snacks & Sweets': 'SnacksSweets',
  'Personal Care': 'PersonalCare',
  'Laundry & Cleaning': 'LaundryCleaning',
  'Others': 'Others',
  'Rice & Grains': 'RiceGrains',
  'Canned & Packaged Foods': 'CannedPackaged',
  'Instant Noodles & Pasta': 'InstantNoodles',
  'Condiments & Sauces': 'CondimentsSauces',
  'Chilled & Processed Foods': 'ChilledProcessed',
  'Coffee, Milk & Breakfast Drinks': 'CoffeeMilk',
  'Bread & Bakery': 'BreadBakery',
  'Candies & Chocolates': 'CandiesChocolates',
  'Oral Care': 'OralCare',
  'Baby Care': 'BabyCare',
  'OTC Medicine & First Aid': 'OtcMedicine',
  'School & Office Supplies': 'SchoolOffice',
  'Household Supplies': 'HouseholdSupplies',
  'Batteries, Lighting & Electrical': 'BatteriesElectrical',
  'Mobile Load & E-Services': 'MobileLoad',
  'Cigarettes & Tobacco': 'CigarettesTobacco'
}

export const categoryMessages = {
  en: {
    nameCookingEssentials: 'Cooking Essentials',
    descCookingEssentials: 'Cooking oil, sugar, salt, pepper, spices, seasonings, garlic, onion, and basic cooking ingredients.',
    nameBeverages: 'Beverages',
    descBeverages: 'Bottled water, softdrinks, juice, energy drinks, sports drinks, and alcoholic drinks.',
    nameSnacksSweets: 'Snacks & Sweets',
    descSnacksSweets: 'Chips, crackers, biscuits, cookies, wafers, nuts, and chicharon.',
    namePersonalCare: 'Personal Care',
    descPersonalCare: 'Bath soap, body wash, shampoo, conditioner, deodorant, razors, sanitary napkins, and cotton buds.',
    nameLaundryCleaning: 'Laundry & Cleaning',
    descLaundryCleaning: 'Laundry detergent, fabric conditioner, bleach, dishwashing liquid, and toilet and floor cleaners.',
    nameOthers: 'Others',
    descOthers: 'Miscellaneous items not covered by the other categories.',
    nameRiceGrains: 'Rice & Grains',
    descRiceGrains: 'Rice, corn, oats, and other grains sold per kilo or per pack.',
    nameCannedPackaged: 'Canned & Packaged Foods',
    descCannedPackaged: 'Sardines, canned tuna, corned beef, luncheon meat, and other canned goods.',
    nameInstantNoodles: 'Instant Noodles & Pasta',
    descInstantNoodles: 'Instant noodles, cup noodles, pancit canton, bihon, sotanghon, mami, and pasta.',
    nameCondimentsSauces: 'Condiments & Sauces',
    descCondimentsSauces: 'Soy sauce, vinegar, fish sauce, ketchup, mayonnaise, seasoning mixes, and spreads.',
    nameChilledProcessed: 'Chilled & Processed Foods',
    descChilledProcessed: 'Hotdog, longganisa, tocino, cheese, margarine, and eggs.',
    nameCoffeeMilk: 'Coffee, Milk & Breakfast Drinks',
    descCoffeeMilk: 'Coffee, 3-in-1 coffee sachets, powdered and condensed milk, and chocolate drinks.',
    nameBreadBakery: 'Bread & Bakery',
    descBreadBakery: 'Pandesal, sliced bread, loaf bread, buns, and other bakery items.',
    nameCandiesChocolates: 'Candies & Chocolates',
    descCandiesChocolates: 'Candies, chocolates, mints, and other sweets.',
    nameOralCare: 'Oral Care',
    descOralCare: 'Toothpaste, toothbrushes, mouthwash, and dental floss.',
    nameBabyCare: 'Baby Care',
    descBabyCare: 'Diapers, baby soap, baby powder, baby wipes, and baby milk.',
    nameOtcMedicine: 'OTC Medicine & First Aid',
    descOtcMedicine: 'Over-the-counter medicines, pain relievers, cold and flu tablets, antacids, bandages, antiseptics, alcohol, and face masks.',
    nameSchoolOffice: 'School & Office Supplies',
    descSchoolOffice: 'Ballpens, pencils, paper, notebooks, envelopes, and other school and office supplies.',
    nameHouseholdSupplies: 'Household Supplies',
    descHouseholdSupplies: 'Tissue, trash bags, plastic bags, insect spray, brooms, and other household needs.',
    nameBatteriesElectrical: 'Batteries, Lighting & Electrical',
    descBatteriesElectrical: 'Batteries, light bulbs, candles, matches, extension cords, and small electrical supplies.',
    nameMobileLoad: 'Mobile Load & E-Services',
    descMobileLoad: 'Prepaid load, e-load, mobile data promos, and e-wallet cash-in services.',
    nameCigarettesTobacco: 'Cigarettes & Tobacco',
    descCigarettesTobacco: 'Cigarettes sold per stick or per pack, and other tobacco products.',
    // Labels of the category field itself, shared by the Add and Edit Product modals.
    uiCategory: 'Category',
    uiChooseCategory: 'Choose a category',
    uiChooseCategoryRule: 'Choose a category.',
    uiCategoryGuideEmpty: 'Select a category to see its description.',
    uiCategoryGuideNone: 'No description available for this category.'
  },
  ph: {
    nameCookingEssentials: 'Mga Sangkap sa Pagluluto',
    descCookingEssentials: 'Mantika, asukal, asin, paminta, pampalasa, bawang, sibuyas, at iba pang panluto.',
    nameBeverages: 'Mga Inumin',
    descBeverages: 'Bottled water, softdrinks, juice, energy drinks, sports drinks, at mga alak.',
    nameSnacksSweets: 'Tsitsirya at Matatamis',
    descSnacksSweets: 'Chips, crackers, biskwit, cookies, wafer, mani, at chicharon.',
    namePersonalCare: 'Pangangalaga sa Sarili',
    descPersonalCare: 'Sabon, body wash, shampoo, conditioner, deodorant, pang-ahit, napkin, at cotton buds.',
    nameLaundryCleaning: 'Panlaba at Panlinis',
    descLaundryCleaning: 'Sabong panlaba, fabric conditioner, bleach, panghugas ng pinggan, at panlinis ng banyo at sahig.',
    nameOthers: 'Iba Pa',
    descOthers: 'Mga bagay na hindi kasama sa ibang kategorya.',
    nameRiceGrains: 'Bigas at Butil',
    descRiceGrains: 'Bigas, mais, oats, at iba pang butil kada kilo o kada pack.',
    nameCannedPackaged: 'De-lata at Nakabalot na Pagkain',
    descCannedPackaged: 'Sardinas, tuna, corned beef, luncheon meat, at iba pang de-lata.',
    nameInstantNoodles: 'Instant Noodles at Pasta',
    descInstantNoodles: 'Instant noodles, cup noodles, pancit canton, bihon, sotanghon, mami, at pasta.',
    nameCondimentsSauces: 'Panimpla at Sarsa',
    descCondimentsSauces: 'Toyo, suka, patis, ketchup, mayonnaise, pampalasa, at mga palaman.',
    nameChilledProcessed: 'Chilled at Processed na Pagkain',
    descChilledProcessed: 'Hotdog, longganisa, tocino, keso, margarine, at itlog.',
    nameCoffeeMilk: 'Kape, Gatas at Pang-almusal',
    descCoffeeMilk: 'Kape, 3-in-1 na kape, gatas na powdered at condensed, at chocolate drink.',
    nameBreadBakery: 'Tinapay at Panaderya',
    descBreadBakery: 'Pandesal, tinapay na hiwa, tasty, buns, at iba pang gawa sa panaderya.',
    nameCandiesChocolates: 'Kendi at Tsokolate',
    descCandiesChocolates: 'Kendi, tsokolate, mints, at iba pang matatamis.',
    nameOralCare: 'Pangangalaga sa Ngipin',
    descOralCare: 'Toothpaste, sipilyo, mouthwash, at dental floss.',
    nameBabyCare: 'Pangangailangan ng Sanggol',
    descBabyCare: 'Diaper, sabon at powder ng baby, baby wipes, at gatas ng baby.',
    nameOtcMedicine: 'Gamot at First Aid',
    descOtcMedicine: 'Gamot na walang reseta, pampawala ng sakit, gamot sa sipon at trangkaso, antacid, benda, antiseptic, alkohol, at face mask.',
    nameSchoolOffice: 'Gamit sa Eskwela at Opisina',
    descSchoolOffice: 'Bolpen, lapis, papel, notebook, sobre, at iba pang gamit sa eskwela at opisina.',
    nameHouseholdSupplies: 'Gamit sa Bahay',
    descHouseholdSupplies: 'Tissue, trash bag, plastic, pamatay-insekto, walis, at iba pang pangangailangan sa bahay.',
    nameBatteriesElectrical: 'Baterya, Ilaw at Kuryente',
    descBatteriesElectrical: 'Baterya, bombilya, kandila, posporo, extension cord, at maliliit na gamit sa kuryente.',
    nameMobileLoad: 'Load at E-Services',
    descMobileLoad: 'Prepaid load, e-load, data promo, at e-wallet cash-in.',
    nameCigarettesTobacco: 'Sigarilyo at Tabako',
    descCigarettesTobacco: 'Sigarilyo kada stick o kada pack, at iba pang produktong tabako.',
    uiCategory: 'Kategorya',
    uiChooseCategory: 'Pumili ng kategorya',
    uiChooseCategoryRule: 'Pumili ng kategorya.',
    uiCategoryGuideEmpty: 'Pumili ng kategorya para makita ang description nito.',
    uiCategoryGuideNone: 'Walang description ang kategoryang ito.'
  }
}

// The English description each seeded category ships with. A stored description still equal to
// this has never been edited, so it is safe to show the translated version instead.
const SEEDED_DESCRIPTIONS = {
  'Cooking Essentials': 'Cooking oil, sugar, salt, pepper, spices, seasonings, garlic, onion, and basic cooking ingredients.',
  'Beverages': 'Bottled water, softdrinks, juice, energy drinks, sports drinks, and alcoholic drinks.',
  'Snacks & Sweets': 'Chips, crackers, biscuits, cookies, wafers, nuts, and chicharon.',
  'Personal Care': 'Bath soap, body wash, shampoo, conditioner, deodorant, razors, sanitary napkins, and cotton buds.',
  'Laundry & Cleaning': 'Laundry detergent, fabric conditioner, bleach, dishwashing liquid, and toilet and floor cleaners.',
  'Others': 'Miscellaneous items not covered by the other categories.',
  'Rice & Grains': 'Rice, corn, oats, and other grains sold per kilo or per pack.',
  'Canned & Packaged Foods': 'Sardines, canned tuna, corned beef, luncheon meat, and other canned goods.',
  'Instant Noodles & Pasta': 'Instant noodles, cup noodles, pancit canton, bihon, sotanghon, mami, and pasta.',
  'Condiments & Sauces': 'Soy sauce, vinegar, fish sauce, ketchup, mayonnaise, seasoning mixes, and spreads.',
  'Chilled & Processed Foods': 'Hotdog, longganisa, tocino, cheese, margarine, and eggs.',
  'Coffee, Milk & Breakfast Drinks': 'Coffee, 3-in-1 coffee sachets, powdered and condensed milk, and chocolate drinks.',
  'Bread & Bakery': 'Pandesal, sliced bread, loaf bread, buns, and other bakery items.',
  'Candies & Chocolates': 'Candies, chocolates, mints, and other sweets.',
  'Oral Care': 'Toothpaste, toothbrushes, mouthwash, and dental floss.',
  'Baby Care': 'Diapers, baby soap, baby powder, baby wipes, and baby milk.',
  'OTC Medicine & First Aid': 'Over-the-counter medicines, pain relievers, cold and flu tablets, antacids, bandages, antiseptics, alcohol, and face masks.',
  'School & Office Supplies': 'Ballpens, pencils, paper, notebooks, envelopes, and other school and office supplies.',
  'Household Supplies': 'Tissue, trash bags, plastic bags, insect spray, brooms, and other household needs.',
  'Batteries, Lighting & Electrical': 'Batteries, light bulbs, candles, matches, extension cords, and small electrical supplies.',
  'Mobile Load & E-Services': 'Prepaid load, e-load, mobile data promos, and e-wallet cash-in services.',
  'Cigarettes & Tobacco': 'Cigarettes sold per stick or per pack, and other tobacco products.'
}

export function useCategoryLabels() {
  const { t } = useLanguage(categoryMessages)

  const keyFor = name => SEEDED_CATEGORY_KEYS[(name || '').trim()]

  const categoryLabel = name => {
    const key = keyFor(name)
    return key ? t(`name${key}`) : (name || '')
  }

  const categoryDescription = (name, storedDescription) => {
    const stored = (storedDescription || '').trim()
    if (!stored) return ''

    const key = keyFor(name)
    // Compared against the English text so a description a vendor edited is never replaced.
    return key && stored === SEEDED_DESCRIPTIONS[(name || '').trim()] ? t(`desc${key}`) : stored
  }

  return { t, categoryLabel, categoryDescription }
}
