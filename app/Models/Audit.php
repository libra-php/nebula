<?php

namespace App\Models;

use Nebula\Model\Model;

final class Audit extends Model
{
    public string $table = "audit";
    public string $primary_key = "id";

    protected array $guarded = ["id", "created_at"];

    public function __construct(protected ?string $id = null)
    {
    }
}

