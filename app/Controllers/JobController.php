<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Repositories\CatalogRepository;
use App\Repositories\JobRepository;
use App\Services\MarketplaceService;
use App\Validator;
use App\View;

final class JobController
{
    private JobRepository $jobs;
    private CatalogRepository $catalog;
    private MarketplaceService $marketplace;

    public function __construct()
    {
        $this->jobs        = new JobRepository();
        $this->catalog     = new CatalogRepository();
        $this->marketplace = new MarketplaceService();
    }

    public function index(array $params = []): void
    {
        Auth::requireLogin();

        $filters = [
            'q'        => trim((string) ($_GET['q'] ?? '')),
            'category' => trim((string) ($_GET['category'] ?? '')),
            'area'     => trim((string) ($_GET['area'] ?? '')),
            'sort'     => trim((string) ($_GET['sort'] ?? 'newest')),
        ];

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 9;
        $total   = $this->jobs->countOpen($filters);
        $pages   = max(1, (int) ceil($total / $perPage));
        $page    = min($page, $pages);

        View::render('jobs/index', [
            'title'      => 'Find work',
            'jobs'       => $this->jobs->openJobs($filters, $perPage, ($page - 1) * $perPage),
            'filters'    => $filters,
            'categories' => $this->catalog->categories(),
            'areas'      => $this->catalog->areas(),
            'total'      => $total,
            'page'       => $page,
            'pages'      => $pages,
        ]);
    }

    public function show(array $params = []): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        $job = $this->jobs->findByPublicId((string) ($params['publicId'] ?? ''));
        if ($job === null) {
            abort(404, 'That opportunity could not be found.');
        }

        $isOwner = (int) $job['employer_id'] === (int) $user['id'];
        if (!$isOwner && !in_array($job['status'], ['OPEN', 'FILLED'], true)) {
            abort(404, 'That opportunity is no longer available.');
        }

        $myApplication = null;
        if ($user['role'] === 'worker') {
            $myApplication = Database::one(
                'SELECT public_id, status FROM application WHERE job_id = ? AND worker_id = ?',
                [$job['id'], $user['id']]
            );
        }

        View::render('jobs/show', [
            'title'         => $job['title'],
            'job'           => $job,
            'isOwner'       => $isOwner,
            'myApplication' => $myApplication,
            'user'          => $user,
        ]);
    }

    public function mine(array $params = []): void
    {
        Auth::requireRole('employer');
        $user = Auth::user();

        View::render('employer/jobs', [
            'title' => 'My Opportunities',
            'jobs'  => $this->jobs->forEmployer((int) $user['id']),
            'stats' => $this->jobs->employerStats((int) $user['id']),
        ]);
    }

    public function create(array $params = []): void
    {
        Auth::requireRole('employer');

        View::render('employer/post', [
            'title'      => 'Create Opportunity',
            'categories' => $this->catalog->categories(),
            'areas'      => $this->catalog->areas(),
            'errors'     => [],
            'old'        => [
                'pay_type'   => 'DAY',
                'headcount'  => 1,
                'starts_on'  => date('Y-m-d'),
                'ends_on'    => date('Y-m-d'),
                'start_time' => '09:00',
                'end_time'   => '18:00',
            ],
        ]);
    }

    public function store(array $params = []): void
    {
        Auth::requireRole('employer');
        $user = Auth::user();

        $rules = [
            'title'       => 'required|min:5|max:180',
            'description' => 'required|min:20|max:5000',
            'category_id' => 'required|int',
            'area_id'     => 'required|int',
            'address'     => 'required|max:255',
            'pay_amount'  => 'required|numeric|gt:0',
            'pay_type'    => 'required|in:DAY,HOUR,TOTAL',
            'headcount'   => 'required|int|gte:1',
            'starts_on'   => 'required|date',
            'ends_on'     => 'required|date',
            'start_time'  => 'required|time',
            'end_time'    => 'required|time',
        ];

        $errors = Validator::validate($_POST, $rules);

        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $areaId     = (int) ($_POST['area_id'] ?? 0);

        if ($categoryId > 0 && !$this->catalog->categoryExists($categoryId)) {
            $errors['category_id'] = 'Please choose a valid category.';
        }
        if ($areaId > 0 && !$this->catalog->areaExists($areaId)) {
            $errors['area_id'] = 'Please choose a valid area.';
        }

        $payRupees = (float) ($_POST['pay_amount'] ?? 0);
        if ($payRupees > 1000000) {
            $errors['pay_amount'] = 'Pay must be ₹10,00,000 or less per unit.';
        }

        $headcount = (int) ($_POST['headcount'] ?? 0);
        if ($headcount > 200) {
            $errors['headcount'] = 'Headcount must be 200 or fewer.';
        }

        $startsOn = (string) ($_POST['starts_on'] ?? '');
        $endsOn   = (string) ($_POST['ends_on'] ?? '');
        $startAt  = (string) ($_POST['start_time'] ?? '');
        $endAt    = (string) ($_POST['end_time'] ?? '');

        if ($startsOn !== '' && $endsOn !== '' && $endsOn < $startsOn) {
            $errors['ends_on'] = 'The end date cannot be before the start date.';
        }
        if ($startsOn !== '' && $startsOn < date('Y-m-d')) {
            $errors['starts_on'] = 'The start date cannot be in the past.';
        }
        if ($startAt !== '' && $endAt !== '' && $endAt <= $startAt) {
            $errors['end_time'] = 'The end time must be after the start time.';
        }

        if ($errors !== []) {
            View::render('employer/post', [
                'title'      => 'Create Opportunity',
                'categories' => $this->catalog->categories(),
                'areas'      => $this->catalog->areas(),
                'errors'     => $errors,
                'old'        => $_POST,
            ]);

            return;
        }

        $publish = isset($_POST['publish_now']);

        $this->jobs->create((int) $user['id'], [
            'category_id'      => $categoryId,
            'area_id'          => $areaId,
            'title'            => trim((string) $_POST['title']),
            'description'      => trim((string) $_POST['description']),
            'address'          => trim((string) $_POST['address']),
            'pay_amount_paise' => (int) round($payRupees * 100),
            'pay_type'         => (string) $_POST['pay_type'],
            'headcount'        => $headcount,
            'starts_on'        => $startsOn,
            'ends_on'          => $endsOn,
            'start_time'       => $startAt . ':00',
            'end_time'         => $endAt . ':00',
            'status'           => $publish ? 'OPEN' : 'DRAFT',
        ]);

        flash('success', $publish ? 'Opportunity published. Workers can start sending Flight Requests.' : 'Draft saved. Publish it when you are ready.');

        redirect('/employer/jobs');
    }

    public function publish(array $params = []): void
    {
        Auth::requireRole('employer');
        $result = $this->marketplace->publish((string) ($params['publicId'] ?? ''), (int) Auth::id());
        flash($result['ok'] ? 'success' : 'error', $result['message']);

        redirect('/employer/jobs');
    }

    public function cancel(array $params = []): void
    {
        Auth::requireRole('employer');
        $result = $this->marketplace->cancel((string) ($params['publicId'] ?? ''), (int) Auth::id());
        flash($result['ok'] ? 'success' : 'error', $result['message']);

        redirect('/employer/jobs');
    }
}
