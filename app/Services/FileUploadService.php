<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileUploadService
{
    private const DISK = 'local';

    /**
     * Store an uploaded file and return its storage path.
     * Deletes the existing file at the same path if one exists.
     */
    public function store(UploadedFile $file, string $directory, string $filename): string
    {
        $path = "{$directory}/{$filename}";

        $this->delete($path);

        Storage::disk(self::DISK)->putFileAs($directory, $file, $filename);

        return $path;
    }

    /**
     * Delete a file from storage if it exists.
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Stream a file as a download response.
     * Throws 404 if path is null (no report uploaded) or the file is missing on disk.
     */
    public function streamDownload(?string $path, string $downloadFilename): StreamedResponse
    {
        if (!$path) {
            throw new NotFoundHttpException('No report has been uploaded yet.');
        }

        if (!Storage::disk(self::DISK)->exists($path)) {
            throw new NotFoundHttpException('Report file not found on disk.');
        }

        return Storage::disk(self::DISK)->download($path, $downloadFilename);
    }

    /**
     * Check whether a file exists at the given path.
     */
    public function exists(?string $path): bool
    {
        return $path && Storage::disk(self::DISK)->exists($path);
    }
}
