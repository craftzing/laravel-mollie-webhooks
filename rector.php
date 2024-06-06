<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->parallel();
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->sets([
        \Rector\PHPUnit\Set\PHPUnitSetList::PHPUNIT_100,
    ]);

    $rectorConfig->skip([
        \Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class,
    ]);
};
