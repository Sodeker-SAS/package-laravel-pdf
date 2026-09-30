<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Contracts;

use Sodeker\Pdf\Application\DTOs\GeneratedPdfDTO;
use Sodeker\Pdf\Application\DTOs\PdfPageOptionsDTO;

interface GeneratePdfContract
{
    /**
     * Genera un PDF a partir de vistas blade que pertenecen a la aplicación.
     * El encabezado y el pie son opcionales y reciben la misma data que el cuerpo.
     *
     * @param  string  $view  Vista del cuerpo (ej. 'pdf.studies.show').
     * @param  array<string, mixed>  $data  Data que consumen las tres vistas.
     * @param  string|null  $header  Vista impresa en el encabezado de cada página.
     * @param  string|null  $footer  Vista impresa en el pie de cada página.
     * @param  string  $fileName  Nombre del archivo; se agrega `.pdf` si falta.
     */
    public function generate(
        string $view,
        array $data = [],
        ?string $header = null,
        ?string $footer = null,
        string $fileName = 'documento.pdf',
        PdfPageOptionsDTO $options = new PdfPageOptionsDTO,
    ): GeneratedPdfDTO;
}
