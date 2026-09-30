<?php

declare(strict_types=1);

use Sodeker\Pdf\Application\DTOs\PdfPageOptionsDTO;
use Sodeker\Pdf\Application\Exceptions\PdfTemplateNotFoundException;
use Sodeker\Pdf\Application\Ports\HtmlToPdfRenderer;
use Sodeker\Pdf\Application\Ports\TemplateRenderer;
use Sodeker\Pdf\Contracts\GeneratePdfContract;
use Sodeker\Pdf\Domain\ValueObjects\PageFormat;
use Sodeker\Pdf\Domain\ValueObjects\PageOrientation;
use Sodeker\Pdf\Tests\Support\FakeHtmlToPdfRenderer;
use Sodeker\Pdf\Tests\Support\SpyTemplateRenderer;
use Sodeker\Pdf\Tests\TestCase;

/*
| Pruebas del contrato genérico de generación de PDFs.
|
| Las vistas viven en `views/` junto a este archivo y se registran bajo el namespace
| `pdf-test`, igual que lo haría cualquier aplicación con sus propias maquetas: el módulo
| no aporta ninguna. El renderer es falso, así que no se necesita Chromium ni puppeteer.
*/

uses(TestCase::class);

beforeEach(function () {
    $this->renderer = new FakeHtmlToPdfRenderer;
    $this->app->instance(HtmlToPdfRenderer::class, $this->renderer);
    $this->app['view']->addNamespace('pdf-test', __DIR__.'/views');

    $this->generator = app(GeneratePdfContract::class);
});

it('genera el PDF solo con el cuerpo cuando no se envían encabezado ni pie', function () {
    $pdf = $this->generator->generate(view: 'pdf-test::body', data: ['title' => 'Documento X']);

    expect($pdf->content)->toBe('%PDF-1.7 fake-pdf-content')
        ->and($pdf->sizeInBytes())->toBeGreaterThan(0)
        ->and($this->renderer->capturedHtml)->toContain('Cuerpo: Documento X')
        ->and($this->renderer->capturedHeaderHtml)->toBeNull()
        ->and($this->renderer->capturedFooterHtml)->toBeNull();
});

it('renderiza encabezado y pie de la aplicación con la misma data del cuerpo', function () {
    $this->generator->generate(
        view: 'pdf-test::body',
        data: ['title' => 'Documento X'],
        header: 'pdf-test::header',
        footer: 'pdf-test::footer',
    );

    expect($this->renderer->capturedHeaderHtml)->toContain('Encabezado: Documento X')
        ->and($this->renderer->capturedFooterHtml)
        ->toContain('Pie: Documento X')
        ->toContain('pageNumber');
});

it('permite enviar solo el pie o solo el encabezado', function () {
    $this->generator->generate(view: 'pdf-test::body', data: ['title' => 'X'], footer: 'pdf-test::footer');

    expect($this->renderer->capturedHeaderHtml)->toBeNull()
        ->and($this->renderer->capturedFooterHtml)->toContain('Pie: X');

    $this->generator->generate(view: 'pdf-test::body', data: ['title' => 'X'], header: 'pdf-test::header');

    expect($this->renderer->capturedHeaderHtml)->toContain('Encabezado: X')
        ->and($this->renderer->capturedFooterHtml)->toBeNull();
});

it('entrega las opciones de página tal cual al renderer', function () {
    $options = new PdfPageOptionsDTO(
        format: PageFormat::A4,
        orientation: PageOrientation::Landscape,
        marginTop: 0,
        marginBottom: 25,
    );

    $this->generator->generate(view: 'pdf-test::body', data: ['title' => 'X'], options: $options);

    expect($this->renderer->capturedOptions)->toBe($options);
});

it('usa un nombre de archivo por defecto y asegura la extensión .pdf', function (?string $fileName, string $expected) {
    $pdf = $fileName === null
        ? $this->generator->generate(view: 'pdf-test::body', data: ['title' => 'X'])
        : $this->generator->generate(view: 'pdf-test::body', data: ['title' => 'X'], fileName: $fileName);

    expect($pdf->fileName)->toBe($expected);
})->with([
    'sin nombre' => [null, 'documento.pdf'],
    'sin extensión' => ['resultado-estudio', 'resultado-estudio.pdf'],
    'con extensión' => ['resultado-estudio.pdf', 'resultado-estudio.pdf'],
]);

it('falla sin llamar al renderer cuando alguna de las vistas no existe', function (array $views) {
    expect(fn () => $this->generator->generate(...$views, data: ['title' => 'X']))
        ->toThrow(PdfTemplateNotFoundException::class);

    expect($this->renderer->calls)->toBe(0);
})->with([
    'cuerpo' => [['view' => 'pdf-test::no-existe']],
    'encabezado' => [['view' => 'pdf-test::body', 'header' => 'pdf-test::no-existe']],
    'pie' => [['view' => 'pdf-test::body', 'footer' => 'pdf-test::no-existe']],
]);

it('valida todas las plantillas antes de renderizar cualquiera', function () {
    $templates = new SpyTemplateRenderer(existing: ['body', 'header']);
    $this->app->instance(TemplateRenderer::class, $templates);
    $this->app->forgetInstance(GeneratePdfContract::class);

    expect(fn () => app(GeneratePdfContract::class)->generate(view: 'body', header: 'header', footer: 'no-existe'))
        ->toThrow(PdfTemplateNotFoundException::class);

    expect($templates->rendered)->toBeEmpty()
        ->and($this->renderer->calls)->toBe(0);
});

it('no registra vistas ni rutas propias en la aplicación', function () {
    expect($this->app['view']->getFinder()->getHints())->not->toHaveKey('pdf-generation')
        ->and(collect(app('router')->getRoutes()->getRoutes())->map->uri()->filter(
            fn (string $uri) => str_contains($uri, 'pdf/demo')
        ))->toBeEmpty();
});
