<?php

namespace App\Models;

use Nebula\Model\Model;

final class Post extends Model
{
    public string $table = "posts";
    public string $primary_key = "id";

    protected array $guarded = ["id", "created_at", "updated_at"];

    public function __construct(protected ?string $id = null)
    {
    }

    public function author(): ?User
    {
        return User::find($this->user_id);
    }
}
