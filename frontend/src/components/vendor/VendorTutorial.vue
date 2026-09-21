<template>
  <!-- Welcome, shown once to an approved vendor on their first visit to the dashboard. -->
  <q-dialog v-model="showWelcome" persistent transition-show="scale" transition-hide="scale">
    <q-card class="tour-dialog">
      <q-card-section class="tour-content">
        <img :src="welcomeLogo" alt="Tindahan" class="tour-logo" />
        <div class="tour-title">{{ t('welcomeTitle') }}</div>
        <p class="tour-message">{{ t('welcomeBody') }}</p>
        <p class="tour-note">{{ t('welcomeNote') }}</p>
      </q-card-section>

      <q-card-actions class="tour-actions">
        <q-btn outline no-caps :label="t('skipBtn')" class="tour-btn tour-btn--secondary" @click="skipFromWelcome" />
        <q-btn unelevated no-caps :label="t('startBtn')" class="tour-btn tour-btn--primary" @click="startFromWelcome" />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <!-- Closing card, shown after the last step. -->
  <q-dialog v-model="showFinish" persistent transition-show="scale" transition-hide="scale">
    <q-card class="tour-dialog">
      <q-card-section class="tour-content">
        <div class="tour-done-icon"><q-icon name="o_check_circle" size="26px" /></div>
        <div class="tour-title">{{ t('doneTitle') }}</div>
        <p class="tour-message">{{ t('doneBody') }}</p>
      </q-card-section>

      <q-card-actions class="tour-actions tour-actions--stacked">
        <q-btn unelevated no-caps :label="t('addFirstBtn')" class="tour-btn tour-btn--primary" @click="goAddProduct" />
        <q-btn outline no-caps :label="t('dashboardBtn')" class="tour-btn tour-btn--secondary" @click="goDashboard" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { driver } from 'driver.js'
import 'driver.js/dist/driver.css'
import { useLanguage } from '@/composables/useLanguage'
import { useVendorTutorial, TUTORIAL_COMPLETED, TUTORIAL_SKIPPED } from '@/composables/useVendorTutorial'
import logoDark from '@/assets/tindahan-black.png'
import logoLight from '@/assets/tindahan-logo.png'

const $q = useQuasar()

// The black wordmark would disappear into the navy card the vendor's dark mode gives this
// dialog, so dark mode gets the light version the red header already uses.
const welcomeLogo = computed(() => ($q.dark.isActive ? logoLight : logoDark))
const route = useRoute()
const router = useRouter()

