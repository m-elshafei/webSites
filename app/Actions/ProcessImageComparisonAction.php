<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class ProcessImageComparisonAction
{
    /**
     * Process an uploaded image into 3 versions:
     * 1. Original (unmodified)
     * 2. Compressed (same format, ~75% quality)
     * 3. WebP (converted to webp, ~75% quality)
     *
     * @param  UploadedFile  $file
     * @return array<int, array<string, mixed>>
     */
    public function execute(UploadedFile $file): array
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->decodePath($file->getRealPath());

        $originalExtension = strtolower($file->getClientOriginalExtension());
        if (in_array($originalExtension, ['jpeg', 'jpg'])) {
            $originalExtension = 'jpg';
        } elseif ($originalExtension === '') {
            $originalExtension = 'jpg';
        }

        $uniqueHash = Str::uuid()->toString();
        $directory = 'images/comparisons';

        // 1. Original Image (unmodified)
        $originalFilename = "{$uniqueHash}_original.{$originalExtension}";
        $originalPath = "{$directory}/{$originalFilename}";
        Storage::disk('public')->putFileAs($directory, $file, $originalFilename);
        $originalBytes = $file->getSize();

        // 2. Compressed Image (same format, ~75% quality)
        $compressedFilename = "{$uniqueHash}_compressed.{$originalExtension}";
        $compressedPath = "{$directory}/{$compressedFilename}";
        $compressedEncoded = $image->encodeUsingFileExtension($originalExtension, quality: 75);
        $compressedBytes = strlen((string) $compressedEncoded);
        Storage::disk('public')->put($compressedPath, (string) $compressedEncoded);

        // 3. WebP Image (~75% quality)
        $webpFilename = "{$uniqueHash}_webp.webp";
        $webpPath = "{$directory}/{$webpFilename}";
        $webpEncoded = $image->encodeUsingFormat(Format::WEBP, quality: 75);
        $webpBytes = strlen((string) $webpEncoded);
        Storage::disk('public')->put($webpPath, (string) $webpEncoded);

        $width = $image->width();
        $height = $image->height();

        $originalSizeKb = round($originalBytes / 1024, 2);
        $compressedSizeKb = round($compressedBytes / 1024, 2);
        $webpSizeKb = round($webpBytes / 1024, 2);

        $compressedSavings = $originalBytes > 0
            ? round((($originalBytes - $compressedBytes) / $originalBytes) * 100, 2)
            : 0.0;

        $webpSavings = $originalBytes > 0
            ? round((($originalBytes - $webpBytes) / $originalBytes) * 100, 2)
            : 0.0;

        return [
            [
                'key' => 'original',
                'title' => 'الصورة الأصلية (Original)',
                'subtitle' => 'بدون أي تعديل أو ضغط',
                'format' => strtoupper($originalExtension),
                'dimensions' => "{$width} × {$height}",
                'path' => $originalPath,
                'url' => Storage::disk('public')->url($originalPath),
                'size_kb' => $originalSizeKb,
                'savings_percent' => 0.0,
                'is_winner' => false,
            ],
            [
                'key' => 'compressed',
                'title' => 'مضغوطة بنفس الصيغة (Compressed)',
                'subtitle' => 'جودة 75% مع الحفاظ على نفس الصيغة',
                'format' => strtoupper($originalExtension),
                'dimensions' => "{$width} × {$height}",
                'path' => $compressedPath,
                'url' => Storage::disk('public')->url($compressedPath),
                'size_kb' => $compressedSizeKb,
                'savings_percent' => $compressedSavings,
                'is_winner' => $compressedSavings > $webpSavings,
            ],
            [
                'key' => 'webp',
                'title' => 'محولة إلى صيغة WebP',
                'subtitle' => 'جودة 75% بتقنية ضغط الجيل الحديث',
                'format' => 'WEBP',
                'dimensions' => "{$width} × {$height}",
                'path' => $webpPath,
                'url' => Storage::disk('public')->url($webpPath),
                'size_kb' => $webpSizeKb,
                'savings_percent' => $webpSavings,
                'is_winner' => $webpSavings >= $compressedSavings && $webpSavings > 0,
            ],
        ];
    }
}
