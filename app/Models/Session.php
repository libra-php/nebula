<?php

namespace App\Models;

use Nebula\Model\Model;

final class Session extends Model
{
    public string $table = "sessions";
    public string $primary_key = "id";
}

