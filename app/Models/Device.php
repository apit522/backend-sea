<?php
// app/Models/Device.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Get the user that owns the device.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function data(): HasMany
    {
        return $this->hasMany(DeviceData::class);
    }
}