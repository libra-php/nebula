<?php

namespace App\Models;

use Nebula\Model\Model;

final class Module extends Model
{
    public string $table = "modules";
    public string $primary_key = "id";

    protected array $guarded = ["id", "created_at", "updated_at"];

    public function __construct(protected ?string $id = null)
    {
    }
}
