import assert from 'node:assert/strict'
import test from 'node:test'
import {
  PASSWORD_REQUIREMENTS,
  validatePassword
} from '../src/utils/passwordValidation.js'
import { useConsumerLanguage } from '../src/composables/useConsumerLanguage.js'

test('password policy accepts complete passwords without requiring a number', () => {
  for (const password of [
    'Abcdefg!',
    'LongPassword!',
    ' Abcdefg! ',
    '\u00c9abcdef!',
    'Abcdefg\u{1f600}',
    '\u{10400}abcdef!'
  ]) {
    assert.equal(validatePassword(password), true, password)
  }
  for (const symbol of '!@#$%^&*()_+-={}[]|:;"<>,.?/~\\') {
    assert.equal(validatePassword('Abcdefg' + symbol), true, symbol)
  }
})

test('password policy reports the missing requirement and counts Unicode characters consistently', () => {
  for (const [password, message] of [
    ['', 'Password is required.'],
    [null, 'Password is required.'],
    [[], 'Password is required.'],
    ['Abcdef!', 'Password must be at least 8 characters.'],
    ['abcdefg!', 'Password must contain at least one uppercase letter.'],
    ['ABCDEFG!', 'Password must contain at least one lowercase letter.'],
    ['Abcdefgh', 'Password must contain at least one symbol.'],
    ['Abcdefg ', 'Password must contain at least one symbol.'],
    ['Abcdef\u{1f600}', 'Password must be at least 8 characters.']
  ]) {
    assert.equal(validatePassword(password), message)
  }
})

test('password guidance and validation errors translate without changing the password', () => {
  const language = useConsumerLanguage()
  const password = 'Abcdefgh'
  language.setLanguage('en')
  const english = validatePassword(password, language.t)
  assert.equal(english, 'Password must contain at least one symbol.')
  language.setLanguage('fil')
  assert.equal(
    validatePassword(password, language.t),
    'Dapat may kahit isang simbolo ang password, gaya ng !, @, o #.'
  )
  assert.match(language.t(PASSWORD_REQUIREMENTS), /malaking titik/)
  assert.equal(password, 'Abcdefgh')
  language.setLanguage('en')
})
