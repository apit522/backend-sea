<?php
//app\Models\DeviceMonthlySummary.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceMonthlySummary extends Model
{
    use HasFactory;

    protected $table = 'device_monthly_summaries';
    public $timestamps = false;
    protected $fillable = [
        'device_id',
        'summary_year',
        'summary_month',
        'avg_watt',
        'total_kwh',
        'peak_watt',
        'avg_temperature',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'summary_year' => 'integer',
        'summary_month' => 'integer',
        'avg_watt' => 'float',
        'total_kwh' => 'float',
        'peak_watt' => 'float',
        'avg_temperature' => 'float',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}