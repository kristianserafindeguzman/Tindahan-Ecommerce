<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocalizedPopularSearch extends Model
{
    protected $table = 'localized_popular_searches';
    protected $primaryKey = 'popular_search_id';
    public $timestamps = false;

    protected $fillable = [
        'lat_grid',
        'lng_grid',
        'search_query',
        'category_id',
        'search_count',
        'generated_at',
    ];
}