const tutorialDict = {
  en: {
    welcomeTitle: 'Welcome to Tindahan!',
    welcomeBody: 'Let us walk you through your whole store — the dashboard, your products, orders, sales reports and settings.',
    welcomeNote: 'About three minutes. You can leave at any point and pick it up again later.',
    startBtn: 'Start Tutorial',
    skipBtn: 'Skip',

    secDashboard: 'Dashboard',
    secProducts: 'Products',
    secCategories: 'Categories',
    secOrders: 'Orders',
    secCustomers: 'Customers',
    secSales: 'Sales Reports',
    secSettings: 'Store Settings',

    s1Title: 'Your dashboard',
    s1Body: 'Everything starts here. This link brings you back to the overview from anywhere in your store.',
    s2Title: 'Store status',
    s2Body: 'Your greeting, today’s date, and whether your store is open right now based on the hours you set. View Store shows customers exactly what they see.',
    s3Title: 'Order counts',
    s3Body: 'How many orders are waiting, being prepared, ready for pickup, and completed. Check this first each morning.',
    s4Title: 'Revenue',
    s4Body: 'Your earnings over time. Switch the period to compare a day, a week or a month.',
    s5Title: 'Demand forecast',
    s5Body: 'The system predicts which products will sell most in the coming days, so you can restock before you run out.',
    s6Title: 'Recent orders',
    s6Body: 'Your five newest orders. Tap one to open it, or View All to see the full list.',

    s7Title: 'Product List',
    s7Body: 'Your inventory lives here — every item you sell, with its price, stock and category.',
    s8Title: 'Add Product',
    s8Body: 'Use this to list a new item: name, price, stock, category and a photo. Do this first so customers have something to buy.',
    s9Title: 'Stock insights',
    s9Body: 'A restock alert for what is running low, the trend for what is about to move, and your current best performer.',
    s10Title: 'Finding a product',
    s10Body: 'Search by name, or filter by category, stock level and price. Tap any product to edit it, change its stock, or remove it.',

    s11Title: 'Categories',
    s11Body: 'Group your products — snacks, drinks, canned goods — so customers can browse your store. Add Category creates a new one; Export saves a report.',

    s12Title: 'Order List',
    s12Body: 'Every order a customer places arrives here.',
    s13Title: 'Order status',
    s13Body: 'Filter by stage, and see at a glance how many are in each. Placed orders need your attention first.',
    s14Title: 'Working an order',
    s14Body: 'Search, sort or filter, then tap an order to open it and move it along: preparing, ready for pickup, then picked up. Export saves the list as a report.',

    s15Title: 'Customers',
    s15Body: 'Your regulars, and everything each one has ordered from you. Pick a name to see their full history.',

    s16Title: 'Sales Reports',
    s16Body: 'The numbers behind your store — how much you earned and what sold.',
    s17Title: 'Revenue for the period',
    s17Body: 'Your total, the orders behind it, and items sold. The date button at the top right switches between a single day and all time.',
    s18Title: 'Performance',
    s18Body: 'Average order value, how often orders get cancelled, and your best-selling category.',
    s19Title: 'Record a walk-in sale',
    s19Body: 'Sold something over the counter? Record it here so your stock and your reports stay correct.',

    s20Title: 'Store Settings',
    s20Body: 'Your store and account settings all live on this page.',
    s21Title: 'Your details',
    s21Body: 'Your own name, mobile number and email — the contact details tied to your account.',
    s22Title: 'Store photo',
    s22Body: 'The storefront photo customers see first. A clear, well-lit shot makes a real difference.',
    s23Title: 'Store details',
    s23Body: 'Your store name, address with its map pin, and opening hours. The hours here are what set your store to open or closed on the dashboard.',
    s24Title: 'Security',
    s24Body: 'Change your password here. Do it regularly, and never share it.',
    s25Title: 'Help & Support',
    s25Body: 'Replay Tutorial brings this whole walkthrough back whenever you need it.',

    doneTitle: 'You’re all set!',
    doneBody: 'That is your whole store. Add your first product to start selling, or head back to the dashboard.',
    addFirstBtn: 'Add Your First Product',
    dashboardBtn: 'Go to Dashboard',

    nextBtn: 'Next',
    backBtn: 'Back',
    finishBtn: 'Finish',
    progress: '{{current}} of {{total}}'
  },
  ph: {
    welcomeTitle: 'Maligayang pagdating sa Tindahan!',
    welcomeBody: 'Libutin natin ang buong tindahan mo — ang dashboard, mga paninda, order, benta, at settings.',
    welcomeNote: 'Mga tatlong minuto lang. Pwede kang huminto anumang oras at ituloy mamaya.',
    startBtn: 'Simulan ang Tutorial',
    skipBtn: 'Laktawan',

    secDashboard: 'Dashboard',
    secProducts: 'Mga Paninda',
    secCategories: 'Mga Kategorya',
    secOrders: 'Mga Order',
    secCustomers: 'Mga Customer',
    secSales: 'Mga Benta',
    secSettings: 'Settings ng Tindahan',

    s1Title: 'Ang dashboard mo',
    s1Body: 'Dito nagsisimula ang lahat. Ibabalik ka ng link na ito sa buod kahit saan ka man sa tindahan.',
    s2Title: 'Status ng tindahan',
    s2Body: 'Ang bati sa iyo, ang petsa ngayon, at kung bukas ba ang tindahan mo base sa oras na itinakda mo. Ipinapakita ng View Store ang nakikita ng mga customer.',
    s3Title: 'Bilang ng order',
    s3Body: 'Ilan ang naghihintay, inihahanda, pwede nang kunin, at tapos na. Ito ang tingnan mo tuwing umaga.',
    s4Title: 'Kita',
    s4Body: 'Ang kita mo sa paglipas ng panahon. Palitan ang period para ikumpara ang araw, linggo, o buwan.',
    s5Title: 'Demand forecast',
    s5Body: 'Hinuhulaan ng sistema kung anong paninda ang mabebenta sa mga susunod na araw, para makapag-stock ka bago maubos.',
    s6Title: 'Mga bagong order',
    s6Body: 'Ang lima mong pinakabagong order. Pindutin ang isa para buksan, o View All para sa buong listahan.',

    s7Title: 'Listahan ng Paninda',
    s7Body: 'Nandito ang imbentaryo mo — lahat ng benta mo, kasama ang presyo, stock, at kategorya.',
    s8Title: 'Magdagdag ng Paninda',
    s8Body: 'Gamitin ito para magdagdag ng bagong item: pangalan, presyo, stock, kategorya, at larawan. Gawin mo muna ito para may mabili ang customer.',
    s9Title: 'Insights sa stock',
    s9Body: 'Babala kung ano ang paubos na, ang trend kung ano ang bibilis mabenta, at ang kasalukuyang pinakamabenta mo.',
    s10Title: 'Paghahanap ng paninda',
    s10Body: 'Maghanap sa pangalan, o mag-filter ayon sa kategorya, stock, at presyo. Pindutin ang paninda para i-edit, baguhin ang stock, o tanggalin.',

    s11Title: 'Mga Kategorya',
    s11Body: 'Pagsama-samahin ang paninda — meryenda, inumin, de lata — para madaling tingnan ng customer. Ang Add Category ay gumagawa ng bago; ang Export ay nagse-save ng report.',

    s12Title: 'Listahan ng Order',
    s12Body: 'Dito dumarating ang bawat order na ginagawa ng customer.',
    s13Title: 'Status ng order',
    s13Body: 'Mag-filter ayon sa yugto, at makikita agad kung ilan ang nasa bawat isa. Ang mga na-order na ang unahin mo.',
    s14Title: 'Pag-asikaso ng order',
    s14Body: 'Maghanap, mag-sort, o mag-filter, tapos pindutin ang order para buksan at usarin: inihahanda, pwede nang kunin, nakuha na. Nagse-save ng report ang Export.',

    s15Title: 'Mga Customer',
    s15Body: 'Ang mga suki mo, at lahat ng inorder nila sa iyo. Pumili ng pangalan para makita ang buong kasaysayan.',

    s16Title: 'Mga Benta',
    s16Body: 'Ang mga numero ng tindahan mo — kung magkano ang kinita at ano ang nabenta.',
    s17Title: 'Kita sa panahong ito',
    s17Body: 'Ang kabuuan mo, ang mga order na pinagmulan nito, at ang bilang ng naibenta. Ang date button sa itaas ang nagpapalit sa pagitan ng isang araw at ng lahat ng panahon.',
    s18Title: 'Performance',
    s18Body: 'Karaniwang halaga ng order, gaano kadalas kinakansela, at ang pinakamabenta mong kategorya.',
    s19Title: 'Itala ang walk-in na benta',
    s19Body: 'May naibenta sa counter? Itala mo dito para tama pa rin ang stock at mga report mo.',

    s20Title: 'Settings ng Tindahan',
    s20Body: 'Nandito sa page na ito ang lahat ng settings ng tindahan at account mo.',
    s21Title: 'Mga detalye mo',
    s21Body: 'Ang pangalan, numero, at email mo — ang contact na nakakabit sa account mo.',
    s22Title: 'Larawan ng tindahan',
    s22Body: 'Ang unang nakikita ng customer. Malaking tulong ang malinaw at maliwanag na kuha.',
    s23Title: 'Detalye ng tindahan',
    s23Body: 'Ang pangalan, address at map pin, at oras ng bukas. Ang oras dito ang nagtatakda kung bukas o sarado ka sa dashboard.',
    s24Title: 'Seguridad',
    s24Body: 'Dito pinapalitan ang password. Gawin mo ito paminsan-minsan, at huwag ipamigay.',
    s25Title: 'Tulong at Suporta',
    s25Body: 'Ibinabalik ng Ulitin ang Tutorial ang buong libot na ito kahit kailan mo kailanganin.',

    doneTitle: 'Handa ka na!',
    doneBody: 'Iyan ang buong tindahan mo. Magdagdag ng unang paninda para makapagsimula, o bumalik sa dashboard.',
    addFirstBtn: 'Idagdag ang Unang Paninda',
    dashboardBtn: 'Pumunta sa Dashboard',

    nextBtn: 'Susunod',
    backBtn: 'Balik',
    finishBtn: 'Tapos',
    progress: '{{current}} ng {{total}}'
  }
}

