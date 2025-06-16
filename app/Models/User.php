<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['profile_photo_url'];


    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            // profile_photo_path disimpan sebagai 'profile_photos/namafile.jpg'
            // Kita perlu mengambil hanya nama filenya saja
            $filename = basename($this->profile_photo_path);
            return route('profile.photo.serve', ['filename' => $filename]);
        }
        return null; // Atau URL ke gambar default
    }

    public function sendPasswordResetNotification($token)
    {
        // URL ini akan menjadi basis untuk link reset di frontend Flutter Anda
        // Ganti 'https://your-flutter-app.com' dengan URL production Anda,
        // atau untuk development bisa jadi 'http://localhost:PORT_FLUTTER_ANDA'
        // atau URL Deep Link jika untuk mobile.
        // Token dan email akan ditambahkan sebagai query parameter.
        $resetUrl = config('app.frontend_url', 'http://localhost:55000') . '/reset-password'; // Contoh
        // Anda bisa menambahkan FRONTEND_URL ke file .env Anda

        $this->notify(new CustomResetPasswordNotification($token, $resetUrl));
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }


}