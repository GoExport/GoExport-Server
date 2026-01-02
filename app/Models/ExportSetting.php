<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Orchid\Screen\AsSource;

class ExportSetting extends Model
{
    use AsSource;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Cache key prefix for settings.
     */
    protected const CACHE_PREFIX = 'export_setting_';

    /**
     * Cache duration in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX . $key,
            self::CACHE_TTL,
            function () use ($key, $default) {
                $setting = static::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            }
        );
    }

    /**
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     * @return static
     */
    public static function set(string $key, mixed $value, ?string $description = null): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ]
        );

        Cache::forget(self::CACHE_PREFIX . $key);

        return $setting;
    }

    /**
     * Get available aspect ratios.
     *
     * @return array
     */
    public static function getAspectRatios(): array
    {
        return self::get('aspect_ratios', [
            '4:3' => '4:3 (Standard)',
            '16:9' => '16:9 (Widescreen)',
            '14:9' => '14:9 (Classic)',
            '9:16' => '9:16 (Vertical)',
        ]);
    }

    /**
     * Get available resolutions.
     *
     * @return array
     */
    public static function getResolutions(): array
    {
        return self::get('resolutions', [
            '240p' => '240p (Low)',
            '360p' => '360p (SD)',
            '420p' => '420p',
            '480p' => '480p',
            '720p' => '720p (HD)',
            '1080p' => '1080p (Full HD)',
            '1440p' => '1440p (2K)',
            '2k' => '2K',
            '4k' => '4K (Ultra HD)',
            '5k' => '5K',
            '8k' => '8K',
        ]);
    }

    /**
     * Get aspect ratio keys for validation.
     *
     * @return array
     */
    public static function getAspectRatioKeys(): array
    {
        return array_keys(self::getAspectRatios());
    }

    /**
     * Get resolution keys for validation.
     *
     * @return array
     */
    public static function getResolutionKeys(): array
    {
        return array_keys(self::getResolutions());
    }

    /**
     * Clear all settings cache.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        $keys = [
            'aspect_ratios',
            'resolutions',
            'obs_websocket_address',
            'obs_websocket_port',
            'obs_websocket_password',
            'obs_fps',
            'obs_no_overwrite',
            'obs_required',
            'load_timeout',
            'video_timeout',
            'force_outro',
            'purge_after_days',
        ];

        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }

    /**
     * Get GoExport CLI settings.
     *
     * @return array
     */
    public static function getCliSettings(): array
    {
        return [
            'obs_websocket_address' => self::get('obs_websocket_address', ''),
            'obs_websocket_port' => self::get('obs_websocket_port', ''),
            'obs_websocket_password' => self::get('obs_websocket_password', ''),
            'obs_fps' => self::get('obs_fps', ''),
            'obs_no_overwrite' => self::get('obs_no_overwrite', false),
            'obs_required' => self::get('obs_required', false),
            'load_timeout' => self::get('load_timeout', 30),
            'video_timeout' => self::get('video_timeout', 0),
            'force_outro' => self::get('force_outro', false),
        ];
    }

    /**
     * Get purge after days setting.
     *
     * @return int
     */
    public static function getPurgeAfterDays(): int
    {
        return (int) self::get('purge_after_days', 30);
    }
}
