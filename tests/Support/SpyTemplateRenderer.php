<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Tests\Support;

use Sodeker\Pdf\Application\Ports\TemplateRenderer;

/**
 * Plantillas en memoria: registra cuáles se renderizaron para comprobar el orden del caso de uso.
 */
final class SpyTemplateRenderer implements TemplateRenderer
{
    /** @var list<string> */
    public array $rendered = [];

    /**
     * @param  list<string>  $existing
     */
    public function __construct(private readonly array $existing) {}

    public function exists(string $template): bool
    {
        return in_array($template, $this->existing, true);
    }

    public function render(string $template, array $data): string
    {
        $this->rendered[] = $template;

        return "<p>{$template}</p>";
    }
}
