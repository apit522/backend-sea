<?php
// app/Models/DeviceDailySummary.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DeviceDailySummary extends Model
{
    public $timestamps = false; // Tabel ini tidak memerlukan created_at/updated_at
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
}