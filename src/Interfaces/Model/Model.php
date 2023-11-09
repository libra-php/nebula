<?php

namespace Nebula\Interfaces\Model;

interface Model
{
    public static function find(mixed $id): ?self;
    public static function search(...$args): mixed;
    public function save(): mixed;
    public function insert(array $data, bool $ignore): mixed;
    public function update(array $data): bool;
    public function delete(): bool;
    public function refresh(): void;
}
