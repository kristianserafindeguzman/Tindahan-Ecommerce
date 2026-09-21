<?php

return [
    'cart_reservation_minutes' => env('CART_RESERVATION_MINUTES', 30),
    'order_preparation_minutes' => env('ORDER_PREPARATION_MINUTES', 180),  // 3 hours
    'order_pickup_minutes' => env('ORDER_PICKUP_MINUTES', 720),            // 12 hours
];
