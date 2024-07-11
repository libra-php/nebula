<?php

namespace App\Models;

use Nebula\Framework\Model\Model;

class User extends Model
{
    public function __construct(?string $key = null)
    {
        parent::__construct("users", $key);
    }

    public function type(): UserType
    {
        return UserType::find($this->user_type_id);
    }
}
