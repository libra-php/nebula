<?php

namespace App\Models;

use Nebula\Framework\Model\Model;

class Modules extends Model
{
    public function __construct(?string $key = null)
    {
        parent::__construct("modules", $key);
    }
}
