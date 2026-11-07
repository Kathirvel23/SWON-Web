<?php
declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Repositories\JobRepository;

/**
 * Marketplace rules that must hold under concurrency:
 *  - a worker applies once per job and only while seats remain
 *  - an employer can only act on their own job
 *  - hiring is seat-safe (row lock + recount)
 *
 * Business failures return [ok => false, message => ...] instead of throwing.
 */
final class MarketplaceService
{
    private JobRepository $jobs;

    public function __construct()
    {
        $this->jobs = new JobRepository();
    }

    /** @return array{ok: bool, message: string} */
    public function apply(string $jobPublicId, int $workerId, string $note): array
    {
        return Database::transaction(function () use ($jobPublicId, $workerId, $note): array {
            $job = Database::one(
                'SELECT * FROM job WHERE public_id = ? FOR UPDATE',
                [$jobPublicId]
            );

            if ($job === null) {
                return ['ok' => false, 'message' => 'That opportunity could not be found.'];
            }
            if ($job['status'] === 'CANCELLED') {
                return ['ok' => false, 'message' => 'This opportunity has been cancelled.'];
            }
            if ($job['status'] === 'FILLED') {
                return ['ok' => false, 'message' => 'This opportunity is already fully staffed.'];
            }
            if ($job['status'] !== 'OPEN') {
                return ['ok' => false, 'message' => 'This opportunity is not accepting Flight Requests.'];
            }

            if (Database::scalar(
                'SELECT 1 FROM application WHERE job_id = ? AND worker_id = ?',
                [$job['id'], $workerId]
            )) {
                return ['ok' => false, 'message' => 'You have already sent a Flight Request for this opportunity.'];
            }

            $hired = $this->jobs->hiredCount((int) $job['id']);
            if ($hired >= (int) $job['headcount']) {
                $this->jobs->setStatus((int) $job['id'], 'FILLED');

                return ['ok' => false, 'message' => 'This opportunity just filled up. Try another one.'];
            }

            Database::insert('application', [
                'public_id' => gen_public_id('app'),
                'job_id'    => (int) $job['id'],
                'worker_id' => $workerId,
                'status'    => 'APPLIED',
                'note'      => mb_substr($note, 0, 500),
            ]);

            return ['ok' => true, 'message' => 'Flight Request sent. The host will review it.'];
        });
    }

    /** @return array{ok: bool, message: string} */
    public function decide(string $applicationPublicId, int $employerId, string $action): array
    {
        $map = [
            'shortlist' => ['SHORTLISTED', 'Candidate shortlisted.'],
            'waitlist'  => ['WAITLISTED', 'Candidate moved to the waitlist.'],
            'reject'    => ['REJECTED', 'Flight Request not selected.'],
            'hire'      => ['HIRED', 'Worker hired for this opportunity.'],
            'release'   => ['RELEASED', 'Worker released from this opportunity.'],
        ];

        if (!isset($map[$action])) {
            return ['ok' => false, 'message' => 'Unknown action.'];
        }

        return Database::transaction(function () use ($applicationPublicId, $employerId, $action, $map): array {
            $app = Database::one(
                'SELECT ap.id, ap.status, ap.job_id, j.employer_id, j.status AS job_status, j.headcount
                   FROM application ap
                   JOIN job j ON j.id = ap.job_id
                  WHERE ap.public_id = ?
                  FOR UPDATE',
                [$applicationPublicId]
            );

            if ($app === null) {
                return ['ok' => false, 'message' => 'Application not found.'];
            }
            if ((int) $app['employer_id'] !== $employerId) {
                return ['ok' => false, 'message' => 'You do not have access to this application.'];
            }
            if ($app['job_status'] === 'CANCELLED') {
                return ['ok' => false, 'message' => 'This opportunity was cancelled.'];
            }

            $allowed = [
                'APPLIED'     => ['shortlist', 'waitlist', 'reject', 'hire'],
                'SHORTLISTED' => ['waitlist', 'reject', 'hire'],
                'WAITLISTED'  => ['shortlist', 'reject', 'hire'],
                'HIRED'       => ['release'],
            ];

            if (!in_array($action, $allowed[$app['status']] ?? [], true)) {
                return ['ok' => false, 'message' => 'That action is not allowed from the current status.'];
            }

            if ($action === 'hire') {
                $hired = $this->jobs->hiredCount((int) $app['job_id']);
                if ($hired >= (int) $app['headcount']) {
                    $this->jobs->setStatus((int) $app['job_id'], 'FILLED');

                    return ['ok' => false, 'message' => 'All seats are already filled.'];
                }
            }

            Database::run(
                'UPDATE application SET status = ?, updated_at = NOW() WHERE id = ?',
                [$map[$action][0], $app['id']]
            );
            $this->jobs->syncFillStatus((int) $app['job_id']);

            return ['ok' => true, 'message' => $map[$action][1]];
        });
    }

    /** @return array{ok: bool, message: string} */
    public function publish(string $jobPublicId, int $employerId): array
    {
        $job = $this->jobs->findByPublicId($jobPublicId);
        if ($job === null || (int) $job['employer_id'] !== $employerId) {
            return ['ok' => false, 'message' => 'Opportunity not found.'];
        }
        if ($job['status'] !== 'DRAFT') {
            return ['ok' => false, 'message' => 'Only draft opportunities can be published.'];
        }

        $this->jobs->setStatus((int) $job['id'], 'OPEN');

        return ['ok' => true, 'message' => 'Opportunity published. Workers can now send Flight Requests.'];
    }

    /** @return array{ok: bool, message: string} */
    public function cancel(string $jobPublicId, int $employerId): array
    {
        $job = $this->jobs->findByPublicId($jobPublicId);
        if ($job === null || (int) $job['employer_id'] !== $employerId) {
            return ['ok' => false, 'message' => 'Opportunity not found.'];
        }
        if (!in_array($job['status'], ['DRAFT', 'OPEN', 'FILLED'], true)) {
            return ['ok' => false, 'message' => 'This opportunity can no longer be cancelled.'];
        }

        $this->jobs->setStatus((int) $job['id'], 'CANCELLED');

        return ['ok' => true, 'message' => 'Opportunity cancelled. Workers will see it as closed.'];
    }
}
