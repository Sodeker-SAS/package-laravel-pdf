<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Infrastructure\Renderers;

use Illuminate\Support\Facades\Log;
use Sodeker\Pdf\Application\DTOs\PdfPageOptionsDTO;
use Sodeker\Pdf\Application\Exceptions\PdfRenderingFailedException;
use Sodeker\Pdf\Application\Ports\HtmlToPdfRenderer;
use Sodeker\Pdf\Domain\ValueObjects\PageOrientation;
use Spatie\Browsershot\Browsershot;
use Throwable;

final class BrowsershotHtmlToPdfRenderer implements HtmlToPdfRenderer
{
    public function render(
        string $html,
        PdfPageOptionsDTO $options,
        ?string $headerHtml = null,
        ?string $footerHtml = null,
    ): string {
        try {
            $browsershot = Browsershot::html($html)
                ->setNodeBinary(config('pdf.browsershot.node_path'))
                ->setChromePath(config('pdf.browsershot.chrome_path'))
                ->setNodeModulePath(config('pdf.browsershot.node_modules_path'))
                ->setTemporaryHtmlDirectory($this->temporaryDirectory())
                // Chromium escribe su perfil (.config, .cache, .local) bajo HOME, y con PHP-FPM el
                // HOME de www-data suele ser la raíz del proyecto. HOME no se puede cambiar aquí
                // (Browsershot le da prioridad al entorno del proceso), pero las rutas XDG sí.
                ->setEnvironmentOptions([
                    'XDG_CONFIG_HOME' => $this->temporaryDirectory('config'),
                    'XDG_CACHE_HOME' => $this->temporaryDirectory('cache'),
                    'XDG_DATA_HOME' => $this->temporaryDirectory('data'),
                ])
                ->writeOptionsToFile()
                ->noSandbox()
                ->timeout($options->timeoutSeconds)
                ->setOption('protocolTimeout', $options->timeoutSeconds * 1000)
                ->addChromiumArguments([
                    'disable-setuid-sandbox',
                    'disable-dev-shm-usage',
                    'disable-gpu',
                    'no-sandbox',
                    'allow-file-access-from-files',
                ])
                ->format($options->format->value)
                ->margins($options->marginTop, $options->marginRight, $options->marginBottom, $options->marginLeft)
                ->showBackground();

            if ($options->orientation === PageOrientation::Landscape) {
                $browsershot->landscape();
            }

            if ($headerHtml !== null || $footerHtml !== null) {
                $browsershot
                    ->setOption('displayHeaderFooter', true)
                    ->headerHtml($headerHtml ?? '<div></div>')
                    ->footerHtml($footerHtml ?? '<div></div>');
            } else {
                $browsershot->setOption('displayHeaderFooter', false);
            }

            return $browsershot->pdf();
        } catch (Throwable $exception) {
            Log::error('Error al generar PDF con Browsershot', [
                'message' => $exception->getMessage(),
                'html_bytes' => strlen($html),
            ]);

            throw PdfRenderingFailedException::becauseOf($exception);
        }
    }

    private function temporaryDirectory(string $subdirectory = ''): string
    {
        $path = rtrim(config('pdf.temporary_directory').'/'.$subdirectory, '/');

        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }

        return $path;
    }
}
