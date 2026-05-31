<?php

namespace App\Services;

use App\Models\DeliverySetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DeliveryChargeService
{
    private const DEFAULT_STANDARD_AMOUNT = 120;
    private const DEFAULT_EXPRESS_AMOUNT = 200;

    public static function amount(?string $city = null, string $deliveryMethod = 'standard'): float
    {
        $options = self::options($city);

        return (float) $options[self::normalizeMethod($deliveryMethod)];
    }

    public static function options(?string $city = null): array
    {
        $settings = self::settings();

        return [
            'standard' => $settings['standard_delivery_charge'],
            'express' => $settings['express_delivery_charge'],
        ];
    }

    public static function standardAmount(?string $city = null): float
    {
        return (float) self::options($city)['standard'];
    }

    public static function settings(): array
    {
        $setting = self::currentSetting();

        if (! $setting) {
            return self::defaultSettings();
        }

        return [
            'standard_delivery_charge' => (float) $setting->standard_delivery_charge,
            'express_delivery_charge' => (float) $setting->express_delivery_charge,
        ];
    }

    public static function save(float $standardDeliveryCharge, float $expressDeliveryCharge): DeliverySetting
    {
        return DeliverySetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'standard_delivery_charge' => round($standardDeliveryCharge, 2),
                'express_delivery_charge' => round($expressDeliveryCharge, 2),
            ]
        );
    }

    private static function currentSetting(): ?DeliverySetting
    {
        if (! self::settingsTableExists()) {
            return null;
        }

        return DeliverySetting::query()->firstOrCreate(
            ['id' => 1],
            self::defaultSettings()
        );
    }

    private static function defaultSettings(): array
    {
        return [
            'standard_delivery_charge' => self::DEFAULT_STANDARD_AMOUNT,
            'express_delivery_charge' => self::DEFAULT_EXPRESS_AMOUNT,
        ];
    }

    private static function normalizeMethod(string $deliveryMethod): string
    {
        return $deliveryMethod === 'express' ? 'express' : 'standard';
    }

    private static function settingsTableExists(): bool
    {
        try {
            return Schema::hasTable('delivery_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
