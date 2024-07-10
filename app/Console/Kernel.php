<?php

namespace App\Console;

use Nebula\Framework\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    private Adapter $adapter;

    /**
    * By default, the response will be generated via Adapter
    */
    public function response(): void
    {
        $this->adapter = new Adapter();
        $this->adapter->run();
    }
}