const { t } = useLanguage(tutorialDict)
const { replaySignal, shouldAutoStart, setTutorialState } = useVendorTutorial()

const showWelcome = ref(false)
const showFinish = ref(false)

// Below md the layout swaps the sidebar for the bottom tab bar, and page cards go
// full-width and tall. Each step therefore carries two targets: the desktop one, and a
// `compact` one that is usually the same card's header, so a phone highlights something
// that fits on screen instead of a card taller than the viewport.
const isCompact = computed(() => $q.screen.lt.md)

const DASHBOARD = '/vendor/dashboard'
const PRODUCTS = '/vendor/products/list'
const CATEGORIES = '/vendor/products/categories'
const ORDERS = '/vendor/orders/list'
const CUSTOMERS = '/vendor/orders/customers'
const SALES = '/vendor/sales'
const PROFILE = '/vendor/profile'

// Every step names its own route, so Next and Back both know where the step belongs and
// the tour can walk forward and backward across all seven pages.
const STEP_DEFS = [
  // ---- Dashboard ----
  { route: DASHBOARD, section: 'secDashboard', nav: 'dashboard', titleKey: 's1Title', bodyKey: 's1Body' },
  { route: DASHBOARD, section: 'secDashboard', wide: '[data-tour="dash-hero"]', titleKey: 's2Title', bodyKey: 's2Body', side: 'bottom', align: 'start' },
  { route: DASHBOARD, section: 'secDashboard', wide: '[data-tour="dash-kpis"]', titleKey: 's3Title', bodyKey: 's3Body', side: 'bottom', align: 'start' },
  { route: DASHBOARD, section: 'secDashboard', wide: '[data-tour="dash-revenue"]', compact: '[data-tour="dash-revenue-head"]', titleKey: 's4Title', bodyKey: 's4Body', side: 'top' },
  { route: DASHBOARD, section: 'secDashboard', wide: '[data-tour="dash-forecast"]', compact: '[data-tour="dash-forecast-head"]', titleKey: 's5Title', bodyKey: 's5Body', side: 'left', compactSide: 'bottom' },
  { route: DASHBOARD, section: 'secDashboard', wide: '[data-tour="dash-recent"]', compact: '[data-tour="dash-recent-head"]', titleKey: 's6Title', bodyKey: 's6Body', side: 'top' },

  // ---- Products ----
  { route: PRODUCTS, section: 'secProducts', nav: 'inventory', titleKey: 's7Title', bodyKey: 's7Body' },
  { route: PRODUCTS, section: 'secProducts', wide: '[data-tour="add-product"]', titleKey: 's8Title', bodyKey: 's8Body', side: 'bottom', align: 'end' },
  { route: PRODUCTS, section: 'secProducts', wide: '[data-tour="pl-insights"]', titleKey: 's9Title', bodyKey: 's9Body', side: 'bottom', align: 'start' },
  { route: PRODUCTS, section: 'secProducts', wide: '[data-tour="pl-list"]', compact: '[data-tour="pl-toolbar"]', titleKey: 's10Title', bodyKey: 's10Body', side: 'top', compactSide: 'bottom' },

  // ---- Categories ----
  { route: CATEGORIES, section: 'secCategories', wide: '[data-tour="cat-actions"]', titleKey: 's11Title', bodyKey: 's11Body', side: 'bottom', align: 'end' },

  // ---- Orders ----
  { route: ORDERS, section: 'secOrders', nav: 'orders', titleKey: 's12Title', bodyKey: 's12Body' },
  { route: ORDERS, section: 'secOrders', wide: '[data-tour="ol-chips"]', titleKey: 's13Title', bodyKey: 's13Body', side: 'bottom', align: 'start' },
  { route: ORDERS, section: 'secOrders', wide: '[data-tour="ol-list"]', compact: '[data-tour="ol-toolbar"]', titleKey: 's14Title', bodyKey: 's14Body', side: 'top', compactSide: 'bottom' },

  // ---- Customers ----
  { route: CUSTOMERS, section: 'secCustomers', wide: '[data-tour="co-directory"]', compact: '[data-tour="co-directory-head"]', titleKey: 's15Title', bodyKey: 's15Body', side: 'right', compactSide: 'bottom' },

  // ---- Sales reports ----
  { route: SALES, section: 'secSales', nav: 'analytics', titleKey: 's16Title', bodyKey: 's16Body' },
  { route: SALES, section: 'secSales', wide: '[data-tour="sr-hero"]', compact: '[data-tour="sr-hero-head"]', titleKey: 's17Title', bodyKey: 's17Body', side: 'bottom', align: 'start' },
  { route: SALES, section: 'secSales', wide: '[data-tour="sr-stats"]', titleKey: 's18Title', bodyKey: 's18Body', side: 'bottom', align: 'start' },
  // One attribute, two elements: the aside at >=lg, the header button below it. Only one exists.
  { route: SALES, section: 'secSales', wide: '[data-tour="sr-entry"]', titleKey: 's19Title', bodyKey: 's19Body', side: 'left', compactSide: 'bottom' },

  // ---- Store settings ----
  { route: PROFILE, section: 'secSettings', nav: 'settings', titleKey: 's20Title', bodyKey: 's20Body' },
  { route: PROFILE, section: 'secSettings', wide: '[data-tour="pf-personal"]', compact: '[data-tour="pf-personal-head"]', titleKey: 's21Title', bodyKey: 's21Body', side: 'bottom', align: 'start' },
  { route: PROFILE, section: 'secSettings', wide: '[data-tour="pf-photo"]', compact: '[data-tour="pf-photo-head"]', titleKey: 's22Title', bodyKey: 's22Body', side: 'left', compactSide: 'bottom' },
  { route: PROFILE, section: 'secSettings', wide: '[data-tour="pf-details"]', compact: '[data-tour="pf-details-head"]', titleKey: 's23Title', bodyKey: 's23Body', side: 'top', compactSide: 'bottom' },
  { route: PROFILE, section: 'secSettings', wide: '[data-tour="pf-security"]', titleKey: 's24Title', bodyKey: 's24Body', side: 'top' },
  { route: PROFILE, section: 'secSettings', wide: '[data-tour="pf-help"]', titleKey: 's25Title', bodyKey: 's25Body', side: 'top' }
]

