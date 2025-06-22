<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceMonthlySummary extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'device_monthly_summaries'; // Mendefinisikan nama tabel secara eksplisit

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false; // Tabel ini tidak memiliki created_at/updated_at

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',       // 
        'summary_year',    // 
        'summary_month',   // 
        'avg_watt',        // 
        'total_kwh',       // 
        'peak_watt',       // 
        'avg_temperature', // 
    ];

    /**
     * Get the device that owns the summary.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}