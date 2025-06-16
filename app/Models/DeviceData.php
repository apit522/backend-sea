<?php
// app/Models/DeviceData.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class DeviceData extends Model
{
    use HasFactory;

    const UPDATED_AT = null;
    protected $fillable = [ /* ... */];

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        // Format tanggal menjadi string tanpa informasi timezone
        return $date->format('Y-m-d H:i:s');
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}