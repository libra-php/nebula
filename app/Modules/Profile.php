<?php

namespace App\Modules;

use Nebula\Framework\Admin\Module;

class Profile extends Module
{
    protected function getIndexTemplate(): string
    {
        return "profile/index.php";
    }

    protected function getIndexData(): array
    {
        return [
            "message" => "Hello, world! " . time()
        ];
    }
}
