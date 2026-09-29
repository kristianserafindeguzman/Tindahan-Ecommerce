<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumerPersonalization extends Model
{
    protected $table = 'consumer_personalizations';
    protected $primaryKey = 'personalization_id';
    public $timestamps = false;

    protected $fillable = [
        'consumer_id',
        'category_id',
        'predicted_future_searches',
        'generated_at',
    ];
}
