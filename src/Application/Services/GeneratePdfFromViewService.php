<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Application\Services;

use Sodeker\Pdf\Application\DTOs\GeneratedPdfDTO;
use Sodeker\Pdf\Application\DTOs\PdfPageOptionsDTO;
use Sodeker\Pdf\Application\Exceptions\PdfTemplateNotFoundException;
use Sodeker\Pdf\Application\Ports\HtmlToPdfRenderer;
use Sodeker\Pdf\Application\Ports\TemplateRenderer;
use Sodeker\Pdf\Contracts\GeneratePdfContract;

/**
 * Coordina el caso de uso: valida las plantillas de la aplicación, las renderiza con
 * la misma data y entrega el HTML al puerto de conversión. No conoce tipos de
 * documento, no contiene maquetas propias y no depende de Blade ni de Browsershot.
 */
final class GeneratePdfFromViewService implements GeneratePdfContract
{
    public function __construct(
        private readonly TemplateRenderer $templates,
        private readonly HtmlToPdfRenderer $pdfRenderer,
    ) {}

    public function generate(
        string $view,
        array $data = [],
        ?string $header = null,
        ?string $footer = null,
        string $fileName = 'documento.pdf',
        PdfPageOptionsDTO $options = new PdfPageOptionsDTO,
    ): GeneratedPdfDTO {
        // Todas las plantillas se validan antes de renderizar cualquiera: una vista que
        // falta no debe costar el render de las demás ni, menos aún, arrancar Chromium.
        foreach (array_filter([$view, $header, $footer], fn (?string $t) => $t !== null) as $template) {
            $this->ensureTemplateExists($template);
        }

        $html = $this->templates->render($view, $data);
        $headerHtml = $header !== null ? $this->templates->render($header, $data) : null;
        $footerHtml = $footer !== null ? $this->templates->render($footer, $data) : null;

        $content = $this->pdfRenderer->render($html, $options, $headerHtml, $footerHtml);

        return new GeneratedPdfDTO($content, $this->withPdfExtension($fileName));
    }

    private function ensureTemplateExists(string $template): void
    {
        if (! $this->templates->exists($template)) {
            throw PdfTemplateNotFoundException::forView($template);
        }
    }

    private function withPdfExtension(string $fileName): string
    {
        return str_ends_with($fileName, '.pdf') ? $fileName : $fileName.'.pdf';
    }
}
