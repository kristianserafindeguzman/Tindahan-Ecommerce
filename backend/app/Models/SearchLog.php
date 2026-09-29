<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    protected $table = 'search_logs';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'consumer_id',
        'category_id',
        'search_query',
        'search_lat',
        'search_lng',
        'searched_at',
    ];

    protected $casts = [
        'search_lat' => 'decimal:8',
        'search_lng' => 'decimal:8',
        'searched_at' => 'datetime',
    ];
}