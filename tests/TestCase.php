<?php

declare(strict_types=1);

namespace Sodeker\Pdf\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Sodeker\Pdf\PdfServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  mixed  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [PdfServiceProvider::class];
    }
}
