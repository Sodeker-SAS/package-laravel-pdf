<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Application\Ports;

use Sodeker\Pdf\Application\DTOs\PdfPageOptionsDTO;

/**
 * Puerto de conversión HTML → PDF. La capa de aplicación renderiza las
 * plantillas blade y este puerto solo convierte HTML ya renderizado,
 * de modo que la herramienta concreta (Browsershot) sea intercambiable.
 */
interface HtmlToPdfRenderer
{
    /**
     * @return string Contenido binario del PDF.
     */
    public function render(
        string $html,
        PdfPageOptionsDTO $options,
        ?string $headerHtml = null,
        ?string $footerHtml = null,
    ): string;
}
