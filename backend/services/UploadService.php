<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Logger;
use App\Repositories\AssetRepositoryInterface;

/**
 * Validated, randomised local upload storage.
 *
 * Files never live in the web root. They are served through media.php by
 * unguessable asset id, with a realpath guard and MIME sniffing.
 */
final class UploadService
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private AssetRepositoryInterface $assets,
        private string $uploadsPath,
        private array $config
    ) {
    }

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    public function store(array $file, string $kind, ?string $ownerProfileId, ?string $userId): array
    {
        $kind = strtolower(trim($kind));
        if (!in_array($kind, ['photo', 'banner', 'certificate', 'sanction-letter', 'sanctionLetter', 'document'], true)) {
            throw ApiException::invalidFile('Unknown upload type.');
        }
        if ($kind === 'sanctionLetter') {
            $kind = 'sanction-letter';
        }

        $this->assertUploadOk($file);

        $isProfileMedia = in_array($kind, ['photo', 'banner'], true);
        $maxBytes = $isProfileMedia
            ? (int) ($this->config[$kind . '_max_bytes'] ?? 5 * 1024 * 1024)
            : (int) ($this->config['attachment_max_bytes'] ?? 3 * 1024 * 1024);

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw ApiException::invalidFile('The uploaded file is empty.');
        }
        if ($size > $maxBytes) {
            throw ApiException::invalidFile('The file is larger than the allowed ' . round($maxBytes / 1048576, 1) . ' MB limit.');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_file($tmpPath)) {
            throw ApiException::invalidFile('The upload could not be read.');
        }

        $mime = $this->detectMime($tmpPath);
        $allowed = $isProfileMedia
            ? (array) ($this->config['photo_mimes'] ?? [])
            : (array) ($this->config['attachment_mimes'] ?? []);

        if (!in_array($mime, $allowed, true)) {
            throw ApiException::invalidFile('This file type is not allowed. Use JPEG, PNG or WebP images' . ($isProfileMedia ? '.' : ', or PDF.'));
        }

        if (str_starts_with($mime, 'image/') && @getimagesize($tmpPath) === false) {
            throw ApiException::invalidFile('The image file appears to be corrupted.');
        }
        if ($mime === 'application/pdf') {
            $handle = @fopen($tmpPath, 'rb');
            $signature = $handle !== false ? (string) fread($handle, 5) : '';
            if ($handle !== false) {
                fclose($handle);
            }
            if ($signature !== '%PDF-') {
                throw ApiException::invalidFile('The PDF file appears to be corrupted.');
            }
        }

        $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
        ];
        $extension = $extensionMap[$mime] ?? 'bin';
        $relativeDir = gmdate('Y') . '/' . gmdate('m');
        $absoluteDir = $this->uploadsPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
        if (!is_dir($absoluteDir) && !@mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new \RuntimeException('Unable to create the upload directory.');
        }

        $storageName = bin2hex(random_bytes(16)) . '.' . $extension;
        $relativeKey = $relativeDir . '/' . $storageName;
        $destination = $absoluteDir . DIRECTORY_SEPARATOR . $storageName;

        if (!@move_uploaded_file($tmpPath, $destination)) {
            throw new \RuntimeException('Unable to store the uploaded file.');
        }
        @chmod($destination, 0640);

        $asset = [
            'id' => 'asset_' . bin2hex(random_bytes(12)),
            'ownerProfileId' => $ownerProfileId,
            'storageKey' => $relativeKey,
            'url' => '/media.php?id=',
            'mimeType' => $mime,
            'byteSize' => $size,
            'originalName' => $this->safeOriginalName((string) ($file['name'] ?? 'upload')),
            'kind' => $kind,
            'uploadedBy' => $userId,
            'createdAt' => gmdate('c'),
        ];
        $asset['url'] .= $asset['id'];

        $created = $this->assets->create($asset);
        Logger::info('Upload stored', [
            'assetId' => $created['id'],
            'ownerProfileId' => $ownerProfileId,
            'mimeType' => $mime,
            'byteSize' => $size,
            'kind' => $kind,
        ]);

        return $created;
    }

    /** @param array<string, mixed> $file */
    private function assertUploadOk(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_OK) {
            return;
        }

        $message = match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The uploaded file is too large.',
            UPLOAD_ERR_PARTIAL => 'The file upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Please choose a file to upload.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server upload directory is unavailable.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file.',
            default => 'The file upload failed.',
        };

        throw ApiException::invalidFile($message);
    }

    private function detectMime(string $path): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return strtolower($mime);
                }
            }
        }

        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($path);
            if (is_string($mime) && $mime !== '') {
                return strtolower($mime);
            }
        }

        throw ApiException::invalidFile('The server cannot determine the file type.');
    }

    private function safeOriginalName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]+/', '', $name) ?? '';
        $name = trim($name);

        return $name !== '' ? mb_substr($name, 0, 200) : 'upload';
    }
}
