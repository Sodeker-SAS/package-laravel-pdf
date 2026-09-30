# sodeker/laravel-pdf

Paquete Composer que concentra el módulo compartido de generación de PDFs de las aplicaciones
Laravel 12 de Sódeker. Convierte vistas blade en PDF con Browsershot (Chromium + puppeteer)
detrás de dos puertos: `TemplateRenderer` (Blade) y `HtmlToPdfRenderer` (Browsershot).

El paquete **no contiene maquetas**: el cuerpo, el encabezado y el pie son vistas de la
aplicación. No conoce estudios, contratos ni ningún otro tipo de documento. Tampoco registra
vistas ni rutas propias.

## Instalación

Declara este repositorio como VCS o como repositorio Composer de tipo `path` durante desarrollo
local y luego instala el paquete:

```bash
composer require sodeker/laravel-pdf
php artisan vendor:publish --tag=pdf-config   # opcional
```

Laravel descubre `Sodeker\Pdf\PdfServiceProvider` automáticamente.

Además del paquete, el servidor donde corre PHP necesita Node, Chromium y `puppeteer`
(`npm install puppeteer`). Ver [Configuración](#configuración).

### Migrar desde el módulo interno (`app/Modules/PdfGeneration`)

1. Instala el paquete y borra `app/Modules/PdfGeneration` y `app/Shared/Contracts/PdfGeneration`.
2. Quita `PdfGenerationServiceProvider` de `bootstrap/providers.php`.
3. Reemplaza los namespaces:
   en este orden (los dos primeros cambian de carpeta, no solo de prefijo):
   - `App\Modules\PdfGeneration\Domain\Exceptions\` → `Sodeker\Pdf\Application\Exceptions\`
   - `App\Modules\PdfGeneration\Application\Contracts\` → `Sodeker\Pdf\Application\Ports\`
   - `App\Shared\Contracts\PdfGeneration\` → `Sodeker\Pdf\Contracts\`
   - `App\Modules\PdfGeneration\` → `Sodeker\Pdf\`
4. Puedes quitar `browsershot` de `config/services.php`: el paquete lee las mismas variables de
   entorno desde su propia config (`config/pdf.php`), así que el `.env` no cambia.

## Uso

```php
use Sodeker\Pdf\Application\DTOs\GeneratedPdfDTO;
use Sodeker\Pdf\Contracts\GeneratePdfContract;

$pdf = $pdfGenerator->generate(
    view: 'pdf.studies.show',       // obligatorio
    data: $data,                    // la reciben el cuerpo, el encabezado y el pie
    header: 'pdf.shared.header',    // opcional
    footer: 'pdf.shared.footer',    // opcional
    fileName: 'resultado-estudio',  // opcional; por defecto 'documento.pdf'
);

return response($pdf->content, 200, [
    'Content-Type' => GeneratedPdfDTO::MIME_TYPE,
    'Content-Disposition' => "inline; filename=\"{$pdf->fileName}\"",
]);
```

Si una vista no existe se lanza `PdfTemplateNotFoundException`. Si Chromium falla se
lanza `PdfRenderingFailedException`. Ambas heredan de `PdfGenerationException`.

## Opciones de página

`options: new PdfPageOptionsDTO(...)` controla formato (`Letter`, `Legal`, `A4`),
orientación, márgenes en mm y timeout. Por defecto: carta, vertical y márgenes 10/10/16/10.

## Encabezado y pie

Chromium los imprime **dentro del margen** de cada página, en un documento aislado:

- Los estilos deben ir en línea (no se cargan hojas de estilo externas).
- Las imágenes deben ir como data URI.
- Hay que declarar `font-size` explícito; el valor por defecto es diminuto.
- `<span class="pageNumber"></span>` y `<span class="totalPages"></span>` imprimen la paginación.
- El margen superior o inferior tiene que ser suficiente para el alto del encabezado o del pie.
- Chromium le agrega margen y relleno por defecto a esa zona. Si el diseño debe llegar al borde de
  la hoja, reinícialos: `<style>html, body { margin: 0 !important; padding: 0 !important; } #footer { padding: 0 !important; }</style>`
  (`#header` para el encabezado).
- Los fondos CSS (`background`) no se imprimen ahí; usa SVG para franjas o formas de color.

## Configuración

`config/pdf.php` (publicable con `--tag=pdf-config`):

| Clave | Variable de entorno | Por defecto |
|---|---|---|
| `browsershot.node_path` | `NODE_BINARY` | `/usr/bin/node` |
| `browsershot.chrome_path` | `CHROME_PATH` | `/usr/bin/chromium` |
| `browsershot.node_modules_path` | `BROWSERSHOT_NODE_MODULES_PATH` | `base_path('node_modules')` |
| `temporary_directory` | `BROWSERSHOT_TEMPORARY_DIRECTORY` | `storage_path('app/browsershot')` |

`node_modules_path` debe apuntar a un `node_modules` que tenga `puppeteer` y que sea
visible **desde el contenedor donde corre PHP**. En Docker, si `node_modules` es un volumen
del contenedor `node`, el contenedor `php` no lo ve: móntale el mismo volumen o instala
puppeteer donde php lo encuentre.

## Renderer propio o falso en pruebas

El provider registra los puertos de salida con `bindIf`:

| Puerto (`Sodeker\Pdf\Application\Ports`) | Implementación por defecto |
|---|---|
| `TemplateRenderer` | `Infrastructure\Templates\BladeTemplateRenderer` |
| `HtmlToPdfRenderer` | `Infrastructure\Renderers\BrowsershotHtmlToPdfRenderer` |

Para usar otra herramienta, o un renderer falso en las pruebas de la aplicación (sin Chromium),
registra tu implementación:

```php
$this->app->instance(HtmlToPdfRenderer::class, new MiRendererFalso);
```

## Capas

| Capa | Contiene | Puede depender de |
|---|---|---|
| `Domain` | Value objects (`PageFormat`, `PageOrientation`) | Nada |
| `Application` | Caso de uso, DTOs, excepciones y puertos de salida | `Domain` |
| `Infrastructure` | Adaptadores: Blade y Browsershot | `Application`, `Domain`, Laravel, Spatie |
| `Contracts` | Frontera pública (`GeneratePdfContract`) | DTOs de `Application` |

`tests/Unit/PdfGeneration/ArchitectureTest.php` hace cumplir estas reglas.

## Compatibilidad con visores ligeros

Algunos visores (por ejemplo, la vista previa de WhatsApp) no aplican las máscaras de
transparencia (`/SMask`) del PDF y pintan bloques grises donde no los hay. Chromium las crea con:

- `box-shadow` con desenfoque: se guarda como imagen con máscara. Usa borde o sombra sin
  desenfoque en color opaco (`box-shadow: 0 3px 0 #C9D8EC`).
- Degradados que terminan en transparente (`rgba(…, 0)`): termina el degradado en un color opaco.
- `filter` (blur, drop-shadow): evítalo en elementos visibles.

`opacity` sobre un elemento (como la marca de agua) sí es seguro: queda como transparencia simple.

## Documentación del paquete

| Archivo | Para qué |
|---|---|
| [`documentation/documentation-laravel-pdf.html`](documentation/documentation-laravel-pdf.html) | Guía extensa: arquitectura, flujo de `generate()`, uso, encabezado y pie, instalación, pruebas, marca de agua y soporte. |
| [`AGENTS.md`](AGENTS.md) | Cómo generar un PDF desde un módulo de negocio: contrato, preparación de la data, errores, checklist y antipatrones. Escrito para que lo siga un agente de IA. |
| [`CHANGELOG.md`](CHANGELOG.md) | Qué cambió en cada versión y qué acción manual exige actualizar. |

## Desarrollo

```bash
composer install
vendor/bin/pest
vendor/bin/pint --test
```
