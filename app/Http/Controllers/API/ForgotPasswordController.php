<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRules;
use Illuminate\Contracts\Auth\CanResetPassword;

class ForgotPasswordController extends Controller
{
    /**
     * Handle an incoming password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email', // Pastikan email ada di tabel users
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Kirim link reset password
        // Argumen kedua untuk Password::sendResetLink adalah sebuah Closure
        // yang bisa Anda gunakan jika ingin mengkustomisasi pengiriman email, tapi defaultnya sudah cukup
        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return response()->json(['message' => trans($status)]); // Pesan sukses default dari Laravel
        }

        // Jika email tidak ditemukan atau gagal mengirim (meskipun validasi 'exists' sudah ada)
        // Sebaiknya tidak memberi tahu secara eksplisit apakah email ada atau tidak untuk keamanan
        return response()->json(['message' => trans($status)], 400); // atau 422 jika dianggap error validasi
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => [
                'required',
                'string',
                PasswordRules::min(8)->letters(), // Aturan password kuat
                'confirmed', // Akan memeriksa field 'password_confirmation'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 'password' di sini adalah nama 'broker' dari config/auth.php, biasanya 'users'
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (CanResetPassword $user, string $password) {
                // Closure ini akan dieksekusi setelah token diverifikasi dan sebelum password baru disimpan
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json(['message' => trans($status)]);
        }

        // Jika token tidak valid atau error lain
        return response()->json(['message' => trans($status)], 400);
    }
}