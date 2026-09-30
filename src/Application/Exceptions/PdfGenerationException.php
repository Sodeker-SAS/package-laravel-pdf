<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Application\Exceptions;

use RuntimeException;

/**
 * En Application y no en Domain: ninguna de estas excepciones protege una regla de negocio.
 * Una plantilla que no existe es un error de integración de la aplicación consumidora, y un
 * fallo de Chromium es un fallo de infraestructura. Son los fallos del caso de uso "generar
 * un PDF", y por eso viven junto a él.
 */
class PdfGenerationException extends RuntimeException {}
