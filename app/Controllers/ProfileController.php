<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Repositories\AccountRepository;
use App\Repositories\CatalogRepository;
use App\Validator;
use App\View;

final class ProfileController
{
    private AccountRepository $accounts;
    private CatalogRepository $catalog;

    public function __construct()
    {
        $this->accounts = new AccountRepository();
        $this->catalog  = new CatalogRepository();
    }

    public function edit(array $params = []): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        if (empty($user['role'])) {
            redirect('/role');
        }
        if (empty($user['onboarded_at'])) {
            redirect('/onboarding');
        }

        $this->renderProfile($user, [], []);
    }

    public function update(array $params = []): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        if (empty($user['role'])) {
            redirect('/role');
        }
        if (empty($user['onboarded_at'])) {
            redirect('/onboarding');
        }

        $isWorker = $user['role'] === 'worker';
        $rules    = $isWorker
            ? [
                'full_name'    => 'required|min:2|max:160',
                'area_id'      => 'required|int',
                'availability' => 'required|in:FULL_TIME,PART_TIME,WEEKENDS,FLEXIBLE',
                'skills'       => 'max:255',
                'about'        => 'max:600',
            ]
            : [
                'org_name'     => 'required|min:2|max:160',
                'contact_name' => 'max:160',
                'area_id'      => 'required|int',
                'about'        => 'max:600',
            ];

        $errors = Validator::validate($_POST, $rules);
        $areaId = (int) ($_POST['area_id'] ?? 0);

        if ($areaId > 0 && !$this->catalog->areaExists($areaId)) {
            $errors['area_id'] = 'Please choose a valid area.';
        }

        if ($errors !== []) {
            $this->renderProfile($user, $errors, $_POST);

            return;
        }

        if ($isWorker) {
            $this->accounts->updateWorkerProfile((int) $user['id'], [
                'full_name'    => trim((string) $_POST['full_name']),
                'area_id'      => $areaId,
                'skills'       => trim((string) ($_POST['skills'] ?? '')),
                'availability' => (string) $_POST['availability'],
                'about'        => trim((string) ($_POST['about'] ?? '')),
            ]);
        } else {
            $this->accounts->updateEmployerProfile((int) $user['id'], [
                'org_name'     => trim((string) $_POST['org_name']),
                'contact_name' => trim((string) ($_POST['contact_name'] ?? '')),
                'area_id'      => $areaId,
                'about'        => trim((string) ($_POST['about'] ?? '')),
            ]);
        }

        flash('success', 'Profile updated.');
        redirect('/profile');
    }

    /** @param array<string, mixed> $user */
    private function renderProfile(array $user, array $errors, array $old): void
    {
        $view = $user['role'] === 'employer' ? 'employer/profile' : 'worker/profile';

        View::render($view, [
            'title'  => 'Your profile',
            'user'   => $user,
            'areas'  => $this->catalog->areas(),
            'errors' => $errors,
            'old'    => $old,
        ]);
    }
}
