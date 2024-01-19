<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\ValueObject\Set\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withSets([SetList::COMMON, SetList::PSR_12, SetList::CLEAN_CODE, SetList::DOCBLOCK])
;
