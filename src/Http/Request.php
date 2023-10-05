<?php

namespace Nebula\Http;

use Nebula\Interfaces\Http\Request as NebulaRequest;
use Nebula\Traits\Instance\Singleton;
use Nebula\Traits\Property\PrivateData;
use StellarRouter\Route;

class Request implements NebulaRequest
{
    use Singleton;
    use PrivateData;

    public ?Route $route;

    public function __construct()
    {
        $this->load($this->request() + $this->input() + $this->files());
    }

    public function getMethod(): string
    {
        return $this->server("REQUEST_METHOD");
    }

    public function getUri(): string
    {
        return $this->server("REQUEST_URI");
    }

    public function input(): mixed
    {
        $input = file_get_contents('php://input');
        $data = [];
        parse_str($input, $data);
        return $data;
    }

    public function server(?string $name = null): mixed
    {
        return $name ? $_SERVER[$name] : $_SERVER;
    }

    public function request(?string $name = null): mixed
    {
        return $name
            ? $_REQUEST[$name]
            : array_filter(
                $_REQUEST,
                fn($key) => $key != "PHPSESSID",
                ARRAY_FILTER_USE_KEY
            );
    }

    public function post(?string $name = null): mixed
    {
        return $name ? $_POST[$name] : $_POST;
    }

    public function query(?string $name = null): mixed
    {
        return $name ? $_GET[$name] : $_GET;
    }

    public function files(?string $name = null): mixed
    {
        return $name ? $_FILES[$name] : $_FILES;
    }
}