let tour = null
let startTimer = null
let navigating = false

// A nav step points at the sidebar item on desktop and the bottom tab on phones and
// tablets; everything else uses its own wide/compact pair.
const selectorFor = (def) => {
  if (def.nav) return isCompact.value ? `[data-tour="mnav-${def.nav}"]` : `[data-tour="nav-${def.nav}"]`
  return (isCompact.value && def.compact) || def.wide
}

const sideFor = (def) => {
  if (def.nav) return isCompact.value ? 'top' : 'right'
  if (isCompact.value) return def.compactSide || def.side || 'bottom'
  return def.side || 'bottom'
}

// Driver only waits when the element is missing, so a generous budget costs nothing on a
// step whose target is already there. The first step of each page gets the longer one,
// since that page is still mounting and fetching when the step begins.
const waitFor = (index) =>
  index === 0 || STEP_DEFS[index].route !== STEP_DEFS[index - 1].route ? 4000 : 1000

const buildSteps = () =>
  STEP_DEFS.map((def, index) => ({
    element: () => document.querySelector(selectorFor(def)) || undefined,
    waitForElement: waitFor(index),
    popover: {
      title: t(def.titleKey),
      description: t(def.bodyKey),
      side: sideFor(def),
      align: def.align || 'center'
    }
  }))

