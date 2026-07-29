<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes;

    /**
     * Custom primary key matching the data dictionary.
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'role',
        'full_name',
        'profile_picture',
        'phone_number',
        'email',
        'password_hash',
        'account_status',
        'suspension_message',
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Disable default timestamps since the table only has created_at.
     */
    public $timestamps = false;
    const UPDATED_AT = null;

    /**
     * Override the default auth password column.
     * Laravel's Auth::attempt() and Hash::check() look for getAuthPassword().
     * Our column is 'password_hash' instead of the default 'password'.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_hash'    => 'hashed',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * A Vendor user owns one store.
     */
    public function store()
    {
        return $this->hasOne(Store::class, 'owner_id', 'user_id');
    }

    /**
     * Check if the account is active (not suspended/deleted/inactive).
     */
    public function isActive(): bool
    {
        return $this->account_status === 'active';
    }

    /**
     * Append custom attributes.
     */
    protected $appends = ['profile_picture_url'];

    /**
     * Resolve the profile picture URL safely.
     */
    protected function profilePictureUrl(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $pic = $attributes['profile_picture'] ?? null;
                if (!$pic || $pic === 'null' || trim($pic) === '') return null;
                return str_starts_with($pic, 'http') ? $pic : asset('storage/' . $pic);
            }
        );
    }
}
