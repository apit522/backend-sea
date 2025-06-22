<?php
// app/Models/Device.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Device extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'location',
        'unique_id',
        'btu',
        'user_id',
        'last_seen_at',
        'daya_va',
        'tarif_per_kwh',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];


    /**
     * Get the user that owns the device.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function data(): HasMany
    {
        return $this->hasMany(DeviceData::class);
    }

    public function dailySummaries(): HasMany
    {
        return $this->hasMany(DeviceDailySummary::class);
    }

    public function monthlySummaries(): HasMany
    {
        return $this->hasMany(DeviceMonthlySummary::class);
    }

    public function trendingData(): HasOne
    {
        return $this->hasOne(DeviceTrendingData::class);
    }

    // public function commands(): HasMany
    // {
    //     return $this->hasMany(DeviceCommand::class);
    // }
}