const ensureRoute = async (path) => {
  if (!path || route.path === path) return
  await router.push(path)
  await nextTick()
}

const closeTour = () => {
  navigating = false
  if (tour) {
    tour.destroy()
    tour = null
  }
}

const finishTour = (state) => {
  closeTour()
  setTutorialState(state)
  if (state === TUTORIAL_COMPLETED) showFinish.value = true
}

const startTour = async () => {
  closeTour()

  tour = driver({
    showProgress: true,
    progressText: t('progress'),
    nextBtnText: t('nextBtn'),
    prevBtnText: t('backBtn'),
    doneBtnText: t('finishBtn'),
    allowClose: true,
    // A tap on the dimmed area ends the tour by default, which on a phone is mostly stray
    // taps. Only the X and Escape should end it, so overlay clicks do nothing.
    overlayClickBehavior: () => {},
    // The highlighted item stays look-only: clicking a nav link mid-tour would navigate
    // away and leave the popover pointing at nothing.
    disableActiveInteraction: true,
    overlayColor: '#111111',
    overlayOpacity: 0.62,
    smoothScroll: true,
    // Stated rather than left to the default: locking body scroll would also block driver's
    // own scrollIntoView on a target below the fold. The stage and popover are repositioned
    // on scroll, so letting the page move under the tour is safe.
    allowScroll: true,
    // Phones have less room to spare around a highlight than desktop does.
    stagePadding: isCompact.value ? 4 : 6,
    stageRadius: 10,
    popoverClass: 'tindahan-tour-popover',
    steps: buildSteps(),

    // Names the area the step belongs to, so 25 steps across seven pages stay legible.
    onPopoverRender: (popover, opts) => {
      const index = opts?.index ?? opts?.state?.activeIndex ?? tour?.getActiveIndex() ?? 0
      const def = STEP_DEFS[index]
      if (!def || !popover?.title?.parentElement) return
      const eyebrow = document.createElement('div')
      eyebrow.className = 'tour-eyebrow'
      eyebrow.textContent = t(def.section)
      popover.title.parentElement.insertBefore(eyebrow, popover.title)
    },

    // Both hooks await a route change before advancing, and driver leaves its buttons live
    // while they do. Without the guard, two quick taps on Next while a page is loading
    // would each move the tour on and the second step would be skipped unseen.
    onNextClick: async () => {
      if (!tour || navigating) return
      const next = (tour.getActiveIndex() ?? 0) + 1
      if (next >= STEP_DEFS.length) return
      navigating = true
      try {
        await ensureRoute(STEP_DEFS[next].route)
        tour?.moveNext()
      } catch {
        navigating = false
      }
    },

    onPrevClick: async () => {
      if (!tour || navigating) return
      const previous = (tour.getActiveIndex() ?? 0) - 1
      if (previous < 0) return
      navigating = true
      try {
        await ensureRoute(STEP_DEFS[previous].route)
        tour?.movePrevious()
      } catch {
        navigating = false
      }
    },

    // Clearing the guard here rather than after moveNext() returns. moveNext() returns
    // immediately when the step's element is not in the DOM yet — it schedules a wait of up
    // to waitForElement and leaves the previous popover on screen, buttons live. Releasing
    // on return would let a second tap during that window advance the tour again.
    onHighlighted: () => {
      navigating = false
    },

    // Reached only from the last step's button.
    onDoneClick: () => finishTour(TUTORIAL_COMPLETED),

    // The X and Escape both arrive here: leaving early counts as a skip.
    onDestroyStarted: () => finishTour(TUTORIAL_SKIPPED)
  })

  await ensureRoute(STEP_DEFS[0].route)
  await nextTick()
  tour.drive()
}

