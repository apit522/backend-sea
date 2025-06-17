<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'timestamp', // <-- Tambahkan 'timestamp' ke fillable
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'timestamp' => 'datetime', // <-- Beritahu Laravel agar memperlakukan kolom ini sebagai objek DateTime/Carbon
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}