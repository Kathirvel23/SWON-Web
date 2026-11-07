<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Repositories\ApplicationRepository;
use App\Repositories\JobRepository;
use App\Services\MarketplaceService;
use App\View;

final class ApplicationController
{
    private ApplicationRepository $applications;
    private JobRepository $jobs;
    private MarketplaceService $marketplace;

    public function __construct()
    {
        $this->applications = new ApplicationRepository();
        $this->jobs         = new JobRepository();
        $this->marketplace  = new MarketplaceService();
    }

    public function mine(array $params = []): void
    {
        Auth::requireRole('worker');
        $user = Auth::user();

        View::render('worker/applications', [
            'title'        => 'My Flight Requests',
            'applications' => $this->applications->forWorker((int) $user['id']),
        ]);
    }

    public function store(array $params = []): void
    {
        Auth::requireRole('worker');
        $user = Auth::user();

        $jobPublicId = (string) ($_POST['job_public_id'] ?? '');
        $note        = trim((string) ($_POST['note'] ?? ''));

        $result = $this->marketplace->apply($jobPublicId, (int) $user['id'], $note);
        flash($result['ok'] ? 'success' : 'error', $result['message']);

        redirect('/jobs/' . rawurlencode($jobPublicId));
    }

    public function withdraw(array $params = []): void
    {
        Auth::requireRole('worker');
        $user = Auth::user();

        $application = $this->applications->findByPublicId((string) ($params['publicId'] ?? ''));
        if ($application === null) {
            flash('error', 'Flight Request not found.');
            redirect('/applications');
        }

        if ((int) $application['worker_id'] !== (int) $user['id']) {
            flash('error', 'You can only withdraw your own Flight Request.');
            redirect('/applications');
        }

        if (!in_array($application['status'], ['APPLIED', 'SHORTLISTED', 'WAITLISTED'], true)) {
            flash('error', 'This Flight Request can no longer be withdrawn.');
            redirect('/applications');
        }

        $this->applications->setStatus((int) $application['id'], 'WITHDRAWN');
        flash('success', 'Flight Request withdrawn.');

        redirect('/applications');
    }

    public function queue(array $params = []): void
    {
        Auth::requireRole('employer');
        $user = Auth::user();

        $job = $this->jobs->findByPublicId((string) ($params['publicId'] ?? ''));
        if ($job === null || (int) $job['employer_id'] !== (int) $user['id']) {
            abort(404, 'That opportunity could not be found.');
        }

        View::render('employer/queue', [
            'title'      => 'Flight Requests — ' . $job['title'],
            'job'        => $job,
            'applicants' => $this->applications->forJob((int) $job['id']),
        ]);
    }

    public function decide(array $params = []): void
    {
        Auth::requireRole('employer');
        $user = Auth::user();

        $publicId = (string) ($params['publicId'] ?? '');
        $action   = (string) ($_POST['action'] ?? '');

        $application = $this->applications->findByPublicId($publicId);
        if ($application === null || (int) $application['employer_id'] !== (int) $user['id']) {
            flash('error', 'Flight Request not found.');
            redirect('/employer/jobs');
        }

        $result = $this->marketplace->decide($publicId, (int) $user['id'], $action);
        flash($result['ok'] ? 'success' : 'error', $result['message']);

        redirect('/employer/jobs/' . rawurlencode((string) $application['job_public_id']) . '/queue');
    }
}
