<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Infrastructure\Templates;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Sodeker\Pdf\Application\Ports\TemplateRenderer;

final class BladeTemplateRenderer implements TemplateRenderer
{
    public function __construct(
        private readonly ViewFactory $views,
    ) {}

    public function exists(string $template): bool
    {
        return $this->views->exists($template);
    }

    public function render(string $template, array $data): string
    {
        return $this->views->make($template, $data)->render();
    }
}
