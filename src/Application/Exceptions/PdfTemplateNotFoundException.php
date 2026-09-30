<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Application\Exceptions;

final class PdfTemplateNotFoundException extends PdfGenerationException
{
    public static function forView(string $view): self
    {
        return new self("La plantilla blade [{$view}] no existe.");
    }
}
