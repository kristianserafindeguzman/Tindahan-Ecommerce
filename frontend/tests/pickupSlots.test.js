import assert from 'node:assert/strict'
import { test } from 'node:test'
import { buildPickupDays, openWindows, formatPickupSlot } from '../src/utils/pickupSlots.js'

// 2026-09-29 is a Tuesday. Times are written in Manila time (+08:00) on purpose, so the tests
// pass whatever time zone the machine running them is set to.
const at = (iso) => new Date(iso)

const WEEK = {
  Monday: { opens: '08:00', closes: '20:00' },
  Tuesday: { opens: '08:00', closes: '20:00' },
  Wednesday: null, // closed
  Thursday: { opens: '09:30', closes: '11:00' },
  Friday: null,
  Saturday: null,
  Sunday: null
}

test('slots stay inside the store hours and start at least 30 minutes from now', () => {
  const [today] = buildPickupDays(WEEK, at('2026-09-29T18:50:00+08:00'))

  assert.equal(today.weekday, 'Tuesday')
  // 18:50 + 30 min = 19:20, rounded up to the next quarter hour = 19:30; last slot must end by 20:00.
  assert.deepEqual(today.slots.map((slot) => slot.iso), [
    '2026-09-29T19:30:00+08:00',
    '2026-09-29T19:45:00+08:00'
  ])
})

test('a day the store is closed has no slots, and later days use their own hours', () => {
  const [, wednesday, thursday] = buildPickupDays(WEEK, at('2026-09-29T10:00:00+08:00'))

  assert.equal(wednesday.weekday, 'Wednesday')
  assert.equal(wednesday.slots.length, 0)

  assert.equal(thursday.weekday, 'Thursday')
  assert.equal(thursday.slots[0].iso, '2026-10-01T09:30:00+08:00')
  assert.equal(thursday.slots.at(-1).iso, '2026-10-01T10:45:00+08:00')
  assert.equal(thursday.slots.length, 6)
})

test('no slots are left today once it is too close to closing time', () => {
  const [today] = buildPickupDays(WEEK, at('2026-09-29T19:40:00+08:00'))
  assert.equal(today.slots.length, 0)
})

test('opening times that are not on a quarter hour start at the next quarter hour', () => {
  const [today] = buildPickupDays({ Tuesday: { opens: '08:10', closes: '09:00' } }, at('2026-09-29T06:00:00+08:00'))
  // 08:15, 08:30 and 08:45 — the last one ends exactly at closing time.
  assert.deepEqual(today.slots.map((slot) => slot.start), [8 * 60 + 15, 8 * 60 + 30, 8 * 60 + 45])
})

test('hours past midnight open from the opening time to midnight and from midnight to closing', () => {
  assert.deepEqual(openWindows({ opens: '18:00', closes: '02:00' }), [[0, 120], [1080, 1440]])
  assert.deepEqual(openWindows({ opens: '00:00', closes: '23:59' }), [[0, 1440]])
  assert.deepEqual(openWindows(null), [])
})

test('the day follows Manila time, not the device clock', () => {
  // 17:30 UTC on Tuesday is already 01:30 on Wednesday in Manila.
  const [today] = buildPickupDays(WEEK, at('2026-09-29T17:30:00Z'))
  assert.equal(today.weekday, 'Wednesday')
  assert.equal(today.ymd, '2026-09-30')
})

test('a saved slot is shown in Manila time, and ASAP has no text of its own', () => {
  assert.equal(formatPickupSlot('2026-09-30T07:30:00Z', 'en-US'), 'Wed, Sep 30 · 3:30 PM – 3:45 PM')
  assert.equal(formatPickupSlot(null), '')
})
