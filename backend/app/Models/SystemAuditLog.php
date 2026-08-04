<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAuditLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'system_audit_logs';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'log_id';

    /**
     * Indicates if the model should be timestamped.
     * We only have created_at.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'admin_id',
        'action_performed',
        'created_at',
    ];

    /**
     * Get the admin who performed this action.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id', 'user_id');
    }
}
