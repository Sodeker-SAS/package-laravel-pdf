<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Application\Exceptions;

use Throwable;

final class PdfRenderingFailedException extends PdfGenerationException
{
    public static function becauseOf(Throwable $previous): self
    {
        return new self("No fue posible generar el PDF: {$previous->getMessage()}", 0, $previous);
    }
}
