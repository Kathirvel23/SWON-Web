<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database;

final class ApplicationRepository
{
    /** Worker's applications with the job they point at. */
    /** @return list<array<string, mixed>> */
    public function forWorker(int $workerId): array
    {
        return Database::all(
            "SELECT ap.public_id, ap.status, ap.note, ap.created_at,
                    j.public_id AS job_public_id, j.title, j.pay_amount_paise, j.pay_type,
                    j.starts_on, j.ends_on, j.status AS job_status,
                    a.name AS area_name, ep.org_name
               FROM application ap
               JOIN job j ON j.id = ap.job_id
               JOIN area a ON a.id = j.area_id
               LEFT JOIN employer_profile ep ON ep.account_id = j.employer_id
              WHERE ap.worker_id = ?
              ORDER BY ap.created_at DESC",
            [$workerId]
        );
    }

    /** Applicants for one job, with the worker profile needed to decide. */
    /** @return list<array<string, mixed>> */
    public function forJob(int $jobId): array
    {
        return Database::all(
            "SELECT ap.id, ap.public_id, ap.status, ap.note, ap.created_at,
                    acc.public_id AS worker_public_id, acc.phone,
                    wp.full_name, wp.skills, wp.availability, wp.about,
                    ar.name AS area_name
               FROM application ap
               JOIN account acc ON acc.id = ap.worker_id
               LEFT JOIN worker_profile wp ON wp.account_id = acc.id
               LEFT JOIN area ar ON ar.id = wp.area_id
              WHERE ap.job_id = ?
              ORDER BY FIELD(ap.status, 'HIRED','SHORTLISTED','APPLIED','WAITLISTED','REJECTED','WITHDRAWN','RELEASED'),
                       ap.created_at ASC",
            [$jobId]
        );
    }

    /** @return array<string, mixed>|null */
    public function findByPublicId(string $publicId): ?array
    {
        return Database::one(
            "SELECT ap.*, j.public_id AS job_public_id, j.employer_id, j.title, j.status AS job_status,
                    j.headcount
               FROM application ap
               JOIN job j ON j.id = ap.job_id
              WHERE ap.public_id = ?",
            [$publicId]
        );
    }

    public function exists(int $jobId, int $workerId): bool
    {
        return (bool) Database::scalar(
            'SELECT 1 FROM application WHERE job_id = ? AND worker_id = ?',
            [$jobId, $workerId]
        );
    }

    public function create(int $jobId, int $workerId, string $note): int
    {
        return Database::insert('application', [
            'public_id' => gen_public_id('app'),
            'job_id'    => $jobId,
            'worker_id' => $workerId,
            'status'    => 'APPLIED',
            'note'      => $note,
        ]);
    }

    public function setStatus(int $applicationId, string $status): void
    {
        Database::run(
            'UPDATE application SET status = ?, updated_at = NOW() WHERE id = ?',
            [$status, $applicationId]
        );
    }

    public function workerApplicationCount(int $workerId): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM application WHERE worker_id = ?', [$workerId]);
    }

    public function workerHiredCount(int $workerId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM application WHERE worker_id = ? AND status = 'HIRED'",
            [$workerId]
        );
    }
}
