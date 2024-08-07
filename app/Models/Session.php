<?php

namespace App\Models;

use Nebula\Framework\Model\Model;

class Session extends Model
{
    public function __construct(?string $key = null)
    {
        parent::__construct("sessions", $key);
    }
}
