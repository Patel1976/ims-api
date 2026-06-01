<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Configure the mailer dynamically from DB settings,
     * falling back to .env values if DB settings are empty.
     */
    private function configureMailer(): void
    {
        $s = Setting::whereIn('key', [
            'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password',
        ])->pluck('value', 'key');

        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.host'       => $s['smtp_host']       ?: env('MAIL_HOST', '127.0.0.1'),
            'mail.mailers.smtp.port'       => $s['smtp_port']       ?: env('MAIL_PORT', 587),
            'mail.mailers.smtp.encryption' => $s['smtp_encryption'] ?: env('MAIL_ENCRYPTION', 'tls'),
            'mail.mailers.smtp.username'   => $s['smtp_username']   ?: env('MAIL_USERNAME'),
            'mail.mailers.smtp.password'   => $s['smtp_password']   ?: env('MAIL_PASSWORD'),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        $token = Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        // Get company name for email branding
        $companyName = Setting::where('key', 'company_name')->value('value') ?: 'Inventory System';
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:8080');
        $resetLink   = "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($request->email);

        try {
            $this->configureMailer();

            $body = "Hello,\n\n"
                  . "You requested a password reset for your {$companyName} account.\n\n"
                  . "Click the link below to reset your password:\n\n"
                  . "{$resetLink}\n\n"
                  . "This link expires in 60 minutes.\n\n"
                  . "If you did not request a password reset, please ignore this email.\n\n"
                  . "{$companyName}";

            Mail::raw($body, function ($message) use ($request, $companyName) {
                $message->to($request->email)
                        ->subject("Password Reset - {$companyName}");
            });

            return response()->json([
                'success' => 1,
                'message' => 'Password reset token has been sent to your email.',
                'data'    => ['email' => $request->email],
            ], 200);

        } catch (\Exception $e) {
            // Email failed — delete the token so it's not left dangling
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json([
                'success' => 0,
                'message' => 'Failed to send reset email. Please check SMTP settings. Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function validateToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json(['success' => 0, 'message' => 'This reset link has already been used or is invalid.'], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['success' => 0, 'message' => 'This reset link has expired.'], 422);
        }

        if (!Hash::check($request->token, $record->token)) {
            return response()->json(['success' => 0, 'message' => 'This reset link is invalid.'], 422);
        }

        return response()->json(['success' => 1, 'message' => 'Token is valid.'], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'token'    => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json(['success' => 0, 'message' => 'Invalid or expired reset token.'], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['success' => 0, 'message' => 'Reset token has expired. Please request a new one.'], 422);
        }

        if (!Hash::check($request->token, $record->token)) {
            return response()->json(['success' => 0, 'message' => 'Invalid reset token.'], 422);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['success' => 1, 'message' => 'Password reset successfully.'], 200);
    }
}
