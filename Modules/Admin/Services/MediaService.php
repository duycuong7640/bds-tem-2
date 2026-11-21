<?php

namespace Modules\Admin\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;
use RuntimeException;

class MediaService
{
    public function folder(?string $directory): string
    {
        return 'media/'.trim($directory ?: '/library', '/');
    }

    public function upload(UploadedFile $file, ?string $directory): bool
    {
        $mime = $file->getMimeType();
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => null,
        };
        $originalExtension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = $extension === 'jpg' ? ['jpg', 'jpeg'] : [$extension];
        if ($extension === null || ! in_array($originalExtension, $allowedExtensions, true)
            || preg_match('~(?:^|\.)(?:php[0-9]*|phtml|phar|cgi|pl|asp[x]?|jsp|html?|svg|js|exe)(?:\.|$)~i', $file->getClientOriginalName())) {
            return false;
        }

        $dimensions = @getimagesize($file->getPathname());
        if ($dimensions === false || $dimensions['mime'] !== $mime || $dimensions[0] * $dimensions[1] > 12000000) {
            return false;
        }

        try {
            // Bound image decoding to one frame and discard uploaded metadata.
            $images = new ImageManager(Driver::class, decodeAnimation: false, strip: true);
            $image = $images->decodePath($file->getPathname());
        } catch (DecoderException) {
            return false;
        }
        $path = $this->folder($directory).'/'.Str::uuid().'.'.$extension;
        $encoded = $image->encodeUsingFileExtension($extension);

        if (! Storage::disk('public')->put($path, (string) $encoded)) {
            throw new RuntimeException('Không thể lưu ảnh.');
        }

        return true;
    }

    public function deleteFolder(string $name, ?string $directory): bool
    {
        return Storage::disk('public')->deleteDirectory($this->folder($directory).'/'.$name);
    }

    public function delete(array $items, ?string $directory): bool
    {
        $folder = $this->folder($directory);

        return Storage::disk('public')->delete(array_map(fn ($name) => "$folder/$name", $items));
    }
}
