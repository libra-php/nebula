<?php

namespace App\Console;

use Nebula\Framework\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    private Adapter $adapter;

    public function response(): void
    {
        $this->adapter = new Adapter();
        $this->adapter->run();
    }
}
