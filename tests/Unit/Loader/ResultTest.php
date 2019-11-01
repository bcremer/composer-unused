<?php
declare(strict_types=1);

namespace Icanhazstring\Composer\Test\Unused\Unit\Loader;

use Icanhazstring\Composer\Unused\Loader\Result;
use Icanhazstring\Composer\Unused\Subject\UsageInterface;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldReturnNewInstanceOnMerge(): void
    {
        $original = new Result();
        $other = new Result();

        $this->assertNotSame($original, $original->merge($other), 'Result must be immutable after merge');
        $this->assertNotSame($other, $original->merge($other), 'It should not return $other after merge');
    }

    /**
     * @test
     */
    public function itShouldMergeOtherItems(): void
    {
        $item = $this->prophesize(UsageInterface::class)->reveal();

        $original = new Result();
        $other = new Result();
        $other->addItem($item);

        $merged = $original->merge($other);

        $this->assertCount(1, $merged->getItems());
        $this->assertSame($merged->getItems()[0], $item);
    }
}