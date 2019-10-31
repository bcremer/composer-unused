<?php

declare(strict_types=1);

namespace Icanhazstring\Composer\Test\Unused\Integration\Parser\PHP;

use Exception;
use Icanhazstring\Composer\Unused\Error\ErrorHandlerInterface;
use Icanhazstring\Composer\Unused\Parser\PHP\NodeVisitor;
use Icanhazstring\Composer\Unused\Parser\PHP\Strategy\ClassConstStrategy;
use Icanhazstring\Composer\Unused\Parser\PHP\Strategy\NewParseStrategy;
use Icanhazstring\Composer\Unused\Parser\PHP\Strategy\ParseStrategyInterface;
use Icanhazstring\Composer\Unused\Parser\PHP\Strategy\PhpExtensionStrategy;
use Icanhazstring\Composer\Unused\Parser\PHP\Strategy\StaticParseStrategy;
use Icanhazstring\Composer\Unused\Parser\PHP\Strategy\UseParseStrategy;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use SplFileInfo;

class NodeVisitorTest extends TestCase
{
    public function itShouldParseUsagesDataProvider(): array
    {
        return [
            'StaticParseStrategyShouldReturnEmptyUsageOnVariableCall'  => [
                'expectedUsedNamespaces' => [],
                'inputFile'              => ASSET_DIR . '/TestFiles/StaticVariableCall.php',
                'strategy'               => new StaticParseStrategy()
            ],
            'StaticParseStrategyShouldReturnEmptyUsageOnNonFQCall'     => [
                'expectedUsedNamespaces' => [],
                'inputFile'              => ASSET_DIR . '/TestFiles/StaticNonFullyQualifiedCall.php',
                'strategy'               => new StaticParseStrategy()
            ],
            'StaticParseStrategyShouldReturnCorrectNamespaceOnFQCall'      => [
                'expectedUsedNamespaces' => [
                    'StaticFullyQualifiedCall'
                ],
                'inputFile'              => ASSET_DIR . '/TestFiles/StaticFullyQualifiedCall.php',
                'strategy'               => new StaticParseStrategy()
            ],
            'NewParseStrategyShouldReturnEmptyUsageOnDynamicClassnameCall' => [
                'expectedUsedNamespaces' => [],
                'inputFile'              => ASSET_DIR . '/TestFiles/NewInstantiateDynamicClass.php',
                'strategy'               => new NewParseStrategy()
            ],
            'NewParseStrategyShouldReturnEmptyUsageOnNonFQCall'        => [
                'expectedUsedNamespaces' => [],
                'inputFile'              => ASSET_DIR . '/TestFiles/NewInstantiateNonFullyQualifiedCall.php',
                'strategy'               => new NewParseStrategy()
            ],
            'NewParseStrategyShouldReturnCorrectNamespaceOnFQCall'     => [
                'expectedUsedNamespaces' => [
                    'NewInstantiateFullyQualifiedCall'
                ],
                'inputFile'              => ASSET_DIR . '/TestFiles/NewInstantiateFullyQualifiedCall.php',
                'strategy'               => new NewParseStrategy()
            ],
            'UseParseStrategyShouldReturnSingleLineImportedNamespaces' => [
                'expectedUsedNamespaces' => [
                    'Icanhazstring\Composer',
                    'Icanhazstring\Composer\Unused\Parser',
                    'Icanhazstring\Composer\Unused\Command'
                ],
                'inputFile'              => ASSET_DIR . '/TestFiles/UseSingleLineNoGroup.php',
                'strategy'               => new UseParseStrategy()
            ],
            'UseParseStrategyShouldReturnMultiLineImportedNamespaces'  => [
                'expectedUsedNamespaces' => [
                    UseParseStrategy::class,
                    StaticParseStrategy::class,
                    NewParseStrategy::class
                ],
                'inputFile'              => ASSET_DIR . '/TestFiles/UseMultiLineGroup.php',
                'strategy'               => new UseParseStrategy()
            ],
            'ClassConstStrategyShouldReturnCorrectNamespace'           => [
                'expectedUsedNamespaces' => [
                    UseParseStrategy::class,
                    StaticParseStrategy::class
                ],
                'inputFile'              => ASSET_DIR . '/TestFiles/ClassConst.php',
                'strategy'               => new ClassConstStrategy()
            ],
            'NewParseStrategyShouldReturnQualifiedNamespace'           => [
                'expectedUsedNamespaces' => [
                    'TestFile\NewInstantiateQualifiedClass'
                ],
                'inputFile'              => ASSET_DIR . '/TestFiles/NewInstantiateQualifiedClass.php',
                'strategy'               => new NewParseStrategy()
            ],
            'StaticParseStrategyShouldReturnQualifiedNamespace'        => [
                'expectedUsedNamespaces' => [
                    'TestFile\StaticQualifiedCall'
                ],
                'inputFile'              => ASSET_DIR . '/TestFiles/StaticQualifiedCall.php',
                'strategy'               => new StaticParseStrategy()
            ],
            'PhpExtensionParseStrategyShouldReturnQualifiedNamespace---1'        => [
                'expectedUsedNamespaces' => [],
                'inputFile'              => ASSET_DIR . '/TestFiles/PhpExtensionStrategy/ClassWithCustomInterfaceName.php',
                'strategy'               => new PhpExtensionStrategy(['json'])
            ],
            'PhpExtensionParseStrategyShouldReturnQualifiedNamespace---2'        => [
                'expectedUsedNamespaces' => [],
                'inputFile'              => ASSET_DIR . '/TestFiles/PhpExtensionStrategy/ClassWithCustomInterface.php',
                'strategy'               => new PhpExtensionStrategy(['json'])
            ],
            'PhpExtensionParseStrategyShouldReturnQualifiedNamespace---3'        => [
                'expectedUsedNamespaces' => ['ext-json'],
                'inputFile'              => ASSET_DIR . '/TestFiles/PhpExtensionStrategy/ClassWithExtensionInterface.php',
                'strategy'               => new PhpExtensionStrategy(['json'])
            ],
            'PhpExtensionParseStrategyShouldReturnQualifiedNamespace---4'        => [
                'expectedUsedNamespaces' => ['ext-json'],
                'inputFile'              => ASSET_DIR . '/TestFiles/PhpExtensionStrategy/ClassWithExtensionInterfaceInUse.php',
                'strategy'               => new PhpExtensionStrategy(['json'])
            ],
            'PhpExtensionParseStrategyShouldReturnQualifiedNamespace---5'        => [
                'expectedUsedNamespaces' => [],
                'inputFile'              => ASSET_DIR . '/TestFiles/PhpExtensionStrategy/ClassWithCustomConstant.php',
                'strategy'               => new PhpExtensionStrategy(['json'])
            ],
            'PhpExtensionParseStrategyShouldReturnQualifiedNamespace---6'        => [
                'expectedUsedNamespaces' => ['ext-json'],
                'inputFile'              => ASSET_DIR . '/TestFiles/PhpExtensionStrategy/ClassWithJsonConstant.php',
                'strategy'               => new PhpExtensionStrategy(['json'])
            ],
            'PhpExtensionParseStrategyShouldReturnQualifiedNamespace---7'        => [
                'expectedUsedNamespaces' => ['ext-json'],
                'inputFile'              => ASSET_DIR . '/TestFiles/PhpExtensionStrategy/ClassWithExtensionFunction.php',
                'strategy'               => new PhpExtensionStrategy(['json'])
            ],
            'PhpExtensionParseStrategyShouldReturnQualifiedNamespace---8'        => [
                'expectedUsedNamespaces' => [],
                'inputFile'              => ASSET_DIR . '/TestFiles/PhpExtensionStrategy/ClassWithCustomFunction.php',
                'strategy'               => new PhpExtensionStrategy(['json'])
            ],
            'PhpExtensionParseStrategyShouldReturnQualifiedNamespace---9'        => [
                'expectedUsedNamespaces' => ['ext-zend-opcache'],
                'inputFile'              => ASSET_DIR . '/TestFiles/PhpExtensionStrategy/ClassWithZendOpcache.php',
                'strategy'               => new PhpExtensionStrategy(['Zend Opcache'])
            ],
        ];
    }

