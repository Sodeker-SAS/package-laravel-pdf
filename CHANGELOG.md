# Changelog

Todos los cambios relevantes de `sodeker/laravel-pdf`.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y el versionado es
[SemVer](https://semver.org/lang/es/).

## Qué se considera ruptura en este paquete

Superficie pública, cuyo cambio incompatible obliga a versión MAYOR:

- El contrato `GeneratePdfContract` y los puertos `HtmlToPdfRenderer` y `TemplateRenderer`
  (`Application\Ports`).
- Los DTOs `PdfPageOptionsDTO` y `GeneratedPdfDTO`, y los enums `PageFormat` y `PageOrientation`.
- La jerarquía de `PdfGenerationException` (`Application\Exceptions`).
- Las claves de `config/pdf.php` y sus variables de entorno.

## [1.0.0] - 2026-09-30

### Añadido

- Extracción del módulo `PdfGeneration` de la aplicación a un paquete Composer instalable.
- Namespace `Sodeker\Pdf`; el contrato público pasa a `Sodeker\Pdf\Contracts\GeneratePdfContract`.
- Config propia `config/pdf.php` (tag `pdf-config`) con las mismas variables de entorno que
  usaba `config/services.php` → `browsershot`, más `BROWSERSHOT_TEMPORARY_DIRECTORY`.
- Puerto `TemplateRenderer` con el adaptador `BladeTemplateRenderer`: la capa de aplicación ya no
  depende de Laravel. `ArchitectureTest` hace cumplir las reglas de dependencia entre capas.
- `HtmlToPdfRenderer` y `TemplateRenderer` se registran con `bindIf` para que la aplicación pueda
  sustituirlos.

### Cambiado respecto del módulo interno

- Las excepciones pasan de `Domain\Exceptions` a `Application\Exceptions`: son fallos del caso de
  uso (plantilla inexistente, Chromium caído), no reglas de negocio.
- El puerto `HtmlToPdfRenderer` pasa de `Application\Contracts` a `Application\Ports`, para no
  confundirlo con `Contracts\GeneratePdfContract`, que es la frontera pública.
- Las tres vistas se validan antes de renderizar cualquiera (antes se validaban una a una).

### Acción manual al migrar desde el módulo interno

- Reemplazar los namespaces. Las excepciones y el puerto cambian de carpeta, no solo de prefijo:
  ver la lista ordenada en el README, "Migrar desde el módulo interno".
- Quitar `PdfGenerationServiceProvider` de `bootstrap/providers.php`.
