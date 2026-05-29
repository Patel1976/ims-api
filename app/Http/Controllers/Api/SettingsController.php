<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function getSettings()
    {
        $settings = Setting::all()->groupBy('group')->map(
            fn($group) => $group->pluck('value', 'key')
        );

        return response()->json(['success' => 1, 'data' => $settings], 200);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            // General
            'company_name'    => 'sometimes|string|max:255',
            'company_email'   => 'sometimes|email|max:255',
            'company_phone'   => 'sometimes|string|max:50',
            'company_address' => 'sometimes|string',
            'timezone'        => 'sometimes|string|max:50',
            // Currency & Tax
            'currency'        => 'sometimes|string|max:10',
            'tax_percentage'  => 'sometimes|numeric|min:0|max:100',
            // Stock
            'stock_alert_threshold' => 'sometimes|integer|min:1',
            // Invoice
            'invoice_prefix'  => 'sometimes|string|max:10',
            // Email
            'smtp_host'       => 'sometimes|string|max:255',
            'smtp_port'       => 'sometimes|string|max:10',
            'smtp_encryption' => 'sometimes|in:tls,ssl',
            'smtp_username'   => 'sometimes|string|max:255',
            'smtp_password'   => 'sometimes|string|max:255',
            // Security
            'two_factor_auth'    => 'sometimes|boolean',
            'session_timeout'    => 'sometimes|boolean',
            'login_notification' => 'sometimes|boolean',
        ]);

        foreach ($request->all() as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        ActivityLogService::log('Edit', 'Settings', 'Updated system settings');

        $settings = Setting::all()->groupBy('group')->map(
            fn($group) => $group->pluck('value', 'key')
        );

        return response()->json(['success' => 1, 'message' => 'Settings updated successfully', 'data' => $settings], 200);
    }

    public function testEmailConnection(Request $request)
    {
        $request->validate([
            'smtp_host'       => 'required|string',
            'smtp_port'       => 'required|string',
            'smtp_encryption' => 'required|in:tls,ssl',
            'smtp_username'   => 'required|string',
            'smtp_password'   => 'required|string',
        ]);

        try {
            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $request->smtp_host,
                (int) $request->smtp_port,
                $request->smtp_encryption === 'ssl'
            );
            $transport->setUsername($request->smtp_username);
            $transport->setPassword($request->smtp_password);
            $transport->start();

            return response()->json(['success' => 1, 'message' => 'SMTP connection successful'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => 0, 'message' => 'SMTP connection failed: ' . $e->getMessage()], 422);
        }
    }
}
