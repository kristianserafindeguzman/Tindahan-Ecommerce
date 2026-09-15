import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'
import * as Vue from 'vue'
import { parse } from '@vue/compiler-sfc'
import { compile } from '@vue/compiler-dom'
import { renderToString } from '@vue/server-renderer'
import { parse as parseJavaScript } from '@babel/parser'
import { useConsumerLanguage } from '../src/composables/useConsumerLanguage.js'

const language = useConsumerLanguage()

async function componentRender(path) {
  const source = await readFile(new URL(path, import.meta.url), 'utf8')
  const { descriptor } = parse(source)
  const { code } = compile(descriptor.template.content, { mode: 'function', prefixIdentifiers: true })
  return new Function('Vue', code)(Vue)
}

async function renderAuth(file, overrides = {}) {
  const source = await readFile(new URL('../src/pages/auth/' + file, import.meta.url), 'utf8')
  const { descriptor } = parse(source)
  const { code } = compile(descriptor.template.content, { mode: 'function', prefixIdentifiers: true })
  const state = Object.fromEntries([...code.matchAll(/_ctx\.(\w+)/g)].map((match) => [match[1], false]))
  Object.assign(state, {
    t: language.t,
    locale: language.locale.value,
    form: { identifier: '', password: '', firstName: '', lastName: '', birthday: '', email: '', phoneNumber: '', confirmPassword: '', operatingDays: [] },
    step: 1,
    STEPS: ['Account', 'Verify phone', 'Store', 'Hours', 'Location', 'Review'],
    DAY_ORDER: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    otp: ['', '', '', '', '', ''],
    forgotOtp: ['', '', '', '', '', ''],
    otpRefs: [],
    forgotOtpRefs: [],
    phoneRule: () => true,
    identifierRule: () => true,
    hasPhone: true,
    displayPhone: '0912***6789',
    timer: 12,
    formattedTimer: '0:12',
    ...overrides
  })
  if (file === 'vendor/VendorRegister.vue') {
    const script = descriptor.scriptSetup.content
    const ast = parseJavaScript(script, { sourceType: 'module' })
    const declaration = ast.program.body
      .flatMap(node => node.declarations || [])
      .find(node => node.id.name === 'otpErrorText')
    const expression = script.slice(declaration.init.start, declaration.init.end)
    const message = new Function('computed', 'otpError', 't', 'return ' + expression)(
      Vue.computed, Vue.toRef(state, 'otpError'), language.t
    )
    Object.defineProperty(state, 'otpErrorText', { enumerable: true, get: () => message.value })
  }
  const app = Vue.createSSRApp({ data: () => state, render: new Function('Vue', code)(Vue) })
  app.component('AuthLanguageSwitcher', {
    render: await componentRender('../src/components/consumer/AuthLanguageSwitcher.vue')
  })
  app.component('LanguageSwitcher', {
    props: { header: Boolean, compact: Boolean, settings: Boolean },
    setup: () => useConsumerLanguage(),
    render: await componentRender('../src/components/consumer/LanguageSwitcher.vue')
  })
  const container = { setup: (_, { slots }) => () => Vue.h('div', slots.default?.()) }
  for (const name of ['QPage', 'QForm', 'QCard', 'QCardSection', 'QCardActions', 'QSeparator', 'QList', 'QItem', 'QItemSection']) app.component(name, container)
  app.directive('close-popup', {})
  app.component('QBtnDropdown', {
    props: ['label'],
    setup: (props, { slots }) => () => Vue.h('div', [Vue.h('button', { 'data-language-trigger': '' }, props.label), slots.default?.()])
  })
  app.component('QDialog', { props: ['modelValue'], setup: (props, { slots }) => () => props.modelValue ? Vue.h('div', slots.default?.()) : null })
  app.component('QInput', {
    inheritAttrs: false,
    props: ['label', 'rules'],
    setup: (props) => () => {
      const error = props.rules?.[0]?.('')
      return Vue.h('div', [Vue.h('input', { 'aria-label': props.label }), error && error !== true ? Vue.h('span', error) : null])
    }
  })
  app.component('QBtn', { props: ['label'], setup: (props) => () => Vue.h('button', props.label) })
  for (const name of ['QSelect', 'QToggle']) app.component(name, { props: ['label'], setup: (props) => () => Vue.h('span', props.label) })
  app.component('BirthdayInput', { props: ['translate'], setup: (props) => () => Vue.h('span', props.translate('Birth Month')) })
  app.component('VendorLocationMap', { props: ['translate'], setup: (props) => () => Vue.h('span', props.translate('Your Location')) })
  for (const name of ['QIcon', 'QBtnToggle', 'TermsModal', 'PrivacyModal', 'ContactSupportModal', 'PhotoCropper']) app.component(name, { render: () => null })
  return renderToString(app)
}

