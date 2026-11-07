<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database;

final class JobRepository
{
    /** Shared SELECT so list/detail stay in sync. */
    private const BASE_SELECT = "
        SELECT j.id, j.public_id, j.employer_id, j.title, j.description, j.address,
               j.pay_amount_paise, j.pay_type, j.headcount, j.starts_on, j.ends_on,
               j.start_time, j.end_time, j.status, j.created_at,
               c.name AS category_name, c.slug AS category_slug,
               a.name AS area_name, a.city,
               ep.org_name,
               (SELECT COUNT(*) FROM application ap WHERE ap.job_id = j.id AND ap.status = 'HIRED') AS hired_count,
               (SELECT COUNT(*) FROM application ap
                 WHERE ap.job_id = j.id AND ap.status IN ('APPLIED','SHORTLISTED','WAITLISTED')) AS pending_count
          FROM job j
          JOIN job_category c ON c.id = j.category_id
          JOIN area a ON a.id = j.area_id
          LEFT JOIN employer_profile ep ON ep.account_id = j.employer_id
    ";

    /**
     * @param array{q?: string, category?: string, area?: string, sort?: string} $filters
     * @return list<array<string, mixed>>
     */
    public function openJobs(array $filters, int $limit = 12, int $offset = 0): array
    {
        [$where, $params] = $this->filterSql($filters);
        $order = ($filters['sort'] ?? '') === 'pay'
            ? 'j.pay_amount_paise DESC, j.created_at DESC'
            : 'j.created_at DESC';

        $sql = self::BASE_SELECT
            . ' WHERE j.status = \'OPEN\' ' . $where
            . ' ORDER BY ' . $order
            . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        return Database::all($sql, $params);
    }

    /** @param array{q?: string, category?: string, area?: string} $filters */
    public function countOpen(array $filters): int
    {
        [$where, $params] = $this->filterSql($filters);

        return (int) Database::scalar(
            "SELECT COUNT(*) FROM job j
               JOIN job_category c ON c.id = j.category_id
               JOIN area a ON a.id = j.area_id
              WHERE j.status = 'OPEN' " . $where,
            $params
        );
    }

    /** @return array<string, mixed>|null */
    public function findByPublicId(string $publicId): ?array
    {
        return Database::one(self::BASE_SELECT . ' WHERE j.public_id = ?', [$publicId]);
    }

    /** @return list<array<string, mixed>> */
    public function forEmployer(int $employerId): array
    {
        return Database::all(
            self::BASE_SELECT . ' WHERE j.employer_id = ? ORDER BY j.created_at DESC',
            [$employerId]
        );
    }

    /** @return array<string, mixed> */
    public function employerStats(int $employerId): array
    {
        $row = Database::one(
            "SELECT
                COUNT(*) AS total_jobs,
                SUM(CASE WHEN status = 'OPEN' THEN 1 ELSE 0 END) AS open_jobs,
                (SELECT COUNT(*) FROM application ap JOIN job j2 ON j2.id = ap.job_id
                  WHERE j2.employer_id = ? AND ap.status IN ('APPLIED','SHORTLISTED','WAITLISTED')) AS pending_applicants,
                (SELECT COUNT(*) FROM application ap JOIN job j2 ON j2.id = ap.job_id
                  WHERE j2.employer_id = ? AND ap.status = 'HIRED') AS hired_workers
               FROM job WHERE employer_id = ?",
            [$employerId, $employerId, $employerId]
        );

        return $row ?? ['total_jobs' => 0, 'open_jobs' => 0, 'pending_applicants' => 0, 'hired_workers' => 0];
    }

    /** @return list<array<string, mixed>> */
    public function latestOpen(int $limit = 3, int $excludeEmployerId = 0): array
    {
        $sql = self::BASE_SELECT . " WHERE j.status = 'OPEN'";
        $params = [];
        if ($excludeEmployerId > 0) {
            $sql .= ' AND j.employer_id <> ?';
            $params[] = $excludeEmployerId;
        }
        $sql .= ' ORDER BY j.created_at DESC LIMIT ' . max(1, $limit);

        return Database::all($sql, $params);
    }

    public function create(int $employerId, array $data): int
    {
        return Database::insert('job', [
            'public_id'        => gen_public_id('job'),
            'employer_id'      => $employerId,
            'category_id'      => $data['category_id'],
            'area_id'          => $data['area_id'],
            'title'            => $data['title'],
            'description'      => $data['description'],
            'address'          => $data['address'],
            'pay_amount_paise' => $data['pay_amount_paise'],
            'pay_type'         => $data['pay_type'],
            'headcount'        => $data['headcount'],
            'starts_on'        => $data['starts_on'],
            'ends_on'          => $data['ends_on'],
            'start_time'       => $data['start_time'],
            'end_time'         => $data['end_time'],
            'status'           => $data['status'],
        ]);
    }

    public function setStatus(int $jobId, string $status): void
    {
        Database::run('UPDATE job SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $jobId]);
    }

    public function hiredCount(int $jobId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM application WHERE job_id = ? AND status = 'HIRED'",
            [$jobId]
        );
    }

    /**
     * Keep OPEN/FILLED in sync with the number of hired crew.
     * Cancelled, completed and draft jobs are never touched.
     */
    public function syncFillStatus(int $jobId): void
    {
        $job = Database::one('SELECT id, status, headcount FROM job WHERE id = ?', [$jobId]);
        if ($job === null || !in_array($job['status'], ['OPEN', 'FILLED'], true)) {
            return;
        }

        $hired  = $this->hiredCount($jobId);
        $target = $hired >= (int) $job['headcount'] ? 'FILLED' : 'OPEN';

        if ($target !== $job['status']) {
            $this->setStatus($jobId, $target);
        }
    }

    /**
     * @param array{q?: string, category?: string, area?: string} $filters
     * @return array{0: string, 1: list<mixed>}
     */
    private function filterSql(array $filters): array
    {
        $where  = '';
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (j.title LIKE ? OR j.description LIKE ? OR c.name LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $where .= ' AND c.slug = ?';
            $params[] = $category;
        }

        $area = trim((string) ($filters['area'] ?? ''));
        if ($area !== '') {
            $where .= ' AND a.id = ?';
            $params[] = (int) $area;
        }

        return [$where, $params];
    }
}
