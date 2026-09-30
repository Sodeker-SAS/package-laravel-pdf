<?php

declare(strict_types=1);

namespace Sodeker\Pdf;

use Illuminate\Support\ServiceProvider;
use Sodeker\Pdf\Application\Ports\HtmlToPdfRenderer;
use Sodeker\Pdf\Application\Ports\TemplateRenderer;
use Sodeker\Pdf\Application\Services\GeneratePdfFromViewService;
use Sodeker\Pdf\Contracts\GeneratePdfContract;
use Sodeker\Pdf\Infrastructure\Renderers\BrowsershotHtmlToPdfRenderer;
use Sodeker\Pdf\Infrastructure\Templates\BladeTemplateRenderer;

/**
 * Sin vistas ni rutas propias: las maquetas, encabezados y pies viven en
 * la aplicación que consume el contrato.
 */
final class PdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pdf.php', 'pdf');

        // Puertos de salida → adaptadores. `bindIf`: una aplicación puede registrar otra
        // implementación (o una falsa en sus pruebas) antes de que se resuelva el contrato.
        $this->app->bindIf(TemplateRenderer::class, BladeTemplateRenderer::class);
        $this->app->bindIf(HtmlToPdfRenderer::class, BrowsershotHtmlToPdfRenderer::class);

        $this->app->singleton(GeneratePdfContract::class, GeneratePdfFromViewService::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/pdf.php' => config_path('pdf.php'),
        ], 'pdf-config');
    }
}