test('auth pages follow the Consumer language and restore their English view', async () => {
  const pages = [
    ['LoginPage.vue', 'Mag-log in sa Tindahan account mo.'],
    ['consumer/ConsumerRegister.vue', 'Gumawa ng account para makapagsimula.'],
    ['consumer/ConsumerVerify.vue', 'I-verify ang account mo'],
    ['consumer/ConsumerSuccess.vue', 'Verified na ang Account'],
    ['vendor/VendorRegister.vue', 'Mag-register bilang Vendor'],
    ['vendor/VendorRejected.vue', 'Hindi Na-approve ang Application'],
    ['vendor/VendorUnderReview.vue', 'Nire-review ang Application']
  ]
  for (const [file, filipino] of pages) {
    language.setLanguage('en')
    const english = await renderAuth(file)
    language.setLanguage('fil')
    assert.ok((await renderAuth(file)).includes(filipino), file)
    language.setLanguage('en')
    assert.equal(await renderAuth(file), english, file)
  }
})

test('all auth pages expose the existing EN/FIL dropdown and preserve form values', async () => {
  for (const file of ['LoginPage.vue', 'consumer/ConsumerRegister.vue', 'consumer/ConsumerVerify.vue', 'consumer/ConsumerSuccess.vue', 'vendor/VendorRegister.vue', 'vendor/VendorRejected.vue', 'vendor/VendorUnderReview.vue']) {
    const fields = { ownerName: 'Sample Owner', storeName: 'Sample Store', identifier: 'buyer@example.com', email: 'buyer@example.com', phoneNumber: '09123456789', password: 'SamePassword123', operatingDays: ['Mon'] }
    const state = { form: fields }
    language.setLanguage('en')
    const english = await renderAuth(file, state)
    assert.match(english, /data-language-trigger(?:="")?>EN<\/button>/, file)
    language.setLanguage('fil')
    const filipino = await renderAuth(file, state)
    assert.match(filipino, /data-language-trigger(?:="")?>FIL<\/button>/, file)
    assert.ok(filipino.includes('English') && filipino.includes('Filipino'), file)
    assert.equal(state.form, fields)
    assert.equal(fields.password, 'SamePassword123')
    assert.equal(fields.email, 'buyer@example.com')
    language.setLanguage('en')
    assert.equal(await renderAuth(file, state), english, file)
  }
})

