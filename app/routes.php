<?php
declare(strict_types=1);

use App\Controllers\ApplicationController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\JobController;
use App\Controllers\ProfileController;
use App\Router;

/** @var Router $router */
$router = new Router();

// ------------------------------------------------------------ public / auth
$router->get('/', [DashboardController::class, 'splash']);
$router->get('/home', [DashboardController::class, 'splash']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'requestOtp']);
$router->get('/otp', [AuthController::class, 'showOtp']);
$router->post('/otp', [AuthController::class, 'verifyOtp']);
$router->post('/otp/resend', [AuthController::class, 'resendOtp']);
$router->get('/role', [AuthController::class, 'showRole']);
$router->post('/role', [AuthController::class, 'chooseRole']);
$router->get('/onboarding', [AuthController::class, 'showOnboarding']);
$router->post('/onboarding', [AuthController::class, 'onboard']);
$router->post('/logout', [AuthController::class, 'logout']);

// ---------------------------------------------------------------- routing
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/worker', [DashboardController::class, 'worker']);
$router->get('/employer', [DashboardController::class, 'employer']);

// ------------------------------------------------------------------- jobs
$router->get('/jobs', [JobController::class, 'index']);
$router->get('/jobs/{publicId}', [JobController::class, 'show']);
$router->get('/employer/jobs', [JobController::class, 'mine']);
$router->get('/employer/jobs/new', [JobController::class, 'create']);
$router->post('/employer/jobs', [JobController::class, 'store']);
$router->post('/employer/jobs/{publicId}/publish', [JobController::class, 'publish']);
$router->post('/employer/jobs/{publicId}/cancel', [JobController::class, 'cancel']);
$router->get('/employer/jobs/{publicId}/queue', [ApplicationController::class, 'queue']);

// ----------------------------------------------------------- applications
$router->get('/applications', [ApplicationController::class, 'mine']);
$router->post('/applications', [ApplicationController::class, 'store']);
$router->post('/applications/{publicId}/withdraw', [ApplicationController::class, 'withdraw']);
$router->post('/applications/{publicId}/decision', [ApplicationController::class, 'decide']);

// --------------------------------------------------------------- profiles
$router->get('/profile', [ProfileController::class, 'edit']);
$router->post('/profile', [ProfileController::class, 'update']);

return $router;
