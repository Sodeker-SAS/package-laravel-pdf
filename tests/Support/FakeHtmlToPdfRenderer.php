<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Tests\Support;

use Sodeker\Pdf\Application\DTOs\PdfPageOptionsDTO;
use Sodeker\Pdf\Application\Ports\HtmlToPdfRenderer;

/**
 * Renderer falso: captura lo que el servicio le entrega a Browsershot.
 */
final class FakeHtmlToPdfRenderer implements HtmlToPdfRenderer
{
    public int $calls = 0;

    public ?string $capturedHtml = null;

    public ?string $capturedHeaderHtml = null;

    public ?string $capturedFooterHtml = null;

    public ?PdfPageOptionsDTO $capturedOptions = null;

    public function render(
        string $html,
        PdfPageOptionsDTO $options,
        ?string $headerHtml = null,
        ?string $footerHtml = null,
    ): string {
        $this->calls++;
        $this->capturedHtml = $html;
        $this->capturedHeaderHtml = $headerHtml;
        $this->capturedFooterHtml = $footerHtml;
        $this->capturedOptions = $options;

        return '%PDF-1.7 fake-pdf-content';
    }
}
