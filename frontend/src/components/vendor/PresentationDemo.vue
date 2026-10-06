<template>
  <!-- Presentation demo has no visible UI of its own. -->
</template>

<script setup>
import { nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { driver } from 'driver.js'
import 'driver.js/dist/driver.css'

const route = useRoute()
const router = useRouter()

const PRESENTATION_DEMO_KEY = 'presentation_demo'

let tour = null

const ADMIN_DASHBOARD = '/admin/dashboard'
const ADMIN_APPROVALS = '/admin/approvals'
const ADMIN_VENDORS = '/admin/vendors'

const steps = [
  {
    route: ADMIN_DASHBOARD,
    popover: {
      title: 'Welcome to the Tindahan Admin Panel',
      description:
        'Welcome! This presentation will briefly walk through the key administrative functions of Tindahan, from vendor approval to vendor management.'
    }
  },
  {
    route: ADMIN_DASHBOARD,
    element: '[data-presentation="admin-dashboard-overview"]',
    popover: {
      title: 'Admin Dashboard',
      description:
        'This dashboard gives the administrator a quick overview of the marketplace, including pending tasks, active users, and overall platform activity.'
    }
  },
  {
  route: ADMIN_DASHBOARD,
  element: '[data-presentation="admin-dashboard-panels"]',
  popover: {
    title: 'Marketplace Overview',
    description:
      'These panels provide a closer look at pending applications, platform activity, the vendor-consumer ecosystem, and recent activity.'
  }
},
  {
  route: ADMIN_APPROVALS,
  element: '[data-presentation="admin-approval-view"]',
  popover: {
    title: 'Review Vendor Application',
    description:
      'Click View to open the vendor application and review its information.'
  }
},
{
  route: ADMIN_APPROVALS,
  element: '[data-presentation="admin-review-info"]',
  popover: {
    title: 'Vendor Information',
    description:
      'The admin can review the vendor and store information before making a decision.'
  }
},
{
  route: ADMIN_APPROVALS,
  element: '[data-presentation="admin-review-approve"]',
  popover: {
    title: 'Approve Vendor Application',
    description:
      'After reviewing the vendor information, the admin can approve the application.'
  }
},
  {
    route: ADMIN_VENDORS,
    element: '[data-presentation="admin-view-products"]',
    popover: {
      title: 'View Vendor Products',
      description:
        'The admin can inspect the products being sold by a vendor to check whether they follow the expected selling price guidelines.'
    }
  },
  {
    route: ADMIN_VENDORS,
    element: '[data-presentation="admin-vendor-options"]',
    popover: {
      title: 'Vendor Account Actions',
      description:
        'The admin can manage a vendor account, including suspending or deactivating the vendor when necessary.'
    }
  }
]

const waitForElement = async (selector, timeout = 5000) => {
  const started = Date.now()

  while (Date.now() - started < timeout) {
    const element = document.querySelector(selector)

    if (element) {
      return element
    }

    await new Promise(resolve => setTimeout(resolve, 100))
  }

  return null
}

const ensureRoute = async path => {
  if (route.path === path) {
    return
  }

  await router.push(path)
  await nextTick()
}

const wait = ms => new Promise(resolve => setTimeout(resolve, ms))

const handlePresentationClick = event => {
  const target = event.target?.closest?.(
    '[data-presentation="admin-approval-view"]'
  )

  if (!target) return

  if (tour?.getActiveIndex() !== 4) return

  setTimeout(async () => {
    const info = await waitForElement(
      '[data-presentation="admin-review-info"]'
    )

    if (!info || !tour?.isActive()) return

    tour.moveNext()
  }, 500)
}

const startPresentationDemo = async () => {
  if (tour?.isActive()) return

  const currentIndex = steps.findIndex(step => step.route === route.path)
  const firstIndex = currentIndex >= 0 ? currentIndex : 0

    tour = driver({
  showProgress: true,
  allowClose: true,
  overlayClickBehavior: () => {},
  disableActiveInteraction: false,
  smoothScroll: true,
  allowScroll: true,
  stagePadding: 6,
  stageRadius: 10,
  popoverClass: 'tindahan-presentation-popover',

  nextBtnText: 'Next',
  prevBtnText: 'Back',
  doneBtnText: 'Finish',


  onNextClick: async () => {
    const current = tour?.getActiveIndex() ?? 0
    const next = current + 1

    if (next >= steps.length) {
      tour?.destroy()
      return
    }

    const nextStep = steps[next]

    await ensureRoute(nextStep.route)

    if (nextStep.element) {
      await waitForElement(nextStep.element)
    }

    tour?.moveNext()
  },

  onPrevClick: async () => {
    const current = tour?.getActiveIndex() ?? 0
    const previous = current - 1

    if (previous < 0) {
      return
    }

    const previousStep = steps[previous]

    await ensureRoute(previousStep.route)

    if (previousStep.element) {
      await waitForElement(previousStep.element)
    }

    tour?.movePrevious()
  },

  steps: steps.map(step => ({
    element: step.element,
    popover: step.popover
  }))
})

  if (steps[firstIndex].element) {
    await waitForElement(steps[firstIndex].element)
  }

  console.log('[PresentationDemo] driving tour', {
    firstIndex,
    target: steps[firstIndex].element,
    element: document.querySelector(steps[firstIndex].element)
  })
  tour.drive(firstIndex)
}

const checkPresentationDemo = async () => {
  console.log('[PresentationDemo] mounted', {
    flag: sessionStorage.getItem(PRESENTATION_DEMO_KEY),
    route: route.path
  })

  if (sessionStorage.getItem(PRESENTATION_DEMO_KEY) !== '1') {
    console.log('[PresentationDemo] no flag')
    return
  }

  sessionStorage.removeItem(PRESENTATION_DEMO_KEY)

  await nextTick()

  console.log('[PresentationDemo] flag detected', route.path)

  if (!route.path.startsWith('/admin/')) {
    console.log('[PresentationDemo] not admin route')
    return
  }

  await startPresentationDemo()
}

onMounted(() => {
  document.addEventListener('click', handlePresentationClick, true)
  checkPresentationDemo()
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handlePresentationClick, true)

  if (tour?.isActive()) {
    tour.destroy()
  }

  tour = null
})
</script>

<style>
.tindahan-presentation-popover {
  max-width: 360px;
}

.tindahan-presentation-popover .driver-popover-title {
  font-weight: 700;
}

.tindahan-presentation-popover .driver-popover-description {
  line-height: 1.5;
}
</style>
