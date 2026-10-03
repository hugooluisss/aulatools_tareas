<?php

declare(strict_types=1);

namespace App\Shared;

final class ImageUpload
{
    public static function store(string $temporaryPath, int $size, string $directory, string $urlPrefix, string $filenamePrefix): string
    {
        if ($size < 1 || $size > 2 * 1024 * 1024) {
            throw new \InvalidArgumentException('Image must be no larger than 2 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!is_string($mime) || !isset($extensions[$mime])) {
            throw new \InvalidArgumentException('Invalid image type.');
        }
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Could not store image.');
        }
        $filename = $filenamePrefix . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
        if (!copy($temporaryPath, $directory . '/' . $filename)) {
            throw new \RuntimeException('Could not store image.');
        }
        return $urlPrefix . $filename;
    }

    public static function delete(string $path, string $urlPrefix, string $publicDirectory): void
    {
        if (str_starts_with($path, $urlPrefix) && preg_match('#^[a-zA-Z0-9._-]+$#D', substr($path, strlen($urlPrefix)))) {
            @unlink($publicDirectory . $path);
        }
    }
}
