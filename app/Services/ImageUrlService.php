<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUrlService
{
    private const CDN_PATH = 'uploads/';

    public static function url($imagePath): string
    {
        if (empty($imagePath)) {
            return '';
        }

        $imagePath = trim($imagePath);

        if (Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }

        $path = ltrim($imagePath, '/');
        $path = Str::startsWith($path, 'storage/') ? Str::after($path, 'storage/') : $path;

        if (Str::startsWith($path, self::CDN_PATH)) {
            return rtrim(config('services.cloudfront.cdn_url'), '/') . '/' . $path;
        }

        return asset(Storage::url($path));
    }
}