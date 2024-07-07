<?php

namespace App\Console;

use Nebula\Framework\Console\Adapter as NebulaAdapter;

class Adapter extends NebulaAdapter
{
    private function version(): void
    {
        $this->info(config("application.version"));
        exit();
    }

    protected function option(string $option)
    {
        match ($option) {
            "version" => $this->version(),
        };
    }

    protected function command(string $command)
    {
    }
}
