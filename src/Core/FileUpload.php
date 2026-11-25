<?php

declare(strict_types=1);

namespace App\Core;

use finfo;

final class FileUpload
{
    public const MAX_BYTES = 10485760; // 10 MB

    /** @var array<string, array<int, string>> */
    private const ALLOWED_MIME_MAP = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'txt' => ['text/plain', 'application/octet-stream'],
    ];

    /**
     * @param array<string, mixed> $file
     * @return array{file_path: string, file_name: string, file_size_bytes: int, mime_type: string}
     */
    public static function store(array $file, string $subdir): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Ukuran berkas melebihi batas maksimal 10 MB.',
                422,
                ['file' => ['Ukuran berkas maksimal 10 MB.']]
            );
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Berkas wajib diunggah dan harus valid.',
                422,
                ['file' => ['Unggahan berkas gagal atau tidak ditemukan.']]
            );
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $originalName = basename((string) ($file['name'] ?? 'file'));
        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Ukuran berkas harus antara 1 byte hingga 10 MB.',
                422,
                ['file' => ['Ukuran berkas maksimal 10 MB.']]
            );
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === '' || !isset(self::ALLOWED_MIME_MAP[$ext])) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Ekstensi berkas tidak diizinkan. Gunakan pdf, doc, docx, ppt, pptx, xls, xlsx, zip, jpg, png, atau txt.',
                422,
                ['file' => ['Tipe berkas tidak diizinkan.']]
            );
        }

        if ($tmpPath === '' || !is_file($tmpPath)) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Berkas sementara unggahan tidak ditemukan.',
                422,
                ['file' => ['Berkas unggahan tidak valid.']]
            );
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = (string) ($finfo->file($tmpPath) ?: 'application/octet-stream');

        $allowedMimes = self::ALLOWED_MIME_MAP[$ext];
        if (!in_array($detectedMime, $allowedMimes, true)) {
            throw new HttpException(
                'VALIDATION_ERROR',
                "Tipe konten berkas ({$detectedMime}) tidak sesuai dengan ekstensi .{$ext}.",
                422,
                ['file' => ['Konten berkas tidak sesuai dengan ekstensi yang diizinkan.']]
            );
        }

        $cleanSubdir = trim($subdir, '/\\');
        $targetDir = self::uploadsRoot() . DIRECTORY_SEPARATOR . $cleanSubdir;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new HttpException('SERVER_ERROR', 'Gagal menyiapkan direktori penyimpanan berkas.', 500);
        }

        $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
        $relativePath = $cleanSubdir . '/' . $randomName;
        $absoluteTarget = $targetDir . DIRECTORY_SEPARATOR . $randomName;

        $moved = is_uploaded_file($tmpPath)
            ? move_uploaded_file($tmpPath, $absoluteTarget)
            : rename($tmpPath, $absoluteTarget);

        if (!$moved) {
            throw new HttpException('SERVER_ERROR', 'Gagal menyimpan berkas unggahan ke penyimpanan.', 500);
        }

        return [
            'file_path' => $relativePath,
            'file_name' => $originalName,
            'file_size_bytes' => $size,
            'mime_type' => $detectedMime,
        ];
    }

    public static function absolutePath(string $relativePath): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativePath, '/\\'));
        return self::uploadsRoot() . DIRECTORY_SEPARATOR . $normalized;
    }

    public static function delete(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        $abs = self::absolutePath($relativePath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    public static function formatBytes(?int $bytes): ?string
    {
        if ($bytes === null || $bytes <= 0) {
            return null;
        }
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return number_format($bytes / 1048576, 1) . ' MB';
    }

    private static function uploadsRoot(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
    }
}
