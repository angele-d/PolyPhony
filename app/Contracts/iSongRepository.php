<?php

namespace App\Contracts;

interface iSongRepository
{
    public function create(string $url): self;
    public function save(): bool;
    public function read(): array;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}