<?php

namespace App;

use Nebula\Framework\Traits\Singleton;
use Nebula\Framework\System\Interface\Kernel;

class Application
{
    use Singleton;

    public function __construct(private Kernel $kernel)
    {
        $paths = config("path");
        $this->kernel->setup($paths)->main();
    }

    public function kernel()
    {
        return $this->kernel;
    }

    public function run(): void
    {
        $this->kernel->response();
    }
}
