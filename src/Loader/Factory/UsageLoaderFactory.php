<?php

declare(strict_types=1);

namespace Icanhazstring\Composer\Unused\Loader\Factory;

use Icanhazstring\Composer\Unused\Di\FactoryInterface;
use Icanhazstring\Composer\Unused\Di\ServiceContainer;
use Icanhazstring\Composer\Unused\Loader\Result;
use Icanhazstring\Composer\Unused\Loader\UsageLoader;
use Icanhazstring\Composer\Unused\Parser\PHP\PHPUsageParser;
use Psr\Container\ContainerInterface;

class UsageLoaderFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface&ServiceContainer $container
     */
    public function __invoke(ContainerInterface $container, array $options = null): UsageLoader
    {
        return new UsageLoader(
            [
                $container->build(PHPUsageParser::class, $options)
            ],
            new Result()
        );
    }
}
