<?php
declare(strict_types=1);

namespace Icanhazstring\Composer\Unused\Parser;

use Icanhazstring\Composer\Unused\Loader\ResultInterface;

interface ParserInterface
{
    public function scan(string $baseDir): ResultInterface;
}