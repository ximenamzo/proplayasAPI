<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FileUploadService
{
    public static function uploadImage(UploadedFile $image, string $folder, ?string $oldPath = null): string
    {
        $filename = Str::uuid() . '.webp';
        $path = "uploads/{$folder}/{$filename}";
    
        $manager = new ImageManager(new Driver());
        $webp = $manager->read($image->getPathname())->toWebp(85);
    
        Storage::disk('public')->put($path, $webp);
    
        if ($oldPath) {
            static::delete("uploads/{$folder}/" . basename($oldPath));
        }
    
        return $filename;
    }

    public static function uploadFile(UploadedFile $file, string $folder, ?string $oldPath = null): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "uploads/{$folder}/{$filename}";
    
        Storage::disk('public')->putFileAs("uploads/{$folder}", $file, $filename);
    
        if ($oldPath) {
            static::delete("uploads/{$folder}/" . basename($oldPath));
        }
    
        return $filename;
    }

    public static function delete(?string $filename, string $folder = ''): void
    {
        if (!$filename || !$folder) return;
        $path = "uploads/{$folder}/{$filename}";
        Storage::disk('public')->delete($path);
    }    
}