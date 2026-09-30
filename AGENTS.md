# Guía para consumir `sodeker/laravel-pdf` desde un módulo

Instrucciones para un agente de IA que deba **generar un PDF desde un módulo de negocio**
(Estudios, Nómina, Contratos…). No describe cómo modificar el paquete, sino cómo usarlo desde
fuera.

El ejemplo de referencia es el PDF de **resultado del estudio** de FINTEGRA, el primer documento
real hecho con el paquete.

---

## 1. Regla de oro

> **Un módulo consumidor depende de UN contrato, dos DTOs y la jerarquía de excepciones. Nunca de
> `Infrastructure/`, nunca de `Application\Services`, nunca de Browsershot.**

| Puedes importar | Para qué |
|---|---|
| `Contracts\GeneratePdfContract` | Pedir el PDF |
| `Application\DTOs\PdfPageOptionsDTO` | Formato, orientación, márgenes y timeout |
| `Application\DTOs\GeneratedPdfDTO` | Lo que recibes: binario, nombre, tamaño, base64 |
| `Domain\ValueObjects\PageFormat` / `PageOrientation` | Enums de las opciones de página |
| `Application\Exceptions\*` | Para traducir fallos a HTTP o a tu job |
| `Application\Ports\HtmlToPdfRenderer` / `TemplateRenderer` | **Solo en pruebas**, para sustituir los adaptadores |

**Prohibido importar**: `Application\Services\GeneratePdfFromViewService`,
`Infrastructure\*` y `Spatie\Browsershot\*`. Son detalles internos que cambian en
versiones PARCHE.

El contrato se resuelve por inyección de constructor. El provider del paquete ya lo enlaza:

```php
use Sodeker\Pdf\Contracts\GeneratePdfContract;

public function __construct(
    private readonly GeneratePdfContract $pdfGenerator,
) {}
```

---

## 2. El paquete es una imprenta, no un diseñador

El paquete **no tiene maquetas, ni vistas, ni rutas**. No sabe qué es un estudio ni una nómina.
Recibe nombres de vistas Blade **de tu aplicación**, las renderiza con la misma data y devuelve
el binario.

```
resources/views/pdf/
├── studies/result.blade.php     ← cuerpo, propio de tu módulo
└── shared/
    ├── footer.blade.php         ← pie, reutilizable por cualquier PDF de la app
    ├── header.blade.php         ← encabezado, opcional
    └── watermark.blade.php      ← parcial que el cuerpo incluye con @include
```

Reglas que se derivan de esto:

- **Tus maquetas viven en tu aplicación**, en `resources/views/pdf/`. Nunca propongas agregar una
  vista, una ruta de demo o un pie por defecto al paquete: hay una prueba que lo impide.
- **El título no es un parámetro.** Si la vista lo necesita, va dentro de `data`.
- **El paquete no guarda, no envía y no descarga.** Qué hacer con el `GeneratedPdfDTO` es
  decisión de tu módulo.

---

## 3. Generar un PDF

```php
use Sodeker\Pdf\Application\DTOs\GeneratedPdfDTO;
use Sodeker\Pdf\Application\DTOs\PdfPageOptionsDTO;
use Sodeker\Pdf\Domain\ValueObjects\PageFormat;

$pdf = $this->pdfGenerator->generate(
    view: 'pdf.studies.result',        // obligatorio
    data: $data,                       // la reciben el cuerpo, el encabezado y el pie
    footer: 'pdf.shared.footer',       // opcional
    fileName: 'resultado-estudio',     // opcional; se agrega .pdf. Por defecto 'documento.pdf'
    options: new PdfPageOptionsDTO(    // opcional; por defecto carta, vertical, 10/10/16/10 mm
        format: PageFormat::Letter,
        marginBottom: 25,              // deja espacio al pie: se imprime DENTRO del margen
    ),
);
```

Usa siempre **argumentos nombrados**: el orden posicional no forma parte del contrato.

### Qué hacer con el resultado

```php
// Ver en el navegador
return response($pdf->content, 200, [
    'Content-Type' => GeneratedPdfDTO::MIME_TYPE,
    'Content-Disposition' => "inline; filename=\"{$pdf->fileName}\"",
]);

// Descargar: lo mismo con `attachment` en vez de `inline`.

// Adjuntar a un correo o a una API externa
$pdf->toBase64();

// Guardarlo como adjunto: combinar con sodeker/laravel-attachments
// (AttachmentBinary a partir de $pdf->content). El paquete PDF no escribe en disco.
```

---

## 4. Preparar la data: lo que Chromium NO puede hacer

Chromium abre el HTML desde un archivo temporal, **sin la sesión del usuario y sin acceso a
archivos privados**. Todo lo que la vista necesite tiene que llegar dentro del HTML.

| Recurso | Cómo pasarlo |
|---|---|
| Imagen (logo, sello, foto) | Data URI: `'data:image/png;base64,'.base64_encode($bytes)` en `data` |
| Imagen en S3 o privada | Descárgala en PHP y conviértela en data URI **antes** de `generate()` |
| SVG (logo, ondas, marca de agua) | En línea en un parcial Blade. No necesita base64 |
| QR | Genéralo como SVG en PHP (p. ej. `bacon/bacon-qr-code`) e imprímelo con `{!! !!}` |
| CSS | En la vista, en `<style>`. No cargues hojas externas |

Base64 pesa ~33 % más que el archivo: incrusta imágenes livianas.

---

## 5. Encabezado y pie

