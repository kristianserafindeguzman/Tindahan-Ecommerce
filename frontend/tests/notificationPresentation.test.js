import test from 'node:test'
import assert from 'node:assert/strict'

import {
  notificationPresentation,
  notificationTime,
  consumerNotificationText
} from '../src/utils/notificationPresentation.js'
import { computed } from 'vue'
import { useConsumerLanguage } from '../src/composables/useConsumerLanguage.js'
import { applyNotificationReadState } from '../src/utils/notificationSync.js'

test('notification presentation maps order stages to their matching icon tone', () => {
  assert.equal(
    notificationPresentation({ title: 'Order Cancelled' }).tone,
    'cancelled'
  )
  assert.equal(
    notificationPresentation({ title: 'Order Picked Up' }).tone,
    'done'
  )
  assert.equal(
    notificationPresentation({ message: 'Your order is ready.' }).tone,
    'ready'
  )
  assert.equal(
    notificationPresentation({ title: 'Order Preparing' }).tone,
    'preparing'
  )
  assert.equal(notificationPresentation({ title: 'New Order' }).tone, 'brand')
})

test('notification time handles recent, older, and invalid timestamps', () => {
  const originalNow = Date.now
  const now = new Date('2026-09-15T12:00:00Z').getTime()
  const t = (message, values = {}) => message.replace('{count}', values.count)
  Date.now = () => now

  try {
    assert.equal(
      notificationTime('2026-09-15T11:59:40Z', t, 'en-PH'),
      'Just now'
    )
    assert.equal(
      notificationTime('2026-09-15T11:45:00Z', t, 'en-PH'),
      '15 mins ago'
    )
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

  applyNotificationReadState(notifications, {
    notificationId: '1',
    isRead: true
  })
  assert.deepEqual(
    notifications.map(item => item.is_read),
    [true, false]
  )

  applyNotificationReadState(notifications, { all: true, isRead: true })
  assert.deepEqual(
    notifications.map(item => item.is_read),
    [true, true]
  )
})

test('consumer notifications translate live without changing store names or order references', () => {
  const language = useConsumerLanguage()
  const notification = {
    title: 'Order Preparing',
    message: "Store 'Aling Nena's Store' is now preparing your order #42."
  }
  const display = computed(() =>
    consumerNotificationText(notification, language.t)
  )
  language.setLanguage('en')
  assert.equal(display.value.title, 'Order Preparing')
  language.setLanguage('fil')
  assert.equal(display.value.title, 'Inihahanda ang Order')
  assert.equal(
    display.value.message,
    "Inihahanda na ng Aling Nena's Store ang order mo #42."
  )
  const fixtures = [
    [
      'Order Ready',
      "Your order #42 is ready for pickup at 'Nena Store'.",
      'Ready na ang order mo #42 para kunin sa Nena Store.'
    ],
    [
      'Order Picked Up',
      'Your order #42 has been picked up. Thank you!',
      'Nakuha mo na ang order #42. Salamat!'
    ],
    [
      'Order Cancelled',
      "Your order #42 at 'Nena Store' was cancelled.",
      'Kinansela ang order mo #42 sa Nena Store.'
    ],
    [
      'Order Cancelled',
      'You successfully cancelled your order #42.',
      'Na-cancel mo na ang order #42.'
    ],
    [
      'Order Cancelled',
      'Your order #42 was cancelled. Reason: Vendor did not prepare the order in time.',
      'Kinansela ang order mo #42. Dahilan: Hindi naihanda ng tindahan ang order sa takdang oras.'
    ],
    [
      'Order Cancelled',
      'Your order #42 was cancelled. Reason: Special reason {id}',
      'Kinansela ang order mo #42. Dahilan: Special reason {id}'
    ]
  ]
  for (const [title, message, expected] of fixtures) {
    assert.equal(
      consumerNotificationText({ title, message }, language.t).message,
      expected
    )
  }
  assert.deepEqual(
    consumerNotificationText(
      {
        title: 'Custom announcement',
        message: 'Store name and custom content'
      },
      language.t
    ),
    { title: 'Custom announcement', message: 'Store name and custom content' }
  )
  assert.equal(notification.title, 'Order Preparing')
  assert.equal(
    notification.message,
    "Store 'Aling Nena's Store' is now preparing your order #42."
  )
  language.setLanguage('en')
  assert.equal(display.value.title, 'Order Preparing')
})
