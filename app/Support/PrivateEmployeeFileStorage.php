<?php

namespace App\Support;

use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateEmployeeFileStorage
{
    /**
     * @return array{file_path: string, original_filename: string, mime_type: string, file_size: int}
     */
    public function store(UploadedFile $file, Employee $employee, string $category): array
    {
        $this->ensureKnownCategory($category);

        $extension = Str::lower($file->extension());
        $filename = Str::uuid().'.'.$extension;
        $path = $file->storeAs("employees/{$employee->id}/{$category}", $filename, 'local');

        if (! is_string($path)) {
            throw new RuntimeException('The private employee file could not be stored.');
        }

        return [
            'file_path' => $path,
            'original_filename' => $this->safeOriginalFilename($file),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'file_size' => (int) $file->getSize(),
        ];
    }

    public function download(
        string $path,
        string $originalFilename,
        string $mimeType,
        Employee $employee,
        string $category,
    ): StreamedResponse {
        abort_unless($this->isManagedPath($path, $employee, $category), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $originalFilename, [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function delete(string $path, Employee $employee, string $category): void
    {
        if ($this->isManagedPath($path, $employee, $category)) {
            Storage::disk('local')->delete($path);
        }
    }

    public function exists(string $path, Employee $employee, string $category): bool
    {
        return $this->isManagedPath($path, $employee, $category)
            && Storage::disk('local')->exists($path);
    }

    private function isManagedPath(string $path, Employee $employee, string $category): bool
    {
        $this->ensureKnownCategory($category);
        $directory = "employees/{$employee->id}/{$category}/";
        $filename = Str::after($path, $directory);

        return Str::startsWith($path, $directory)
            && $filename !== ''
            && ! str_contains($filename, '/')
            && ! str_contains($filename, '..');
    }

    private function safeOriginalFilename(UploadedFile $file): string
    {
        $filename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?? '';

        return Str::limit($filename !== '' ? $filename : 'employee-file', 240, '');
    }

    private function ensureKnownCategory(string $category): void
    {
        if (! in_array($category, ['contracts', 'documents'], true)) {
            throw new RuntimeException('Unknown private employee file category.');
        }
    }
}