Chromium los imprime en **un documento aislado, dentro del margen** de cada página:

- Estilos **en línea**: no ven el CSS del cuerpo.
- Imágenes como **data URI**.
- **`font-size` explícito** en cada elemento: el valor por defecto es diminuto.
- `<span class="pageNumber"></span>` y `<span class="totalPages"></span>` imprimen la paginación.
- El margen (`marginTop` / `marginBottom`) debe ser **mayor o igual** que el alto de la franja.
- Los fondos CSS (`background`) **no se imprimen**: usa SVG para franjas de color.
- Para llegar al borde de la hoja, reinicia el margen que agrega Chromium:
  `<style>html, body { margin: 0 !important; padding: 0 !important; } #footer { padding: 0 !important; }</style>`
  (`#header` para el encabezado).

### Marca de agua ≠ pie

| | Marca de agua | Pie |
|---|---|---|
| Dónde se declara | En el cuerpo, con `@include` | Parámetro `footer` de `generate()` |
| Cómo se repite | `position: fixed`: Chromium la dibuja en cada hoja | Chromium la imprime en cada margen |
| ¿Paginación? | No | Sí, `pageNumber` / `totalPages` |

**No pidas un parámetro `watermark` al paquete.** No hace falta: es HTML normal del cuerpo.

### Visores ligeros (vista previa de WhatsApp)

No aplican máscaras de transparencia y pintan bloques grises. Evita `box-shadow` con desenfoque,
degradados que terminan en transparente y `filter`. `opacity` sobre un elemento sí es seguro.

---

## 6. Errores

Todas las excepciones heredan de `PdfGenerationException`:

| Excepción | Cuándo | ¿Reintentar? |
|---|---|---|
| `PdfTemplateNotFoundException` | La vista del cuerpo, encabezado o pie no existe. Chromium no llega a arrancar | No: es un error de código |
| `PdfRenderingFailedException` | Chromium, puppeteer o Browsershot fallaron. Conserva la original en `previous` | Solo si fue timeout; si no, es infraestructura |

```php
try {
    $pdf = $this->pdfGenerator->generate(/* … */);
} catch (PdfGenerationException $e) {
    // No es culpa del usuario: presentarlo como fallo del sistema, no como error de validación.
    report($e);

    return back()->with('error', 'No fue posible generar el documento.');
}
```

El paquete ya registra en el log "Error al generar PDF con Browsershot" **sin el HTML** (puede
llevar datos personales). No vuelvas a loguear el HTML desde tu módulo.

---

## 7. Probar tu módulo sin Chromium

Los puertos de salida se registran con `bindIf`. En tus pruebas, sustituye el de Chromium:

```php
use Sodeker\Pdf\Application\Ports\HtmlToPdfRenderer;
use Sodeker\Pdf\Application\DTOs\PdfPageOptionsDTO;

$this->app->instance(HtmlToPdfRenderer::class, new class implements HtmlToPdfRenderer {
    public ?string $html = null;

    public function render(string $html, PdfPageOptionsDTO $options, ?string $headerHtml = null, ?string $footerHtml = null): string
    {
        $this->html = $html;

        return '%PDF-fake';
    }
});
```

Así verificas que tu vista recibe la data correcta sin depender de Node ni de Chromium en CI.
Las vistas se siguen renderizando con Blade de verdad, que es lo que quieres probar.

---

## 8. Checklist para un PDF nuevo

1. **Crear la vista del cuerpo** en `resources/views/pdf/<módulo>/`.
2. **Reutilizar el pie/encabezado** de `resources/views/pdf/shared/` si existe; si no, crearlo
   ahí para que otros PDFs lo usen.
3. **Armar la data en un servicio de aplicación**, con imágenes ya convertidas a data URI.
4. **Ajustar márgenes** en `PdfPageOptionsDTO` al alto del encabezado y del pie.
5. **Inyectar `GeneratePdfContract`** y llamar a `generate()` con argumentos nombrados.
6. **Decidir la salida** en el controlador o job: `inline`, `attachment`, base64 o adjunto.
7. **Capturar `PdfGenerationException`** y presentarlo como fallo del sistema.
8. **Prueba** con el renderer falso de la sección 7.

---

## 9. Antipatrones

| ❌ No hagas | ✅ Haz |
|---|---|
| Inyectar `GeneratePdfFromViewService` | Inyectar `GeneratePdfContract` |
| Llamar a Browsershot directamente | Pasar por el contrato |
| Agregar maquetas, rutas o un pie por defecto al paquete | Vistas en `resources/views/pdf/` de la app |
| `<img src="https://…">` o una URL de S3 | Data URI construido en PHP |
| CSS externo en encabezado o pie | Estilos en línea |
| Margen inferior menor que el pie | `marginBottom` ≥ alto del pie |
| Pedir un parámetro `watermark` | `@include` en el cuerpo con `position: fixed` |
| Pasar `title` como argumento | Meterlo en `data` |
| Generar el PDF en cada visita a una ruta pública | Rutas con autorización; cada llamada arranca un Chromium |
| Loguear el HTML al fallar | Confiar en el log del paquete |

---

## 10. Referencias

- Contrato: `src/Contracts/GeneratePdfContract.php`.
- Guía extensa con arquitectura, flujo, configuración y soporte:
  `documentation/documentation-laravel-pdf.html`.
- Configuración y variables de entorno: `config/pdf.php` y `README.md`.
- Qué rompe y qué no al actualizar: `CHANGELOG.md`.
