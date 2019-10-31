<?php

declare(strict_types=1);

namespace Icanhazstring\Composer\Unused\Loader;

use Composer\Composer;
use Icanhazstring\Composer\Unused\Parser\ParserInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UsageLoader implements LoaderInterface
{
    /** @var ParserInterface[] */
    private $parsers;
    /**
     * @var ResultInterface
     */
    private $usageResult;

    public function __construct(array $parsers, ResultInterface $usageResult)
    {
        $this->parsers = $parsers;
        $this->usageResult = $usageResult;
    }

    /**
     * @param Composer     $composer
     * @param SymfonyStyle $io
     *
     * @return ResultInterface
     */
    public function load(Composer $composer, SymfonyStyle $io): ResultInterface
    {
        $baseDir = dirname($composer->getConfig()->getConfigSource()->getName());

        foreach ($this->parsers as $parser) {
            $this->usageResult = $this->usageResult->merge($parser->scan($baseDir));
        }

        return $this->usageResult;
    }
}
