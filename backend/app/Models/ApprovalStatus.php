<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalStatus extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'approval_status';

    /**
     * Custom primary key matching the data dictionary.
     */
    protected $primaryKey = 'approval_id';

    /**
     * No timestamps columns on this table.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'store_id',
        'admin_id',
        'status',
        'rejection_reason',
        'reviewed_at',
    ];

    /**
     * The store this approval belongs to.
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    /**
     * The admin who reviewed this application (nullable until reviewed).
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id', 'user_id');
    }
}
