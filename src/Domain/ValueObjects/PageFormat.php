<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Domain\ValueObjects;

enum PageFormat: string
{
    case Letter = 'Letter';
    case Legal = 'Legal';
    case A4 = 'A4';
}
