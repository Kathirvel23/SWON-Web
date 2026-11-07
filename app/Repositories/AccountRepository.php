<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database;

final class AccountRepository
{
    /** @return array<string, mixed>|null */
    public function findByPhone(string $phone): ?array
    {
        return Database::one('SELECT * FROM account WHERE phone = ?', [$phone]);
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        return Database::one('SELECT * FROM account WHERE id = ?', [$id]);
    }

    public function create(string $phone): int
    {
        return Database::insert('account', [
            'public_id' => gen_public_id('acc'),
            'phone'     => $phone,
            'role'      => null,
            'status'    => 'PENDING',
        ]);
    }

    public function setRole(int $accountId, string $role): void
    {
        Database::run(
            "UPDATE account SET role = ?, status = 'ACTIVE', updated_at = NOW() WHERE id = ?",
            [$role, $accountId]
        );
    }

    public function markOnboarded(int $accountId): void
    {
        Database::run('UPDATE account SET onboarded_at = NOW(), status = ? WHERE id = ?', ['ACTIVE', $accountId]);
    }

    public function touchLogin(int $accountId): void
    {
        Database::run('UPDATE account SET last_login_at = NOW() WHERE id = ?', [$accountId]);
    }

    /** @return array<string, mixed>|null */
    public function workerProfile(int $accountId): ?array
    {
        return Database::one('SELECT * FROM worker_profile WHERE account_id = ?', [$accountId]);
    }

    /** @return array<string, mixed>|null */
    public function employerProfile(int $accountId): ?array
    {
        return Database::one('SELECT * FROM employer_profile WHERE account_id = ?', [$accountId]);
    }

    public function createWorkerProfile(int $accountId, array $data): int
    {
        return Database::insert('worker_profile', [
            'account_id'   => $accountId,
            'full_name'    => $data['full_name'],
            'area_id'      => $data['area_id'],
            'skills'       => $data['skills'],
            'availability' => $data['availability'],
            'about'        => $data['about'],
        ]);
    }

    public function createEmployerProfile(int $accountId, array $data): int
    {
        return Database::insert('employer_profile', [
            'account_id'   => $accountId,
            'org_name'     => $data['org_name'],
            'contact_name' => $data['contact_name'],
            'area_id'      => $data['area_id'],
            'about'        => $data['about'],
        ]);
    }

    public function updateWorkerProfile(int $accountId, array $data): void
    {
        Database::run(
            'UPDATE worker_profile
                SET full_name = ?, area_id = ?, skills = ?, availability = ?, about = ?
              WHERE account_id = ?',
            [$data['full_name'], $data['area_id'], $data['skills'], $data['availability'], $data['about'], $accountId]
        );
    }

    public function updateEmployerProfile(int $accountId, array $data): void
    {
        Database::run(
            'UPDATE employer_profile
                SET org_name = ?, contact_name = ?, area_id = ?, about = ?
              WHERE account_id = ?',
            [$data['org_name'], $data['contact_name'], $data['area_id'], $data['about'], $accountId]
        );
    }
}
