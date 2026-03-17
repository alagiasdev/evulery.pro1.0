<?php

/**
 * Route Definitions
 * $router is injected from App.php
 */

use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\PasswordController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\TenantsController;
use App\Controllers\Admin\SubscriptionsController;
use App\Controllers\Dashboard\HomeController;
use App\Controllers\Dashboard\ReservationsController;
use App\Controllers\Dashboard\CustomersController;
use App\Controllers\Dashboard\SettingsController;
use App\Controllers\Dashboard\SlotsController;
use App\Controllers\Dashboard\DomainController;
use App\Controllers\Dashboard\MealCategoriesController;
use App\Controllers\Dashboard\ClosuresController;
use App\Controllers\ProfileController;
use App\Controllers\Booking\BookingController;
use App\Controllers\ManageReservationController;
use App\Controllers\Api\AvailabilityController;
use App\Controllers\Api\ReservationApiController;
use App\Controllers\Api\WebhookController;

// --- AUTH ROUTES ---
$router->group('/auth', ['csrf'], function ($r) {
    $r->get('/login', [LoginController::class, 'showForm']);
    $r->post('/login', [LoginController::class, 'login']);
    $r->post('/logout', [LoginController::class, 'logout']);
    $r->get('/forgot-password', [PasswordController::class, 'showForgot']);
    $r->post('/forgot-password', [PasswordController::class, 'sendReset']);
    $r->get('/reset-password/{token}', [PasswordController::class, 'showReset']);
    $r->post('/reset-password', [PasswordController::class, 'doReset']);
});

// --- DASHBOARD ROUTES (restaurant owner) ---
$router->group('/dashboard', ['auth', 'tenant', 'csrf'], function ($r) {
    $r->get('', [HomeController::class, 'index']);
    $r->get('/reservations', [ReservationsController::class, 'index']);
    $r->get('/reservations/create', [ReservationsController::class, 'create']);
    $r->get('/reservations/export', [ReservationsController::class, 'export']);
    $r->post('/reservations', [ReservationsController::class, 'store']);
    $r->get('/reservations/{id}', [ReservationsController::class, 'show']);
    $r->get('/reservations/{id}/edit', [ReservationsController::class, 'edit']);
    $r->post('/reservations/{id}/edit', [ReservationsController::class, 'update']);
    $r->post('/reservations/{id}/status', [ReservationsController::class, 'updateStatus']);
    $r->post('/reservations/{id}/notes', [ReservationsController::class, 'updateNotes']);
    $r->post('/reservations/{id}/delete', [ReservationsController::class, 'destroy']);
    $r->get('/customers', [CustomersController::class, 'index']);
    $r->get('/customers/search/json', [CustomersController::class, 'searchJson']);
    $r->get('/customers/{id}', [CustomersController::class, 'show']);
    $r->post('/customers/{id}/notes', [CustomersController::class, 'updateNotes']);
    $r->post('/customers/{id}/toggle-block', [CustomersController::class, 'toggleBlock']);
    $r->get('/settings', [SettingsController::class, 'general']);
    $r->post('/settings', [SettingsController::class, 'updateGeneral']);
    $r->get('/settings/slots', [SlotsController::class, 'index']);
    $r->post('/settings/slots', [SlotsController::class, 'update']);
    $r->get('/settings/deposit', [SettingsController::class, 'deposit']);
    $r->post('/settings/deposit', [SettingsController::class, 'updateDeposit']);
    $r->get('/settings/meal-categories', [MealCategoriesController::class, 'index']);
    $r->post('/settings/meal-categories', [MealCategoriesController::class, 'update']);
    $r->get('/settings/domain', [DomainController::class, 'index']);
    $r->post('/settings/domain', [DomainController::class, 'update']);
    $r->post('/settings/domain/verify', [DomainController::class, 'verify']);
    $r->get('/settings/closures', [ClosuresController::class, 'index']);
    $r->post('/settings/closures', [ClosuresController::class, 'store']);
    $r->post('/settings/closures/{id}/delete', [ClosuresController::class, 'delete']);
    $r->get('/profile', [ProfileController::class, 'show']);
    $r->post('/profile', [ProfileController::class, 'update']);
});

// --- SUPER ADMIN ROUTES ---
$router->group('/admin', ['auth', 'admin', 'csrf'], function ($r) {
    $r->get('', [AdminDashboardController::class, 'index']);
    $r->get('/tenants', [TenantsController::class, 'index']);
    $r->get('/tenants/create', [TenantsController::class, 'create']);
    $r->post('/tenants', [TenantsController::class, 'store']);
    $r->get('/tenants/{id}/edit', [TenantsController::class, 'edit']);
    $r->post('/tenants/{id}', [TenantsController::class, 'update']);
    $r->post('/tenants/{id}/toggle', [TenantsController::class, 'toggle']);
    $r->post('/tenants/{id}/users/{userId}', [TenantsController::class, 'updateUser']);
    $r->get('/subscriptions', [SubscriptionsController::class, 'index']);
    $r->get('/profile', [ProfileController::class, 'show']);
    $r->post('/profile', [ProfileController::class, 'update']);
});

// --- API ROUTES (JSON) ---
$router->group('/api/v1', ['ratelimit'], function ($r) {
    $r->get('/tenants/{slug}/availability', [AvailabilityController::class, 'check']);
    $r->get('/tenants/{slug}/closures', [AvailabilityController::class, 'closedDates']);
    $r->post('/tenants/{slug}/reservations', [ReservationApiController::class, 'store']);
    $r->get('/tenants/{slug}/reservations/{id}', [ReservationApiController::class, 'show']);
    $r->post('/tenants/{slug}/reservations/{id}/cancel', [ReservationApiController::class, 'cancel']);
    $r->post('/stripe/webhook', [WebhookController::class, 'handle']);
});

// --- MANAGE RESERVATION (magic link, public) ---
$router->get('/manage/{token}', [ManageReservationController::class, 'show']);
$router->post('/manage/{token}/cancel', [ManageReservationController::class, 'cancel'], ['csrf']);

// --- PUBLIC BOOKING ROUTES (tenant-scoped, must be last) ---
$router->get('/{slug}', [BookingController::class, 'show']);
$router->get('/{slug}/booking/success', [BookingController::class, 'success']);
$router->get('/{slug}/booking/cancel', [BookingController::class, 'cancelPayment']);

// --- HOME ---
$router->get('/', [LoginController::class, 'showForm']);
