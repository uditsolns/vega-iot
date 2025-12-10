<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAreaAccess extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "user_area_access";

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ["user_id", "area_id", "granted_by"];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "granted_at" => "datetime",
        ];
    }

    /**
     * Get the user that owns this area access.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the area that this access grants.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the user who granted this access.
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "granted_by");
    }
}
