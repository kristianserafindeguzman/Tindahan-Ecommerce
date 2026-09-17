import assert from 'node:assert/strict'
import { test } from 'node:test'
import { formatBirthday, isValidBirthday, latestBirthday } from '../src/utils/birthday.js'

test('birthday validation rejects impossible dates and malformed input', () => {
  for (const value of [
    '2000-02-30', '2001-02-29', '1900-02-29', '2000-04-31',
    '1990-13-10', '1990-00-10', '1990-02-00', '2000-2-1',
    '2000-02-29extra', '', null, undefined, 20000229
  ]) {
    assert.equal(isValidBirthday(value), false, String(value))
    assert.equal(formatBirthday(value), '', String(value))
  }
  assert.equal(isValidBirthday('2000-02-29'), true)
  assert.equal(isValidBirthday('1904-02-29'), true)
})

test('birthday limits use local yesterday across a year boundary', t => {
  t.mock.timers.enable({ apis: ['Date'], now: new Date(2026, 0, 1, 12).getTime() })
  assert.equal(latestBirthday(), '2025-12-31')
  assert.equal(isValidBirthday('1900-01-01'), true)
  assert.equal(isValidBirthday('1899-12-31'), false)
  assert.equal(isValidBirthday('2025-12-31'), true)
  assert.equal(isValidBirthday('2026-01-01'), false)
  assert.equal(isValidBirthday('2026-01-02'), false)
})

test('birthday formatting preserves the entered calendar date', () => {
  assert.equal(formatBirthday('2000-02-29', 'en-US'), 'February 29, 2000')
  assert.equal(formatBirthday('1900-01-01', 'en-US'), 'January 1, 1900')
})
