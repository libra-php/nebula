<?php

namespace Nebula\Framework\System;

use Dotenv\Dotenv;
use Error;

class Kernel
{
    protected array $paths;

    /**
     * Main kernel method
     */
    public function main(): void
    {
        $this->bootstrap();
    }

    /**
     * Response from kernel
     */
    public function response(): void
    {
    }

    /**
     * Initializes the framework, sets up essential configurations,
     * and prepares the environment for the application to run.
     */
    protected function bootstrap(): void
    {
        $this->initEnvironment();
    }

    public function setup(array $paths): self
    {
        $this->paths = $paths;
        return $this;
    }

    protected function initEnvironment(): void
    {
        $env_path = $this->paths["env"];
        if (!file_exists($env_path)) {
            throw new Error("warning: your .env path: '$env_path' doesn't exist");
        }
        $dotenv = Dotenv::createImmutable($env_path);
        $dotenv->safeLoad();
    }

}
