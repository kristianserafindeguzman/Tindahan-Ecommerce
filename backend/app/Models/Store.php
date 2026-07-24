<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    /**
     * Custom primary key matching the data dictionary.
     */
    protected $primaryKey = 'store_id';

    /**
     * No timestamps columns on this table.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'owner_id',
        'store_name',
        'store_picture',
        'opening_time',
        'closing_time',
        'latitude',
        'longitude',
    ];

    /**
     * The vendor user who owns this store.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'user_id');
    }

    /**
     * The approval status record for this store.
     */
    public function approvalStatus()
    {
        return $this->hasOne(ApprovalStatus::class, 'store_id', 'store_id');
    }
}
