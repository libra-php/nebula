<?php

namespace Nebula\Framework\Template;

use Exception;
use Nebula\Framework\Traits\Singleton;

class Engine
{
    use Singleton;

    private array $methods = [];

    public function __construct()
    {
    }

    public function addMethod(string $key, callable $fn)
    {
        $this->methods[$key] = $fn;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param string $path template path
     * @param array<string,mixed> $data
     */
    public function render(string $path, array $data = []): string
    {
        if (!file_exists($path)) {
            throw new Exception("Template path not found: $path");
        }

        foreach ($this->methods as $key => $method) {
            $data[$key] = $method;
        }

        extract($data);

        ob_start();
        require $path;
        return ob_get_clean();
    }
}
