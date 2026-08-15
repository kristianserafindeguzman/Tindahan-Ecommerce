<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * Custom primary key matching the data dictionary.
     */
    protected $primaryKey = 'order_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'consumer_id',
        'store_id',
        'total_amount',
        'status',
        'cancellation_reason',
        'consumer_latitude',
        'consumer_longitude',
    ];

    /**
     * Get the store that owns the order.
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    /**
     * Get the consumer that placed the order.
     */
    public function consumer()
    {
        return $this->belongsTo(User::class, 'consumer_id', 'user_id');
    }

    /**
     * Get the items for the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }
}
