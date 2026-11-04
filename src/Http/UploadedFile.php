<?php
declare(strict_types=1);

namespace TinyPHP\Http;

use RuntimeException;

final class UploadedFile
{
    public function __construct(private readonly array $file) {}

    public function error(): int { return (int) ($this->file['error'] ?? UPLOAD_ERR_NO_FILE); }
    public function isValid(): bool
    {
        return $this->error() === UPLOAD_ERR_OK
            && isset($this->file['tmp_name'])
            && is_uploaded_file((string) $this->file['tmp_name']);
    }
    public function originalName(): string { return basename((string) ($this->file['name'] ?? '')); }
    public function size(): int { return (int) ($this->file['size'] ?? 0); }
    public function mimeType(): ?string
    {
        if (!$this->isValid()) return null;
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $this->file['tmp_name']);
        return is_string($mime) ? $mime : null;
    }
    public function extension(): ?string
    {
        $mime = $this->mimeType();
        $map = [
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif',
            'image/webp' => 'webp', 'application/pdf' => 'pdf',
            'text/plain' => 'txt',
        ];
        return $mime === null ? null : ($map[$mime] ?? null);
    }

    /**
     * Validate a file. Throws RuntimeException when a rule fails.
     * Supported rules: max_size (bytes), extensions (array), mime_types (array).
     */
    public function validate(array $rules = []): self
    {
        if (!$this->isValid()) {
            throw new RuntimeException('The upload is missing or invalid (code ' . $this->error() . ').');
        }
        if (isset($rules['max_size']) && $this->size() > (int) $rules['max_size']) {
            throw new RuntimeException('The uploaded file is too large.');
        }
        if (isset($rules['extensions']) &&
            !in_array($this->extension(), $rules['extensions'], true)) {
            throw new RuntimeException('This file extension is not allowed.');
        }
        if (isset($rules['mime_types']) &&
            !in_array($this->mimeType(), $rules['mime_types'], true)) {
            throw new RuntimeException('This file type is not allowed.');
        }
        return $this;
    }

    /** Store with a cryptographically random filename; destination should be non-public. */
    public function store(string $directory): string
    {
        if (!$this->isValid()) {
            throw new RuntimeException('Cannot store an invalid upload.');
        }
        $extension = $this->extension();
        if ($extension === null) {
            throw new RuntimeException('Unsupported file type; no safe extension mapping exists.');
        }
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create upload directory.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file((string) $this->file['tmp_name'], $destination)) {
            throw new RuntimeException('Could not move uploaded file.');
        }
        return $name;
    }
}
