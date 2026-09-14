import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'
import * as Vue from 'vue'
import { parse, compileScript } from '@vue/compiler-sfc'
import { baseParse, compile } from '@vue/compiler-dom'
import { parse as parseJavaScript } from '@babel/parser'
import useValidate, { useValidateProps } from '../node_modules/quasar/src/composables/private.use-validate/use-validate.js'
import useFormChild from '../node_modules/quasar/src/composables/use-form/use-form-child.js'
import { formKey } from '../node_modules/quasar/src/utils/private.symbols/symbols.js'
import { isValidBirthday } from '../src/utils/birthday.js'
import { useConsumerLanguage } from '../src/composables/useConsumerLanguage.js'

const language = useConsumerLanguage()
const vueExports = { ...Vue }
const renderer = Vue.createRenderer({
  createElement: type => ({ type, children: [], props: {} }),
  createText: text => ({ text }),
  createComment: () => ({ text: '' }),
  setText: (node, text) => { node.text = text },
  setElementText: (node, text) => { node.text = text; node.children = [] },
  patchProp: (node, key, _, value) => { node.props[key] = value },
  insert(node, parent, anchor) {
    if (node.parent) {
      const siblings = node.parent.children
      siblings.splice(siblings.indexOf(node), 1)
    }
    node.parent = parent
    const index = anchor ? parent.children.indexOf(anchor) : -1
    parent.children.splice(index < 0 ? parent.children.length : index, 0, node)
  },
  remove(node) {
    const siblings = node.parent?.children
    if (siblings) siblings.splice(siblings.indexOf(node), 1)
    node.parent = null
  },
  parentNode: node => node.parent,
  nextSibling: node => node.parent?.children[node.parent.children.indexOf(node) + 1]
})
const textContent = node => (node.text || '') + (node.children || []).map(textContent).join('')
const settle = async () => {
  await Vue.nextTick()
  await new Promise(resolve => setTimeout(resolve, 10))
  await Vue.nextTick()
}

async function loadComponent(path, bindings) {
  const source = await readFile(new URL(path, import.meta.url), 'utf8')
  const { descriptor } = parse(source)
  const script = compileScript(descriptor, { id: 'regression', inlineTemplate: true }).content
  const ast = parseJavaScript(script, { sourceType: 'module' })
  const dependencies = { ...bindings }
  const edits = []
  for (const node of ast.program.body) {
    if (node.type === 'ImportDeclaration') {
      for (const specifier of node.specifiers) {
        dependencies[specifier.local.name] = node.source.value === 'vue'
          ? vueExports[specifier.imported.name]
          : bindings[specifier.local.name]
      }
      edits.push([node.start, node.end, ''])
    } else if (node.type === 'ExportDefaultDeclaration') {
      edits.push([node.start, node.declaration.start, 'return '])
    }
  }
  let code = script
  for (const [start, end, value] of edits.sort((a, b) => b[0] - a[0])) code = code.slice(0, start) + value + code.slice(end)
  return new Function(...Object.keys(dependencies), code)(...Object.values(dependencies))
}

test('existing Quasar validation errors change language without clearing form values', async () => {
  for (const file of ['LoginPage.vue', 'consumer/ConsumerRegister.vue', 'vendor/VendorRegister.vue']) {
    const source = await readFile(new URL('../src/pages/auth/' + file, import.meta.url), 'utf8')
    const template = parse(source).descriptor.template.content
    let input
    function find(node) {
      if (!input && node.tag === 'q-input') input = node
      for (const child of node.children || []) find(child)
    }
    find(baseParse(template))
    const { code } = compile(input.loc.source, { mode: 'function', prefixIdentifiers: true })
    const state = Object.fromEntries([...code.matchAll(/_ctx\.(\w+)/g)].map(match => [match[1], true]))
    Object.assign(state, { t: language.t, form: { identifier: '', firstName: '', ownerName: '', password: 'unchanged' } })
    const root = { children: [] }
    let field
    const app = renderer.createApp({ data: () => state, render: new Function('Vue', code)(Vue) })
    app.component('QInput', {
      props: { ...useValidateProps, disable: Boolean, label: String },
      setup() {
        const validation = useValidate(Vue.ref(false), Vue.ref(false))
        field = validation
        return () => Vue.h('span', validation.errorMessage.value || '')
      }
    })
    language.setLanguage('en')
    app.mount(root)
    assert.equal(textContent(root), '', file)
    assert.equal(field.validate(), false, file)
    await settle()
    const english = textContent(root)
    assert.ok(english.includes('is required.'), file)
    language.setLanguage('fil')
    await settle()
    assert.equal(textContent(root), language.t(english), file)
    language.setLanguage('en')
    await settle()
    assert.equal(textContent(root), english, file)
    assert.equal(state.form.password, 'unchanged')
    app.unmount()
  }
})

test('birthday required errors follow live language changes and stay silent initially', async () => {
  const BirthdayInput = await loadComponent('../src/components/shared/BirthdayInput.vue', { useFormChild, isValidBirthday })
  const root = { children: [] }
  let field
  const app = renderer.createApp({
    setup() {
      Vue.provide(formKey, { bindComponent: instance => { field = instance }, unbindComponent: () => {} })
      return () => Vue.h(BirthdayInput, {
        modelValue: '', translate: language.t, locale: language.locale.value,
        rules: [value => !!value || language.t('Birthday is required.')]
      })
    }
  })
  app.component('QSelect', { render: () => null })
  language.setLanguage('fil')
  app.mount(root)
  assert.equal(textContent(root), '')
  assert.equal(field.validate(), false)
  await settle()
  assert.equal(textContent(root), language.t('Birthday is required.'))
  language.setLanguage('en')
  await settle()
  assert.equal(textContent(root), 'Birthday is required.')
  language.setLanguage('fil')
  await settle()
  assert.equal(textContent(root), language.t('Birthday is required.'))
  app.unmount()
  language.setLanguage('en')
})

test('HTML language follows Consumer and auth routes and restores the original elsewhere', async () => {
  const route = Vue.reactive({ path: '/login' })
  const document = { documentElement: { lang: 'en' } }
  const MainLayout = await loadComponent('../src/layouts/MainLayout.vue', { useRoute: () => route, useConsumerLanguage, document })
  const app = renderer.createApp(MainLayout)
  const container = { setup: (_, { slots }) => () => Vue.h('div', slots.default?.()) }
  app.component('QLayout', container)
  app.component('QPageContainer', container)
  app.component('RouterView', { render: () => null })
  app.mount({ children: [] })
  language.setLanguage('fil')
  for (const path of ['/login', '/consumer/register', '/verification', '/consumer/success', '/vendor/register', '/auth/vendor/rejected', '/auth/vendor/under-review', '/consumer/home']) {
    route.path = path
    await Vue.nextTick()
    assert.equal(document.documentElement.lang, 'fil', path)
  }
  for (const path of ['/vendor/dashboard', '/admin/dashboard']) {
    route.path = path
    await Vue.nextTick()
    assert.equal(document.documentElement.lang, 'en', path)
  }
  route.path = '/consumer/home'
  await Vue.nextTick()
  app.unmount()
  assert.equal(document.documentElement.lang, 'en')
  language.setLanguage('en')
})
