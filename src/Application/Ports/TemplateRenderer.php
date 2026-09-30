<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Application\Ports;

/**
 * Puerto de renderizado de plantillas. La capa de aplicación decide qué plantillas
 * renderizar y en qué orden; este puerto solo las convierte en HTML, de modo que el
 * motor concreto (Blade) quede en Infrastructure.
 */
interface TemplateRenderer
{
    public function exists(string $template): bool;

    /**
     * @param  array<string, mixed>  $data
     * @return string HTML renderizado.
     */
    public function render(string $template, array $data): string;
}
