<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // Seed default settings
        $defaults = [
            // General
            ['key' => 'company_name',    'value' => 'Inventory Management System', 'group' => 'general'],
            ['key' => 'company_email',   'value' => 'admin@inventory.com',          'group' => 'general'],
            ['key' => 'company_phone',   'value' => '+1 234 567 890',               'group' => 'general'],
            ['key' => 'company_address', 'value' => '123 Main Street, New York',    'group' => 'general'],
            ['key' => 'timezone',        'value' => 'utc-5',                        'group' => 'general'],
            // Currency & Tax
            ['key' => 'currency',        'value' => 'USD', 'group' => 'currency'],
            ['key' => 'tax_percentage',  'value' => '0',   'group' => 'currency'],
            // Stock
            ['key' => 'stock_alert_threshold', 'value' => '10', 'group' => 'stock'],
            // Invoice
            ['key' => 'invoice_prefix', 'value' => 'INV', 'group' => 'invoice'],
            // Email (SMTP)
            ['key' => 'smtp_host',       'value' => '',    'group' => 'email'],
            ['key' => 'smtp_port',       'value' => '587', 'group' => 'email'],
            ['key' => 'smtp_encryption', 'value' => 'tls', 'group' => 'email'],
            ['key' => 'smtp_username',   'value' => '',    'group' => 'email'],
            ['key' => 'smtp_password',   'value' => '',    'group' => 'email'],
            // Security
            ['key' => 'two_factor_auth',    'value' => '1', 'group' => 'security'],
            ['key' => 'session_timeout',    'value' => '1', 'group' => 'security'],
            ['key' => 'login_notification', 'value' => '0', 'group' => 'security'],
        ];

        foreach ($defaults as $setting) {
            DB::table('settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