const startFromWelcome = () => {
  showWelcome.value = false
  // Quasar's hide transition runs 300ms; the tour waits it out so the dialog backdrop is
  // gone before driver's own overlay goes up and nothing flashes through two scrims.
  // Tracked so that logging out inside that window cannot leave driver's overlay on the
  // login page with no popover to close it.
  startTimer = setTimeout(startTour, 320)
}

const skipFromWelcome = () => {
  showWelcome.value = false
  setTutorialState(TUTORIAL_SKIPPED)
}

const goAddProduct = () => {
  showFinish.value = false
  router.push({ path: PRODUCTS, query: { add: '1' } })
}

const goDashboard = () => {
  showFinish.value = false
  router.push(DASHBOARD)
}

// Crossing the sidebar/bottom-bar breakpoint mid-tour invalidates the highlighted element
// and the chosen popover side, so the current step is re-driven against the targets the
// new layout actually renders.
watch(isCompact, () => {
  if (!tour?.isActive()) return
  const index = tour.getActiveIndex() ?? 0
  tour.setConfig({ ...tour.getConfig(), stagePadding: isCompact.value ? 4 : 6, steps: buildSteps() })
  tour.drive(index)
})

// The Replay button on the profile page bumps this counter.
watch(replaySignal, (value) => {
  if (value > 0) startTour()
})

// Offered on the dashboard only, which is where login lands a vendor. A vendor who deep-links
// straight to another page still gets it the first time they reach the dashboard.
const maybeAutoStart = () => {
  // `tour` rather than tour.isActive(): startTour builds the driver, then awaits a route
  // change before calling drive(). During that await the tour is not yet "active", and the
  // route change it just made lands here — which could raise the welcome dialog on top of
  // the tour that is about to start.
  if (showWelcome.value || tour) return
  if (route.path !== DASHBOARD) return
  if (!shouldAutoStart()) return
  showWelcome.value = true
}

watch(() => route.path, maybeAutoStart)

onMounted(async () => {
  await nextTick()
  maybeAutoStart()
})

onBeforeUnmount(() => {
  clearTimeout(startTimer)
  closeTour()
})
</script>

<style scoped>
/* Same dialog shell as the logout confirm and the login status cards. */
.tour-dialog {
  width: 400px;
  max-width: 90vw;

  /* Long Filipino copy on a short phone would otherwise push the buttons off the card. */
  max-height: 90vh;
  overflow-y: auto;

  border-radius: var(--r-xl);

  font-family: 'Roboto', Arial, sans-serif;
}

.tour-content {
  text-align: center;

  padding: 28px 28px 0;
}

/* The wordmark rather than a generic storefront glyph, so the welcome card opens with the
   same brand the vendor just logged into. Width-bounded and auto-height, so the asset's
   own proportions decide the rest.

   The PNG carries a band of transparent padding on every side, so its box is taller than
   the art inside it. The negative margins crop that back visually — without them the card
   reads as having two stacked gaps between the logo and the title. */
.tour-logo {
  display: block;

  width: 150px;
  max-width: 62%;
  height: auto;
  margin: -10px auto -4px;
}

.tour-done-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 52px;
  height: 52px;
  margin-bottom: 14px;

  border-radius: var(--r-xl);

  background: var(--c-success-tint);
  color: var(--c-success);
}

.tour-title {
  font-size: 19px;
  font-weight: 700;

  color: var(--c-text);

  margin-bottom: 10px;
}

.tour-message {
  font-size: var(--fs-sm);
  line-height: 1.6;

  color: var(--c-text-3);

  margin: 0;
}

/* How long it takes and that it can be left, under the main line in a quieter tone. */
.tour-note {
  margin: 10px 0 0;

  font-size: var(--fs-xs);
  line-height: 1.5;

  color: var(--c-muted);
}

.tour-actions {
  flex-wrap: nowrap;

  gap: 12px;

  padding: 20px 28px 28px;
}

