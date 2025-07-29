<?php
// app/Models/DeviceDailySummary.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceDailySummary extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'device_id',
        'summary_date',
        'samples_count',
        'avg_watt',
        'min_watt',
        'max_watt',
        'avg_temperature',
        'avg_voltage',
        'avg_current',
        'total_kwh'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'summary_date' => 'date:Y-m-d', // Kirim sebagai string tanggal 'YYYY-MM-DD'
        'samples_count' => 'integer',
        'avg_watt' => 'float',
        'min_watt' => 'float',
        'max_watt' => 'float',
        'avg_temperature' => 'float',
        'avg_voltage' => 'float',
        'avg_current' => 'float',
        'total_kwh' => 'float',
    ];
}