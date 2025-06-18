<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceTrendingData extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'device_trending_data'; // Mendefinisikan nama tabel secara eksplisit

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'device_id'; //  Primary key bukan 'id'

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false; // Primary key bukan auto-increment

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false; // Tabel ini tidak memiliki created_at/updated_at standar

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',          // 
        'last_24h_kwh',       // 
        'last_7d_kwh',        // 
        'last_30d_kwh',       // 
        'current_efficiency', // 
        'last_updated',       // 
    ];

    /**
     * Get the device that owns the trending data.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}