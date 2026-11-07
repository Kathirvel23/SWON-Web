<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database;

final class CatalogRepository
{
    /** @return list<array<string, mixed>> */
    public function areas(): array
    {
        return Database::all('SELECT id, name, city FROM area WHERE active = 1 ORDER BY city, name');
    }

    /** @return list<array<string, mixed>> */
    public function categories(): array
    {
        return Database::all('SELECT id, name, slug FROM job_category WHERE active = 1 ORDER BY name');
    }

    public function areaExists(int $id): bool
    {
        return (bool) Database::scalar('SELECT 1 FROM area WHERE id = ? AND active = 1', [$id]);
    }

    public function categoryExists(int $id): bool
    {
        return (bool) Database::scalar('SELECT 1 FROM job_category WHERE id = ? AND active = 1', [$id]);
    }
}
