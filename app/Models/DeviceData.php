<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class DeviceData extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false; // <-- PENTING: Set ke false

    protected $fillable = [
        'device_id',
        'watt',
        'temperature',
        'voltage',
        'current',
        'timestamp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'watt' => 'decimal:2',
        'temperature' => 'decimal:2',
        'voltage' => 'decimal:2',
        'current' => 'decimal:2',
        'timestamp' => 'datetime',
    ];


    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function scopeCurrentMonth(Builder $query): Builder
    {
        return $query->whereBetween('timestamp', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    public function scopeLast24Hours(Builder $query): Builder
    {
        return $query->where('timestamp', '>=', now()->subDay());
    }


}