/* The closing card's labels are long, so its two buttons stack instead of sharing a row. */
.tour-actions--stacked {
  flex-direction: column;
  align-items: stretch;
}

/* Quasar spaces neighbouring card buttons with its own margin, which would double the gap. */
.tour-actions .q-btn {
  flex: 1;

  height: 48px;
  margin: 0;

  border-radius: var(--r-sm);

  font-family: 'Roboto', Arial, sans-serif;
  font-size: var(--fs-sm);
  font-weight: 600;
}

.tour-btn--primary {
  background: var(--c-brand);
  color: #ffffff;

  box-shadow: var(--sh-brand);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.tour-btn--primary:hover {
  background: var(--c-brand-hover);

  box-shadow: var(--sh-brand-hover);

  transform: translateY(-1px);
}

.tour-btn--primary:active {
  background: var(--c-brand-active);

  transform: translateY(0);
}

/* The grey outlined secondary used by the vendor under-review and rejected pages. */
.tour-btn--secondary {
  color: var(--c-text-2);
}

/* Quasar draws the outline on ::before in the text colour, so the softer border goes there. */
.tour-btn--secondary::before {
  border-color: var(--c-border-strong);
}

.tour-btn--secondary:hover {
  background: var(--c-surface);
}

.tour-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

@media (max-width: 480px) {
  .tour-content {
    padding: 24px 20px 0;
  }

  .tour-logo {
    width: 132px;
  }

  /* Side by side, "Simulan ang Tutorial" has about 130px to live in and wraps or clips.
     Stacking gives every label the full width, primary first so the thumb lands on it. */
  .tour-actions {
    flex-direction: column;
    align-items: stretch;

    gap: 10px;
    padding: 18px 20px 24px;
  }

  .tour-actions .tour-btn--primary {
    order: -1;
  }

  .tour-actions .q-btn {
    height: 46px;
  }
}
</style>

<!--
  Two reasons this block is unscoped. driver.js appends its popover to <body>, outside this
  component's scope id, and the welcome/finish dialogs are teleported there too. Every
  selector is still prefixed with .tindahan-tour-popover (set via popoverClass) or
  .tour-dialog, so nothing here reaches another popup.

  The dark rules are gated on body.vendor-dark-mode, the marker VendorLayout toggles
  alongside $q.dark, matching the convention in that file: the layout re-declares the whole
  --c-* palette on <body> for exactly this case, so most of the light-mode rules above
  already follow dark mode through their tokens and only the few hardcoded whites are
  restated below.
-->
<style>
.driver-popover.tindahan-tour-popover {
  padding: 18px;

  border-radius: var(--r-surface);

  background: #ffffff;
  color: var(--c-text);

  box-shadow: var(--sh-pop);

  font-family: 'Roboto', Arial, sans-serif;
  /* Phones are narrower than driver's 300px default, so the popover keeps a side gutter. */
  max-width: min(320px, calc(100vw - 32px));
}

/* Which area of the store this step belongs to, injected in onPopoverRender. The eyebrow
   shares its line with the close button, and "SETTINGS NG TINDAHAN" is long enough to run
   underneath it, so it clears the same corner the title does. */
.tindahan-tour-popover .tour-eyebrow {
  margin-bottom: 4px;
  padding-right: 24px;

  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;

  color: var(--c-brand);
}

.tindahan-tour-popover .driver-popover-title {
  font-size: var(--fs-lg);
  font-weight: 700;

  color: var(--c-text);

  /* Clears the close button parked in the top-right corner. */
  padding-right: 24px;
}

.tindahan-tour-popover .driver-popover-description {
  margin-top: 6px;

  font-size: var(--fs-sm);
  line-height: 1.6;

  color: var(--c-text-3);
}

.tindahan-tour-popover .driver-popover-progress-text {
  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-muted);
}

.tindahan-tour-popover .driver-popover-close-btn {
  color: var(--c-muted);
}

/* driver.css darkens this on :focus, and it takes focus as each step renders — so on a
   phone the X arrived already looking pressed, heavier than the Next button beside it.
   Hover and keyboard focus darken it; plain focus leaves it quiet. */
.tindahan-tour-popover .driver-popover-close-btn:hover {
  color: var(--c-text);
}

.tindahan-tour-popover .driver-popover-close-btn:focus {
  color: var(--c-muted);
}

.tindahan-tour-popover .driver-popover-close-btn:focus-visible {
  color: var(--c-text);

  border-radius: 50%;
  outline: 2px solid var(--c-brand);
  outline-offset: -4px;
}

.tindahan-tour-popover .driver-popover-footer {
  margin-top: 16px;
}

