import test from 'node:test'
import assert from 'node:assert/strict'

import {
  notificationPresentation,
  notificationTime
} from '../src/utils/notificationPresentation.js'
import { applyNotificationReadState } from '../src/utils/notificationSync.js'

test('notification presentation maps order stages to their matching icon tone', () => {
  assert.equal(notificationPresentation({ title: 'Order Cancelled' }).tone, 'cancelled')
  assert.equal(notificationPresentation({ title: 'Order Picked Up' }).tone, 'done')
  assert.equal(notificationPresentation({ message: 'Your order is ready.' }).tone, 'ready')
  assert.equal(notificationPresentation({ title: 'Order Preparing' }).tone, 'preparing')
  assert.equal(notificationPresentation({ title: 'New Order' }).tone, 'brand')
})

test('notification time handles recent, older, and invalid timestamps', () => {
  const originalNow = Date.now
  const now = new Date('2026-09-15T12:00:00Z').getTime()
  const t = (message, values = {}) => message.replace('{count}', values.count)
  Date.now = () => now

  try {
    assert.equal(notificationTime('2026-09-15T11:59:40Z', t, 'en-PH'), 'Just now')
    assert.equal(notificationTime('2026-09-15T11:45:00Z', t, 'en-PH'), '15 mins ago')
    assert.equal(notificationTime('invalid', t, 'en-PH'), '')
  } finally {
    Date.now = originalNow
  }
})

test('notification read updates synchronize one item or the whole list', () => {
  const notifications = [
    { notification_id: 1, is_read: false },
    { notification_id: 2, is_read: false }
  ]

  applyNotificationReadState(notifications, { notificationId: '1', isRead: true })
  assert.deepEqual(notifications.map(item => item.is_read), [true, false])

  applyNotificationReadState(notifications, { all: true, isRead: true })
  assert.deepEqual(notifications.map(item => item.is_read), [true, true])
})
