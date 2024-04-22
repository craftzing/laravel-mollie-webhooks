<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->parallel();
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->sets([
        \Rector\Set\ValueObject\LevelSetList::UP_TO_PHP_81,
    ]);

    $rectorConfig->skip([
        \Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class,
    ]);
};
