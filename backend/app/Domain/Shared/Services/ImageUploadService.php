<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service for handling image uploads.
 *
 * Manages file uploads for payment proofs, product images, and other
 * documents with support for local and S3 storage.
 */
class ImageUploadService
{
    /** @var string The disk to use for storage */
    private string $disk;

    public function __construct()
    {
        $this->disk = config('filesystems.default', 'local');
    }

    /**
     * Upload a payment proof image.
     *
     * @param  UploadedFile  $file
     * @param  string        $orderNumber
     * @return string        The URL/path to the uploaded file
     */
    public function uploadPaymentProof(UploadedFile $file, string $orderNumber): string
    {
        $directory = 'payment-proofs/' . date('Y/m');
        $filename = $this->generateFilename($file, $orderNumber);

        $path = $file->storeAs($directory, $filename, $this->disk);

        return $this->getUrl($path);
    }

    /**
     * Upload a generic image.
     *
     * @param  UploadedFile  $file
     * @param  string        $directory
     * @param  string|null   $prefix
     * @return string
     */
    public function uploadImage(UploadedFile $file, string $directory, ?string $prefix = null): string
    {
        $filename = $this->generateFilename($file, $prefix ?? 'img');
        $path = $file->storeAs($directory, $filename, $this->disk);

        return $this->getUrl($path);
    }

    /**
     * Delete a file if it exists.
     *
     * @param  string  $url
     * @return bool
     */
    public function deleteIfExists(string $url): bool
    {
        // Extract path from URL
        $path = $this->extractPathFromUrl($url);

        if (Storage::disk($this->disk)->exists($path)) {
            return Storage::disk($this->disk)->delete($path);
        }

        return false;
    }

    /**
     * Generate a unique filename.
     *
     * @param  UploadedFile  $file
     * @param  string        $prefix
     * @return string
     */
    private function generateFilename(UploadedFile $file, string $prefix): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(6);

        return "{$prefix}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get URL for a stored path.
     *
     * @param  string  $path
     * @return string
     */
    private function getUrl(string $path): string
    {
        return config('app.url') . '/storage/' . $path;
    }

    /**
     * Extract storage path from URL.
     *
     * @param  string  $url
     * @return string
     */
    private function extractPathFromUrl(string $url): string
    {
        // Remove the base URL to get the path
        $baseUrl = config('app.url') . '/storage/';

        return str_replace($baseUrl, '', $url);
    }
}
