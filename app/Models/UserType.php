<?php

namespace App\Models;

use Nebula\Framework\Model\Model;

class UserType extends Model
{
    public function __construct(?string $key = null)
    {
        parent::__construct("user_types", $key);
    }
}
