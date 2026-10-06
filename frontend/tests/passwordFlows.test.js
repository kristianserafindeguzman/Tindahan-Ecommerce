import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { compileScript, parse } from '@vue/compiler-sfc'
import { parse as parseJavaScript } from '@babel/parser'
import * as Vue from 'vue'
import {
  PASSWORD_REQUIREMENTS,
  validatePassword
} from '../src/utils/passwordValidation.js'
import { isValidBirthday } from '../src/utils/birthday.js'
import { useConsumerLanguage } from '../src/composables/useConsumerLanguage.js'

function loadForm(t, path) {
  const source = readFileSync(
    new URL('../src/pages/' + path, import.meta.url),
    'utf8'
  )
  const { descriptor } = parse(source)
  const script = compileScript(descriptor, { id: 'password-flow-test' }).content
  const ast = parseJavaScript(script, { sourceType: 'module' })
  const scope = Vue.effectScope()
  t.after(() => scope.stop())
  const posts = []
  const language = useConsumerLanguage()
  const vendorLanguage = Vue.ref('en')
  const bindings = {
    PASSWORD_REQUIREMENTS,
    validatePassword,
    isValidBirthday,
    useConsumerLanguage,
    useRouter: () => ({ push() {} }),
    useRoute: () => ({ query: {} }),
    useQuasar: () => ({ screen: { lt: { md: false } }, notify() {} }),
    useAuth: () => ({ logout() {} }),
    useVendorTutorial: () => ({ requestReplay() {} }),
    useLanguage: dict => ({
      t: key => dict[vendorLanguage.value]?.[key] || key
    }),
    api: {
      post: async (url, payload) => {
        posts.push({ url, payload })
        return { data: {} }
      }
    },
    setInterval: () => 1,
    clearInterval() {},
    window: { scrollTo() {}, addEventListener() {}, removeEventListener() {} }
  }
  const vueBindings = {
    ...Vue,
    onMounted() {},
    onUnmounted() {},
    onBeforeUnmount() {}
  }
  const dependencies = { ...bindings }
  const edits = []
  for (const node of ast.program.body) {
    if (node.type === 'ImportDeclaration') {
      for (const specifier of node.specifiers) {
        dependencies[specifier.local.name] =
          node.source.value === 'vue'
            ? vueBindings[specifier.imported.name]
            : bindings[specifier.local.name]
      }
      edits.push([node.start, node.end, ''])
    } else if (node.type === 'ExportDefaultDeclaration') {
      edits.push([node.start, node.declaration.start, 'return '])
    }
  }
  let code = script
  for (const [start, end, value] of edits.sort((a, b) => b[0] - a[0])) {
    code = code.slice(0, start) + value + code.slice(end)
  }
  const component = new Function(...Object.keys(dependencies), code)(
    ...Object.values(dependencies)
  )
  const state = scope.run(() => component.setup({}, { expose() {} }))
  language.setLanguage('en')
  return { state, posts, language, vendorLanguage }
}

for (const path of [
  'auth/consumer/ConsumerRegister.vue',
  'auth/vendor/VendorRegister.vue'
]) {
  test(
    path +
      ' gates registration and blocks weak passwords even when form validation returns true',
    async t => {
      const { state, posts } = loadForm(t, path)
      Object.assign(state.form, {
        firstName: 'Nena',
        lastName: 'Cruz',
        email: 'new@example.com',
        phoneNumber: '09171234567',
        birthday: '1995-06-15',
        storeName: 'Test Store',
        operatingDays: ['Mon'],
        openingTime: '08:00',
        closingTime: '18:00',
        latitude: 14.6,
        longitude: 121.0
      })
      const vendor = path.includes('/vendor/')
      if (vendor) {
        state.phoneVerified.value = true
        state.verifiedPhone.value = state.form.phoneNumber
        state.photoFile.value = {}
        state.accountForm.value = { validate: async () => true }
      } else {
        state.registerForm.value = { validate: async () => true }
      }
      for (const password of [
        'Abcdef!',
        'abcdefg!',
        'ABCDEFG!',
        'Abcdefgh',
        'Abcdefg '
      ]) {
        state.form.password = password
        state.form.confirmPassword = password
        assert.equal(state.passwordStrong.value, false)
        assert.equal(state.canRegister.value, false)
        await (vendor ? state.submitAccount() : state.handleRegister())
      }
      assert.equal(posts.length, 0)
      state.form.password = 'Abcdefg!'
      state.form.confirmPassword = 'Abcdefg!'
      assert.equal(state.passwordStrong.value, true)
      assert.equal(state.canRegister.value, true)
      state.form.confirmPassword = 'Different!'
      assert.equal(state.canRegister.value, false)
    }
  )
}

test('forgot-password reset enforces the policy while existing passwords can still be entered for login', async t => {
  const { state, posts } = loadForm(t, 'auth/LoginPage.vue')
  state.forgotResetForm.value = { validate: async () => true }
  state.form.identifier = 'consumer@example.com'
  state.form.password = 'secret123'
  assert.equal(state.canLogin.value, true)
  for (const password of [
    'Abcdef!',
    'abcdefg!',
    'ABCDEFG!',
    'Abcdefgh',
    'Abcdefg '
  ]) {
    state.forgotPassword1.value = password
    state.forgotPassword2.value = password
    assert.equal(state.newPasswordStrong.value, false)
    assert.equal(state.canSubmitNewPassword.value, false)
    await state.submitNewPassword()
  }
  assert.equal(posts.length, 0)
  state.forgotPassword1.value = 'Abcdefg!'
  state.forgotPassword2.value = 'Abcdefg!'
  assert.equal(state.canSubmitNewPassword.value, true)
  state.forgotPassword2.value = 'Different!'
  assert.equal(state.canSubmitNewPassword.value, false)
})

for (const path of [
  'Consumer/ConsumerProfile.vue',
  'Vendor/VendorProfile.vue'
]) {
  test(
    path +
      ' uses the shared policy for feedback, confirmation, and OTP submission',
    async t => {
      const { state, posts, language, vendorLanguage } = loadForm(t, path)
      state.passwordFormRef.value = { validate: async () => true }
      state.passwords.current = 'secret123'
      for (const password of [
        'Abcdef!',
        'abcdefg!',
        'ABCDEFG!',
        'Abcdefgh',
        'Abcdefg '
      ]) {
        state.passwords.new = password
        state.passwords.confirm = password
        assert.equal(state.isNewPasswordValid.value, false)
        assert.equal(state.newPasswordMessage.value.type, 'error')
        assert.equal(state.canSavePassword.value, false)
        await state.savePassword()
      }
      assert.equal(posts.length, 0)

      state.passwords.new = 'Abcdefgh'
      language.setLanguage('fil')
      vendorLanguage.value = 'ph'
      assert.equal(
        state.newPasswordMessage.value.text,
        'Dapat may kahit isang simbolo ang password, gaya ng !, @, o #.'
      )
      language.setLanguage('en')
      vendorLanguage.value = 'en'
      assert.equal(state.passwords.new, 'Abcdefgh')

      state.passwords.new = ' Abcdefg! '
      state.passwords.confirm = ' Abcdefg! '
      assert.equal(state.isNewPasswordValid.value, true)
      assert.equal(state.newPasswordMessage.value.type, 'success')
      assert.equal(state.canSavePassword.value, true)
      state.passwords.confirm = 'Different!'
      assert.equal(state.canSavePassword.value, false)
      await state.savePassword()
      assert.equal(posts.length, 0)
    }
  )
}