.tindahan-tour-popover .driver-popover-footer-btn {
  height: 32px;
  padding: 0 14px;

  border-radius: var(--r-sm);
  border-color: var(--c-border-strong);

  background: transparent;
  color: var(--c-text-2);

  font-size: var(--fs-xs);
  font-weight: 600;
  line-height: 30px;
  /* driver.css right-aligns the whole footer and builds these buttons with `all: unset`,
     so they inherit that alignment. Invisible while they are shrink-to-fit, but the moment
     they stretch on a phone the label slides to the right edge. */
  text-align: center;
}

.tindahan-tour-popover .driver-popover-footer-btn:hover {
  background: var(--c-surface);
}

.tindahan-tour-popover .driver-popover-next-btn {
  border-color: var(--c-brand);

  background: var(--c-brand);
  color: #ffffff;
}

.tindahan-tour-popover .driver-popover-next-btn:hover {
  border-color: var(--c-brand-hover);

  background: var(--c-brand-hover);
}

.tindahan-tour-popover .driver-popover-arrow {
  border-width: 6px;
}

/* Phones: a tighter card, and buttons big enough to be tapped rather than clicked. */
@media (max-width: 599px) {
  .driver-popover.tindahan-tour-popover {
    padding: 14px;

    /* A long step description on a short screen — or a phone held sideways — would run the
       popover past the viewport, taking Next with it. It scrolls inside instead. */
    max-height: calc(100vh - 24px);
    overflow-y: auto;
  }

  /* The close button grows to a 44px tap target here, so both lines beside it clear more. */
  .tindahan-tour-popover .driver-popover-title {
    font-size: var(--fs-md);
    padding-right: 40px;
  }

  .tindahan-tour-popover .tour-eyebrow {
    padding-right: 40px;
  }

  .tindahan-tour-popover .driver-popover-footer {
    margin-top: 12px;
    gap: 8px;
  }

  .tindahan-tour-popover .driver-popover-footer-btn {
    height: 38px;
    padding: 0 14px;

    line-height: 36px;
  }

  /* Back and Next take the room the progress text gives up, so neither is a thin sliver
     next to a 25-step counter. */
  .tindahan-tour-popover .driver-popover-navigation-btns {
    flex: 1;
    gap: 8px;
  }

  .tindahan-tour-popover .driver-popover-navigation-btns button {
    flex: 1;
    margin-left: 0;
  }

  /* On step 1 there is no Back. Driver only fades it, which on a phone spends half the row
     on a button that does nothing — hidden, Next takes the full width instead. */
  .tindahan-tour-popover .driver-popover-footer .driver-popover-btn-disabled {
    display: none;
  }

  .tindahan-tour-popover .driver-popover-progress-text {
    flex-shrink: 0;
  }

  /* 44px of tappable corner instead of driver's 32x28 click target, without the glyph
     itself growing heavy enough to compete with Next. */
  .tindahan-tour-popover .driver-popover-close-btn {
    width: 44px;
    height: 40px;

    font-size: 19px;
  }
}

/* ---- Vendor dark mode ---- */

body.body--dark.vendor-dark-mode .driver-popover.tindahan-tour-popover {
  background: #111a2e;

  border: 1px solid rgba(255, 255, 255, 0.08);

  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.55);
}

/* driver.css paints the arrow white on whichever side is showing; the other three sides are
   already transparent there, so only the visible one has to be re-tinted to the card. */
body.body--dark.vendor-dark-mode .tindahan-tour-popover .driver-popover-arrow-side-left {
  border-left-color: #111a2e;
}

body.body--dark.vendor-dark-mode .tindahan-tour-popover .driver-popover-arrow-side-right {
  border-right-color: #111a2e;
}

body.body--dark.vendor-dark-mode .tindahan-tour-popover .driver-popover-arrow-side-top {
  border-top-color: #111a2e;
}

body.body--dark.vendor-dark-mode .tindahan-tour-popover .driver-popover-arrow-side-bottom {
  border-bottom-color: #111a2e;
}

/* The same lit gradient the layout gives .dialog-icon and the pages give .info-icon, instead
   of the flat light-mode tint, which reads muddy against navy. */
body.body--dark.vendor-dark-mode .tour-dialog .tour-done-icon {
  background: linear-gradient(145deg, rgba(74, 222, 128, 0.24) 0%, rgba(74, 222, 128, 0.08) 100%);
  box-shadow: 0 0 0 1px rgba(74, 222, 128, 0.28), 0 0 18px rgba(74, 222, 128, 0.26);
  color: #86efac;
}

/* app.scss paints every dialog's q-card__actions #1e293b; these cards have no divider above
   the buttons, so the action row stays part of the one surface. */
body.body--dark.vendor-dark-mode .q-dialog .tour-dialog .tour-actions {
  background: transparent !important;
  border-top: none !important;
}
</style>
