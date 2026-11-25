<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Kompres dan simpan gambar ke storage sebagai WebP (atau fallback).
     *
     * @param UploadedFile $file File gambar dari request
     * @param string $folder Folder penyimpanan (misal: 'post-images', 'community-banners')
     * @param int $maxWidth Lebar maksimum gambar (default: 1280px)
     * @param int $quality Kualitas kompresi 1-100 (default: 80)
     * @param string $disk Storage disk (default: 'public')
     * @return string Path relatif file yang tersimpan
     */
    public static function compressAndStore(
        UploadedFile $file,
        string $folder,
        int $maxWidth = 1280,
        int $quality = 80,
        string $disk = 's3'
    ): string {
        $realPath = $file->getRealPath();

        // Coba baca file gambar menggunakan GD
        $sourceImage = false;
        if (function_exists('imagecreatefromstring') && file_exists($realPath)) {
            $contents = @file_get_contents($realPath);
            if ($contents !== false) {
                $sourceImage = @imagecreatefromstring($contents);
            }
        }

        // Jika GD gagal membaca atau tidak tersedia, fallback ke penyimpanan standar Laravel
        if (!$sourceImage) {
            return $file->store($folder, $disk);
        }

        // Koreksi orientasi EXIF (sering terjadi pada foto dari kamera HP)
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($realPath);
            if (!empty($exif['Orientation'])) {
                $sourceImage = match ((int) $exif['Orientation']) {
                    3 => imagerotate($sourceImage, 180, 0),
                    6 => imagerotate($sourceImage, -90, 0),
                    8 => imagerotate($sourceImage, 90, 0),
                    default => $sourceImage,
                };
            }
        }

        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        // Hitung proporsi dimensi baru jika melebihi maxWidth
        if ($origWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) round(($origHeight / $origWidth) * $newWidth);
        } else {
            $newWidth = $origWidth;
            $newHeight = $origHeight;
        }

        // Siapkan canvas baru
        $targetImage = imagecreatetruecolor($newWidth, $newHeight);

        // Pertahankan transparansi (untuk PNG & WebP dengan alpha channel)
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
        imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);

        // Resize gambar dengan algoritma resampling berkualitas tinggi
        imagecopyresampled(
            $targetImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $origWidth, $origHeight
        );

        // Nama file unik dengan ekstensi .webp
        $filename = Str::random(40) . '.webp';
        $relativePath = trim($folder, '/') . '/' . $filename;

        // Render hasil kompresi ke buffer
        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($targetImage, null, $quality);
        } else {
            imagejpeg($targetImage, null, $quality);
            $filename = Str::random(40) . '.jpg';
            $relativePath = trim($folder, '/') . '/' . $filename;
        }
        $compressedData = ob_get_clean();

        // Bersihkan memori GD
        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        // Simpan ke disk Laravel Storage
        Storage::disk($disk)->put($relativePath, $compressedData);

        return $relativePath;
    }

    /**
     * Hapus gambar dari storage jika file ada.
     *
     * @param string|null $path
     * @param string $disk
     * @return void
     */
    public static function delete(?string $path, string $disk = 's3'): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
