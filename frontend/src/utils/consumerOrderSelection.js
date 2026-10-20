export const CONSUMER_ORDER_SELECTED = 'consumer-order-selected'

export function selectConsumerOrder(orderId) {
  localStorage.setItem('consumer_selected_order_id', orderId)
  // Selecting another order can keep the same route, so route mounting is insufficient.
  window.dispatchEvent(new Event(CONSUMER_ORDER_SELECTED))
}
