<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Application\DTOs;

final class GeneratedPdfDTO
{
    public const MIME_TYPE = 'application/pdf';

    /**
     * @param  string  $content  Contenido binario del PDF.
     */
    public function __construct(
        public readonly string $content,
        public readonly string $fileName,
    ) {}

    public function sizeInBytes(): int
    {
        return strlen($this->content);
    }

    public function toBase64(): string
    {
        return base64_encode($this->content);
    }
}
