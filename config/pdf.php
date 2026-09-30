<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Browsershot (Chromium + puppeteer)
    |--------------------------------------------------------------------------
    |
    | Rutas que usa el renderer para convertir HTML en PDF. Todas deben ser
    | visibles DESDE EL CONTENEDOR DONDE CORRE PHP: en Docker, si `node_modules`
    | es un volumen del contenedor `node`, el contenedor `php` no lo ve.
    |
    | Las variables de entorno son las mismas que usaba el módulo cuando vivía
    | dentro de la aplicación (`config/services.php` → `browsershot`), así que
    | un `.env` existente sigue sirviendo sin cambios.
    |
    */

    'browsershot' => [
        'node_path' => env('NODE_BINARY', '/usr/bin/node'),
        'chrome_path' => env('CHROME_PATH', '/usr/bin/chromium'),
        'node_modules_path' => env('BROWSERSHOT_NODE_MODULES_PATH', base_path('node_modules')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Directorio temporal
    |--------------------------------------------------------------------------
    |
    | Donde Browsershot escribe el HTML temporal y Chromium su perfil
    | (XDG_CONFIG_HOME, XDG_CACHE_HOME, XDG_DATA_HOME). Debe ser escribible
    | por el usuario de PHP-FPM.
    |
    */

    'temporary_directory' => env('BROWSERSHOT_TEMPORARY_DIRECTORY', storage_path('app/browsershot')),

];
