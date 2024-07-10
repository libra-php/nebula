<?php

namespace App\Console;

use Nebula\Framework\Console\Adapter as NebulaAdapter;
use splitbrain\phpcli\Options;

class Adapter extends NebulaAdapter
{
    /**
    * Setup options / commands here
    */
    protected function setup(Options $options): void
    {
        parent::setup($options);
        $options->registerOption("version", "Print version", "v");
    }

    /**
    * Add options here
    */
    protected function option(string $option)
    {
        match ($option) {
            "version" => $this->version(),
        };
    }

    /**
    * Add commands here
    */
    protected function command(string $command)
    {
    }

    /**
    * Display's current application version
    */
    private function version(): void
    {
        $config = config("application");
        $this->info("Nebula Framework");
        $this->info("----------------");
        $this->info("Version: {$config['version']}");
        $this->info("https://github.com/libra-php/nebula");
        exit();
    }

}
