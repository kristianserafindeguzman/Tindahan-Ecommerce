<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandForecast extends Model
{
    protected $table = 'demand_forecasts';
    protected $primaryKey = 'forecast_id';

    protected $fillable = [
        'store_id',
        'inventory_id',
        'forecast_date',
        'predicted_quantity',
        'generated_at'
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_quantity' => 'decimal:2',
        'generated_at' => 'datetime'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id', 'inventory_id');
    }
}
