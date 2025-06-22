<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceDataHourly extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'device_data_hourly';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'hour_timestamp',
        'watt_avg',
        'voltage_avg',
        'current_avg',
        'temperature_avg',
        'kwh_total',
        'cost_total',
    ];

    /**
     * Tipe data asli dari atribut model.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hour_timestamp' => 'datetime',
        'watt_avg' => 'float',
        'voltage_avg' => 'float',
        'current_avg' => 'float',
        'temperature_avg' => 'float',
        'kwh_total' => 'float',
        'cost_total' => 'float', // atau 'decimal:2' agar sesuai dengan migrasi
    ];
}