test('vendor registration translates steps, errors, location controls and dialogs without translating entered data', async () => {
  language.setLanguage('fil')
  const registration = await renderAuth('vendor/VendorRegister.vue', {
    ownerNameTouched: true,
    phoneTouched: true,
    storeNameTouched: true,
    locationVisited: true,
    resendTimer: 12,
    formattedResendTimer: '0:12',
    maskedPhone: '0912***6789',
    photoMissing: true,
    stepError: 'Please retype the same password.',
    otpError: 'Invalid verification code. Please try again.',
    registerError: 'Validation failed. Please check your inputs.',
    showCropModal: true,
    showSuccess: true,
    form: { ownerName: 'Application Under Review', storeName: 'Your Store', detectedAddress: 'Store Location', phoneNumber: '09123456789', operatingDays: ['Mon'] },
    finalAddress: 'Store Location'
  })
  for (const text of [
    'Step 1 sa 6',
    'Ilagay ang pangalan ng store owner.',
    'Ilagay ang phone number mo.',
    'Ilagay ang store name.',
    'I-resend (0:12)',
    '0912***6789',
    'Digit 1 sa 6',
    'Mag-upload ng photo ng harap ng store mo.',
    'I-type ulit ang parehong password.',
    'Hindi valid ang verification code. Subukan ulit.',
    'May problema sa details mo. Paki-check ulit.',
    'Lokasyon Mo',
    'I-crop ang Store Photo',
    'Na-submit na ang Application!',
    '1–3 business days',
    'Application Under Review',
    'Your Store',
    'Store Location',
    '09123456789',
    'Mon'
  ]) assert.ok(registration.includes(text), text)
  const rejected = await renderAuth('vendor/VendorRejected.vue', { rejectionReason: 'Application Under Review', rejectedBy: 'Your Store' })
  assert.ok(rejected.includes('Dahilan Kung Bakit Hindi Na-approve'))
  assert.ok(rejected.includes('Ni-review ni'))
  assert.ok(rejected.includes('Application Under Review'))
  assert.ok(rejected.includes('Your Store'))
  const review = await renderAuth('vendor/VendorUnderReview.vue')
  assert.ok(review.includes('1–3 business days'))
  assert.ok(review.includes('I-refresh ang Status'))
  language.setLanguage('en')
})

test('vendor OTP recovery messages switch languages while keeping unknown server errors intact', async () => {
  for (const error of ['Please verify your phone number again before registering.', 'A custom server error.']) {
    const state = { otpError: error + ' We sent you a new code.' }
    language.setLanguage('fil')
    const filipino = await renderAuth('vendor/VendorRegister.vue', state)
    assert.ok(filipino.includes(language.t(error) + ' Nag-send kami ng bagong code.'))
    language.setLanguage('en')
    const english = await renderAuth('vendor/VendorRegister.vue', state)
    assert.ok(english.includes(error + ' We sent you a new code.'))
    assert.equal(state.otpError, error + ' We sent you a new code.')
  }
})

test('auth errors, field labels, registration choices and countdowns use Filipino', async () => {
  language.setLanguage('fil')
  const login = await renderAuth('LoginPage.vue', {
    identifierTouched: true,
    passwordTouched: true,
    loginError: 'The provided credentials are incorrect.',
    showRegistrationOptions: true
  })
  assert.ok(login.includes('Email o Mobile Number'))
  assert.ok(login.includes('Ilagay ang email o mobile number mo.'))
  assert.ok(login.includes('Ilagay ang password mo.'))
  assert.ok(login.includes('Mali ang email, mobile number, o password.'))
  assert.ok(login.includes('Mag-register bilang Consumer'))
  const registration = await renderAuth('consumer/ConsumerRegister.vue', {
    registerError: 'This email is already registered.',
    confirmPasswordMessage: { type: 'error', text: 'Passwords do not match.' }
  })
  assert.ok(registration.includes('Registered na ang email na ito.'))
  assert.ok(registration.includes('Hindi tugma ang passwords.'))
  const verification = await renderAuth('consumer/ConsumerVerify.vue', { otpError: 'Invalid verification code. Please try again.' })
  assert.ok(verification.includes('Nag-send kami ng 6-digit verification code sa'))
  assert.ok(verification.includes('0912***6789'))
  assert.ok(verification.includes('I-resend (0:12)'))
  assert.ok(verification.includes('Digit 1 sa 6'))
  assert.ok(verification.includes('Hindi valid ang verification code. Subukan ulit.'))
  const reset = await renderAuth('LoginPage.vue', {
    showForgotOtp: true,
    forgotResendTimer: 12,
    formattedForgotResendTimer: '0:12',
    maskedForgotPhone: '0912***6789'
  })
  assert.ok(reset.includes('I-verify ang Phone Number'))
  assert.ok(reset.includes('0912***6789'))
  assert.ok(reset.includes('I-resend (0:12)'))
  const missing = await renderAuth('consumer/ConsumerVerify.vue', { hasPhone: false })
  assert.ok(missing.includes('Hindi namin alam kung aling number ang ive-verify'))
  language.setLanguage('en')
})