    /**
     * @test
     * @param array  $expectedUsedNamespaces
     * @param string $inputFile
     * @param ParseStrategyInterface $strategy
     * @dataProvider itShouldParseUsagesDataProvider
     */
    public function itShouldParseUsages(array $expectedUsedNamespaces, string $inputFile, ParseStrategyInterface $strategy): void
    {
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        /** @var string $contents */
        $contents = file_get_contents($inputFile);
        /** @var Node[] $nodes */
        $nodes = $parser->parse($contents);

        $nodeVisitor = new NodeVisitor([$strategy], $this->prophesize(ErrorHandlerInterface::class)->reveal());
        $fileInfo = new SplFileInfo($inputFile);
        $nodeVisitor->setCurrentFile($fileInfo);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($nodeVisitor);

        $traverser->traverse($nodes);
        $this->assertEquals($expectedUsedNamespaces, array_keys($nodeVisitor->getUsages()));
    }

    /**
     * @test
     */
    public function itShouldRaiseExceptionHandledByErrorHandler(): void
    {
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        /** @var string $contents */
        $inputFile = ASSET_DIR . '/TestFiles/UseSingleLineNoGroup.php';
        /** @var string $contents */
        $contents = file_get_contents($inputFile);
        /** @var Node[] $nodes */
        $nodes = $parser->parse($contents);

        $exception = new Exception('');

        $errorHandler = $this->prophesize(ErrorHandlerInterface::class);
        $errorHandler->handle($exception)->shouldBeCalled();

        $exceptionParseStrategy = $this->prophesize(UseParseStrategy::class);

        /** @var Node $node */
        $node = Argument::any();
        $exceptionParseStrategy->meetsCriteria($node)->willReturn(true);
        $exceptionParseStrategy->extractNamespaces($node)->willThrow($exception);

        $nodeVisitor = new NodeVisitor([$exceptionParseStrategy->reveal()], $errorHandler->reveal());
        $fileInfo = new SplFileInfo($inputFile);
        $nodeVisitor->setCurrentFile($fileInfo);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($nodeVisitor);

        $traverser->traverse($nodes);
    }
}
