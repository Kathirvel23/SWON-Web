<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Repositories\ApplicationRepository;
use App\Repositories\JobRepository;
use App\View;

final class DashboardController
{
    private JobRepository $jobs;
    private ApplicationRepository $applications;

    public function __construct()
    {
        $this->jobs         = new JobRepository();
        $this->applications = new ApplicationRepository();
    }

    public function splash(array $params = []): void
    {
        if (Auth::check()) {
            redirect(Auth::homeFor(Auth::role()));
        }

        View::render('pages/splash', ['title' => 'SWON — Find work. Build your crew.'], 'layouts/splash');
    }

    public function index(array $params = []): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        if (empty($user['role'])) {
            redirect('/role');
        }
        if (empty($user['onboarded_at'])) {
            redirect('/onboarding');
        }

        redirect(Auth::homeFor((string) $user['role']));
    }

    public function worker(array $params = []): void
    {
        Auth::requireRole('worker');
        $user = Auth::user();

        $applications = $this->applications->forWorker((int) $user['id']);

        View::render('worker/dashboard', [
            'title'        => 'Your work dashboard',
            'user'         => $user,
            'jobs'         => $this->jobs->latestOpen(3),
            'applications' => array_slice($applications, 0, 4),
            'stats'        => [
                'open_jobs'    => $this->jobs->countOpen([]),
                'applications' => count($applications),
                'hired'        => $this->applications->workerHiredCount((int) $user['id']),
            ],
        ]);
    }

    public function employer(array $params = []): void
    {
        Auth::requireRole('employer');
        $user = Auth::user();

        $jobs = $this->jobs->forEmployer((int) $user['id']);

        View::render('employer/dashboard', [
            'title' => 'Host dashboard',
            'user'  => $user,
            'jobs'  => array_slice($jobs, 0, 4),
            'stats' => $this->jobs->employerStats((int) $user['id']),
        ]);
    }
}
