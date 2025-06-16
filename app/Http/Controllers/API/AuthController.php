<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed', // 'password_confirmation' field is needed
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Buat token (Laravel Sanctum direkomendasikan untuk API)
        // Pastikan Anda sudah menginstal Sanctum: composer require laravel/sanctum
        // Dan sudah menjalankan: php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
        // Dan sudah menambahkan HasApiTokens trait di User model
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::attempt($request->only('name', 'password'))) {
            return response()->json(['message' => 'Unauthorized, credentials do not match'], 401);
        }

        $user = User::where('name', $request->name)->firstOrFail();
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        // Jika menggunakan Sanctum, token bisa dihapus
        // $request->user()->currentAccessToken()->delete(); // Jika ingin logout dari device ini saja
        // $request->user()->tokens()->delete(); // Jika ingin logout dari semua device

        // Atau jika Anda mengelola token secara manual di client, client cukup menghapus tokennya.
        // Di backend, jika menggunakan session (kurang umum untuk API stateless), Anda bisa:
        // Auth::logout();
        // $request->session()->invalidate();
        // $request->session()->regenerateToken();

        // Untuk API token-based yang stateless, logout biasanya dihandle client-side dengan menghapus token.
        // Namun, backend bisa menyediakan endpoint untuk invalidasi token jika diperlukan.
        // Untuk Sanctum, menghapus token dari sisi server:
        if ($request->user()) { // Pastikan user terautentikasi untuk menghapus tokennya
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Successfully logged out']);
        }
        return response()->json(['message' => 'No active session to logout'], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            // Email harus unik, tapi abaikan email user saat ini
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'profile_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048', // 'sometimes' berarti opsional
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $validator->safe()->only(['name', 'email']);

        if ($request->hasFile('profile_photo')) {
            // Hapus foto lama jika ada
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            // Simpan foto baru
            // Nama file bisa dibuat unik, misal: $user->id . '_' . time() . '.' . $request->file('profile_photo')->getClientOriginalExtension()
            $path = $request->file('profile_photo')->store('profile_photos', 'public'); // Simpan di storage/app/public/profile_photos
            $updateData['profile_photo_path'] = $path;
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        // Mengambil ulang user model untuk mendapatkan profile_photo_url yang ter-update
        $updatedUser = User::find($user->id);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $updatedUser, // Kirim data user yang sudah terupdate
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user(); // Mendapatkan user yang terautentikasi

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                Password::min(8) // Contoh: minimal 8 karakter
                    ->letters(),     // Wajib ada huruf
                // ->mixedCase()    // Wajib ada huruf besar dan kecil
                // ->numbers()      // Wajib ada angka
                // ->symbols(),     // Wajib ada simbol (opsional, sesuaikan)
                'confirmed' // Akan memeriksa field 'new_password_confirmation'
            ],
            // 'new_password_confirmation' => 'required', // Tidak perlu didefinisikan eksplisit jika menggunakan 'confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verifikasi password saat ini
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password does not match'], 401); // Atau 422 dengan field error
        }

        // Update password user
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Opsional: Logout user dari semua device lain setelah ganti password
        // Auth::logoutOtherDevices($request->new_password); // Ini memerlukan fitur session guard, mungkin tidak relevan untuk API token

        return response()->json(['message' => 'Password changed successfully']);
    }
}