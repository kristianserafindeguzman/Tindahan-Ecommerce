<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Store extends Model
{
    use SoftDeletes;
    /**
     * Custom primary key matching the data dictionary.
     */
    protected $primaryKey = 'store_id';

    /**
     * No timestamps columns on this table.
     */
    public $timestamps = false;
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'owner_id',
        'store_name',
        'store_picture',
        'opening_time',
        'closing_time',
        'operating_days',
        'latitude',
        'longitude',
        'address',
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'operating_days' => 'array',
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

    /**
     * The inventory items belonging to this store.
     */
    public function inventory()
    {
        return $this->hasMany(Inventory::class, 'store_id', 'store_id');
    }

    /**
     * Append custom attributes.
     */
    protected $appends = ['store_picture_url'];

    /**
     * Resolve the store picture URL safely.
     */
    protected function storePictureUrl(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $pic = $attributes['store_picture'] ?? null;
                if (!$pic || $pic === 'null' || trim($pic) === '') return null;
                return str_starts_with($pic, 'http') ? $pic : asset('storage/' . $pic);
            }
        );
    }
}
