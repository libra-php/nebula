<?php

namespace App\Models;

use Nebula\Model\Model;

final class User extends Model
{
    public string $table = "users";
    public string $primary_key = "id";

    // Columns won't be inserted/updated
    protected array $guarded = ["id", "uuid", "created_at", "updated_at"];

    public function type(): mixed
    {
        return db()->select(
            "SELECT * FROM user_types WHERE id = ?",
            $this->user_type
        );
    }
}
