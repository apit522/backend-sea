<?php
// app/Models/DeviceTrendingData.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceTrendingData extends Model
{
    use HasFactory;

    protected $table = 'device_trending_data';
    protected $primaryKey = 'device_id';
    // public $incrementing = false;

    public $timestamps = false;
    protected $fillable = [
        'device_id',
        'last_24h_kwh',
        'last_7d_kwh',
        'last_30d_kwh',
        'current_efficiency',
        'last_updated',
    ];

    protected $casts = [
        'last_24h_kwh' => 'float',
        'last_7d_kwh' => 'float',
        'last_30d_kwh' => 'float',
        'current_efficiency' => 'float',
        'last_updated' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        // Pastikan foreign key didefinisikan dengan benar jika namanya non-standar
        return $this->belongsTo(Device::class, 'device_id');
    }
}