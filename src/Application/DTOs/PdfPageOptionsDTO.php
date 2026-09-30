<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Application\DTOs;

use Sodeker\Pdf\Domain\ValueObjects\PageFormat;
use Sodeker\Pdf\Domain\ValueObjects\PageOrientation;

final class PdfPageOptionsDTO
{
    /**
     * Márgenes en milímetros. Si se usa encabezado o pie, el margen superior
     * o inferior debe dejarles espacio: Chromium los imprime dentro del margen.
     */
    public function __construct(
        public readonly PageFormat $format = PageFormat::Letter,
        public readonly PageOrientation $orientation = PageOrientation::Portrait,
        public readonly float $marginTop = 10,
        public readonly float $marginRight = 10,
        public readonly float $marginBottom = 16,
        public readonly float $marginLeft = 10,
        public readonly int $timeoutSeconds = 60,
    ) {}
}
