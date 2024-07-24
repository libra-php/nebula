<?php

namespace Nebula\Framework\Console;

use Nebula\Framework\System\Interface\Kernel as NebulaInterface;
use Nebula\Framework\Traits\Singleton;
use Nebula\Framework\System\Kernel as SystemKernel;

class Kernel extends SystemKernel implements NebulaInterface
{
    use Singleton;
}
