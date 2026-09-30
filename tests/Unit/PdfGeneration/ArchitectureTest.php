<?php

declare(strict_types=1);

/*
| Reglas de dependencia entre capas. Si una falla, algo quedó en la capa equivocada:
| Domain no conoce a nadie, y Application solo conoce Domain y sus propios puertos.
*/

arch('domain no depende de otras capas ni del framework')
    ->expect('Sodeker\Pdf\Domain')
    ->not->toUse(['Sodeker\Pdf\Application', 'Sodeker\Pdf\Infrastructure', 'Illuminate', 'Spatie']);

arch('application no depende de infrastructure ni del framework')
    ->expect('Sodeker\Pdf\Application')
    ->not->toUse(['Sodeker\Pdf\Infrastructure', 'Illuminate', 'Spatie']);

arch('los puertos de salida son interfaces')
    ->expect('Sodeker\Pdf\Application\Ports')
    ->toBeInterfaces();
