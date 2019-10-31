<?php
declare(strict_types=1);

namespace Icanhazstring\Composer\Unused\Parser\PHP;

use Icanhazstring\Composer\Unused\Error\ErrorHandlerInterface;
use Icanhazstring\Composer\Unused\Loader\ResultInterface;
use Icanhazstring\Composer\Unused\Parser\ParserInterface;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class PHPUsageParser implements ParserInterface
{
    /** @var Parser */
    private $parser;
    /** @var NodeVisitor */
    private $visitor;
    /** @var ErrorHandlerInterface */
    private $errorHandler;
    /** @var LoggerInterface */
    private $logger;
    /** @var ResultInterface */
    private $usageResult;
    /** @var array */
    private $excludes;

    public function __construct(
        Parser $parser,
        NodeVisitor $visitor,
        ErrorHandlerInterface $errorHandler,
        LoggerInterface $logger,
        ResultInterface $usageResult,
        array $excludes = []
    ) {
        $this->parser = $parser;
        $this->visitor = $visitor;
        $this->errorHandler = $errorHandler;
        $this->logger = $logger;
        $this->usageResult = $usageResult;
        $this->excludes = $excludes;
    }

    public function scan(string $baseDir): ResultInterface
    {
        $finder = new Finder();

        /** @var SplFileInfo[] $files */
        $files = $finder
            ->files()
            ->name('*.php')
            ->in($baseDir)
            ->exclude(
                array_merge(['vendor'], $this->excludes)
            );

        $traverser = new NodeTraverser();
        $traverser->addVisitor($this->visitor);

//        $io->section(sprintf('Scanning files from basedir %s', $baseDir));

//        $io->progressStart(count($files));

        foreach ($files as $file) {
//            $io->progressAdvance();
            $this->visitor->setCurrentFile($file);
            $this->logger->debug(sprintf('Parsing file %s', $file->getPathname()));

            $nodes = $this->parser->parse($file->getContents(), $this->errorHandler) ?? [];

            if (!$nodes) {
                $this->usageResult->skipItem($file->getFilename(), 'Could not parse nodes');
                $this->logger->debug(sprintf('Could not parse nodes from file %s', $file->getFilename()));

                continue;
            }

            $traverser->traverse($nodes);
        }

//        $io->progressFinish();

        foreach ($this->visitor->getUsages() as $usage) {
            $this->usageResult->addItem($usage);
        }

        return $this->usageResult;
    }
}