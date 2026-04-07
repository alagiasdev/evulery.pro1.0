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
use App\Controllers\Admin\ActivityLogController;
use App\Controllers\Admin\UsersController;
use App\Controllers\Dashboard\HomeController;
use App\Controllers\Dashboard\ReservationsController;
use App\Controllers\Dashboard\CustomersController;
use App\Controllers\Dashboard\SettingsController;
use App\Controllers\Dashboard\SlotsController;
use App\Controllers\Dashboard\DomainController;
use App\Controllers\Dashboard\MealCategoriesController;
use App\Controllers\Dashboard\ClosuresController;
use App\Controllers\Dashboard\PromotionsController;
use App\Controllers\Dashboard\MenuController;
use App\Controllers\Dashboard\SuspendedController;
use App\Controllers\Dashboard\ImpersonationController;
use App\Controllers\Dashboard\CommunicationsController;
use App\Controllers\Dashboard\NotificationController;
use App\Controllers\Dashboard\PushController;
use App\Controllers\Dashboard\OrderController;
use App\Controllers\ProfileController;
use App\Controllers\UnsubscribeController;
use App\Controllers\Menu\MenuPageController;
use App\Controllers\Api\MenuApiController;
use App\Controllers\Booking\BookingController;
use App\Controllers\ManageReservationController;
use App\Controllers\Api\AvailabilityController;
use App\Controllers\Api\ReservationApiController;
use App\Controllers\Api\WebhookController;
use App\Controllers\Api\OrderApiController;
use App\Controllers\Ordering\OrderStoreController;
use App\Controllers\Delivery\DeliveryBoardController;
use App\Controllers\Dashboard\ReviewController;
use App\Controllers\ReviewLandingController;

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
$router->group('/dashboard', ['auth', 'tenant', 'csrf', 'dashboard-ratelimit'], function ($r) {
    $r->get('/suspended', [SuspendedController::class, 'index']);
    $r->post('/stop-impersonation', [ImpersonationController::class, 'stop']);
    $r->get('', [HomeController::class, 'index']);
    $r->get('/reservations', [ReservationsController::class, 'index']);
    $r->get('/reservations/create', [ReservationsController::class, 'create']);
    $r->get('/reservations/export', [ReservationsController::class, 'export']);
    $r->post('/reservations', [ReservationsController::class, 'store']);
    $r->get('/reservations/{id}', [ReservationsController::class, 'show']);
    $r->get('/reservations/{id}/edit', [ReservationsController::class, 'edit']);
    $r->post('/reservations/{id}/edit', [ReservationsController::class, 'update']);
    $r->post('/reservations/{id}/status', [ReservationsController::class, 'updateStatus']);
    $r->post('/reservations/{id}/deposit-paid', [ReservationsController::class, 'markDepositPaid']);
    $r->post('/reservations/{id}/deposit-refunded', [ReservationsController::class, 'markDepositRefunded']);
    $r->post('/reservations/{id}/notes', [ReservationsController::class, 'updateNotes']);
    $r->post('/reservations/{id}/delete', [ReservationsController::class, 'destroy']);
    $r->get('/customers', [CustomersController::class, 'index']);
    $r->get('/customers/stats', [CustomersController::class, 'stats']);
    $r->get('/customers/search/json', [CustomersController::class, 'searchJson']);
    $r->get('/customers/import', [CustomersController::class, 'import']);
    $r->post('/customers/import', [CustomersController::class, 'processImport']);
    $r->get('/customers/{id}', [CustomersController::class, 'show']);
    $r->post('/customers/{id}/notes', [CustomersController::class, 'updateNotes']);
    $r->post('/customers/{id}/toggle-block', [CustomersController::class, 'toggleBlock']);
    $r->post('/customers/{id}/resubscribe', [CustomersController::class, 'resubscribe']);
    $r->post('/customers/{id}/birthday', [CustomersController::class, 'updateBirthday']);
    $r->post('/customers/{id}/add-tag', [CustomersController::class, 'addTag']);
    $r->post('/customers/{id}/remove-tag', [CustomersController::class, 'removeTag']);
    $r->get('/settings', [SettingsController::class, 'general']);
    $r->post('/settings', [SettingsController::class, 'updateGeneral']);
    $r->get('/settings/slots', [SlotsController::class, 'index']);
    $r->post('/settings/slots', [SlotsController::class, 'update']);
    $r->get('/settings/notifications', [SettingsController::class, 'notifications']);
    $r->post('/settings/notifications', [SettingsController::class, 'updateNotifications']);
    $r->get('/settings/deposit', [SettingsController::class, 'deposit']);
    $r->post('/settings/deposit', [SettingsController::class, 'updateDeposit']);
    $r->get('/settings/meal-categories', [MealCategoriesController::class, 'index']);
    $r->post('/settings/meal-categories', [MealCategoriesController::class, 'update']);
    $r->get('/settings/domain', [DomainController::class, 'index']);
    $r->post('/settings/domain', [DomainController::class, 'update']);
    $r->post('/settings/domain/verify', [DomainController::class, 'verify']);
    $r->get('/settings/closures', [ClosuresController::class, 'index']);
    $r->post('/settings/closures', [ClosuresController::class, 'store']);
    $r->post('/settings/closures/delete-group', [ClosuresController::class, 'deleteGroup']);
    $r->post('/settings/closures/{id}/delete', [ClosuresController::class, 'delete']);
    $r->get('/settings/promotions', [PromotionsController::class, 'index']);
    $r->post('/settings/promotions', [PromotionsController::class, 'store']);
    $r->get('/settings/promotions/{id}/edit', [PromotionsController::class, 'edit']);
    $r->post('/settings/promotions/{id}/update', [PromotionsController::class, 'update']);
    $r->post('/settings/promotions/{id}/toggle', [PromotionsController::class, 'toggle']);
    $r->post('/settings/promotions/{id}/delete', [PromotionsController::class, 'delete']);
    $r->get('/menu', [MenuController::class, 'index']);
    $r->get('/menu/categories', [MenuController::class, 'categoriesIndex']);
    $r->get('/menu/appearance', [MenuController::class, 'appearanceIndex']);
    $r->get('/menu/items/create', [MenuController::class, 'createItem']);
    $r->post('/menu/items', [MenuController::class, 'storeItem']);
    $r->get('/menu/items/{id}/edit', [MenuController::class, 'editItem']);
    $r->post('/menu/items/{id}/update', [MenuController::class, 'updateItem']);
    $r->post('/menu/items/{id}/toggle', [MenuController::class, 'toggleAvailable']);
    $r->post('/menu/items/{id}/toggle-special', [MenuController::class, 'toggleDailySpecial']);
    $r->post('/menu/items/{id}/delete', [MenuController::class, 'deleteItem']);
    $r->post('/menu/categories', [MenuController::class, 'storeCategory']);
    $r->post('/menu/categories/{id}/update', [MenuController::class, 'updateCategory']);
    $r->post('/menu/categories/{id}/delete', [MenuController::class, 'deleteCategory']);
    $r->post('/menu/toggle', [MenuController::class, 'toggleMenu']);
    $r->post('/menu/settings', [MenuController::class, 'saveSettings']);
    // Communications (email broadcast)
    $r->get('/communications', [CommunicationsController::class, 'index']);
    $r->get('/communications/create', [CommunicationsController::class, 'create']);
    $r->get('/communications/credits', [CommunicationsController::class, 'credits']);
    $r->get('/communications/preview', [CommunicationsController::class, 'preview']);
    $r->post('/communications', [CommunicationsController::class, 'store']);
    $r->get('/communications/{id}', [CommunicationsController::class, 'show']);
    $r->post('/communications/{id}/delete', [CommunicationsController::class, 'destroy']);
    $r->post('/communications/{id}/archive', [CommunicationsController::class, 'archive']);
    $r->post('/communications/{id}/send-now', [CommunicationsController::class, 'sendNow']);
    $r->post('/communications/{id}/retry', [CommunicationsController::class, 'retryFailed']);
    // Notifications
    $r->get('/notifications', [NotificationController::class, 'index']);
    $r->get('/notifications/unread', [NotificationController::class, 'apiUnread']);
    $r->get('/notifications/recent', [NotificationController::class, 'apiRecent']);
    $r->post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    $r->post('/notifications/delete-all', [NotificationController::class, 'destroyAll']);
    $r->post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    $r->post('/notifications/{id}/delete', [NotificationController::class, 'destroy']);
    // Push
    $r->post('/push/subscribe', [PushController::class, 'subscribe']);
    $r->post('/push/unsubscribe', [PushController::class, 'unsubscribe']);
    $r->get('/push/vapid-key', [PushController::class, 'vapidKey']);
    // Orders (online ordering)
    $r->get('/orders', [OrderController::class, 'index']);
    $r->get('/orders/history', [OrderController::class, 'history']);
    $r->get('/orders/history/orders', [OrderController::class, 'historyOrders']);
    $r->get('/orders/history/rankings', [OrderController::class, 'historyRankings']);
    $r->get('/orders/history/csv', [OrderController::class, 'exportCsv']);
    $r->get('/orders/api/kanban', [OrderController::class, 'apiKanban']);
    $r->get('/orders/api/stats', [OrderController::class, 'apiStats']);
    $r->get('/orders/{id}', [OrderController::class, 'show']);
    $r->post('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    // Reputation management
    $r->get('/reputation', [ReviewController::class, 'index']);
    $r->get('/reputation/feedback', [ReviewController::class, 'feedback']);
    $r->get('/reputation/history', [ReviewController::class, 'history']);
    $r->get('/reputation/api/stats', [ReviewController::class, 'apiStats']);
    $r->post('/reputation/feedback/{id}/reply', [ReviewController::class, 'replyFeedback']);
    $r->post('/reputation/feedback/{id}/status', [ReviewController::class, 'updateFeedbackStatus']);
    // Settings reviews
    $r->get('/settings/reviews', [SettingsController::class, 'reviews']);
    $r->post('/settings/reviews', [SettingsController::class, 'updateReviews']);
    // Settings ordering
    $r->get('/settings/ordering', [SettingsController::class, 'ordering']);
    $r->post('/settings/ordering', [SettingsController::class, 'updateOrdering']);
    $r->post('/settings/ordering/zones', [SettingsController::class, 'storeDeliveryZone']);
    $r->post('/settings/ordering/zones/{id}/update', [SettingsController::class, 'updateDeliveryZone']);
    $r->post('/settings/ordering/zones/{id}/delete', [SettingsController::class, 'deleteDeliveryZone']);
    $r->get('/profile', [ProfileController::class, 'show']);
    $r->post('/profile', [ProfileController::class, 'update']);
});

// --- SUPER ADMIN ROUTES ---
$router->group('/admin', ['auth', 'admin', 'csrf', 'dashboard-ratelimit'], function ($r) {
    $r->get('', [AdminDashboardController::class, 'index']);
    $r->get('/tenants', [TenantsController::class, 'index']);
    $r->get('/tenants/create', [TenantsController::class, 'create']);
    $r->post('/tenants', [TenantsController::class, 'store']);
    $r->get('/tenants/{id}/edit', [TenantsController::class, 'edit']);
    $r->post('/tenants/{id}/toggle', [TenantsController::class, 'toggle']);
    $r->post('/tenants/{id}/users/{userId}', [TenantsController::class, 'updateUser']);
    $r->post('/tenants/{id}/credits', [TenantsController::class, 'assignCredits']);
    $r->post('/tenants/{id}', [TenantsController::class, 'update']);
    // Subscriptions
    $r->get('/subscriptions', [SubscriptionsController::class, 'index']);
    $r->post('/subscriptions/{id}/change-plan', [SubscriptionsController::class, 'changePlan']);
    // Plans
    $r->get('/subscriptions/plans', [SubscriptionsController::class, 'plans']);
    $r->post('/subscriptions/plans', [SubscriptionsController::class, 'storePlan']);
    $r->get('/subscriptions/plans/{id}/edit', [SubscriptionsController::class, 'editPlan']);
    $r->post('/subscriptions/plans/{id}', [SubscriptionsController::class, 'updatePlan']);
    $r->post('/subscriptions/plans/{id}/duplicate', [SubscriptionsController::class, 'duplicatePlan']);
    $r->post('/subscriptions/plans/{id}/delete', [SubscriptionsController::class, 'deletePlan']);
    // Services
    $r->get('/subscriptions/services', [SubscriptionsController::class, 'services']);
    $r->post('/subscriptions/services', [SubscriptionsController::class, 'storeService']);
    $r->post('/subscriptions/services/{id}', [SubscriptionsController::class, 'updateService']);
    $r->post('/subscriptions/services/{id}/delete', [SubscriptionsController::class, 'deleteService']);
    // Users
    $r->get('/users', [UsersController::class, 'index']);
    $r->post('/impersonate/{id}', [UsersController::class, 'impersonate']);
    // Activity Log
    $r->get('/activity-log', [ActivityLogController::class, 'index']);
    $r->post('/activity-log/purge', [ActivityLogController::class, 'purge']);
    // Profile
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
    $r->post('/tenants/{slug}/reservations/{id}/retry-payment', [ReservationApiController::class, 'retryPayment']);
    $r->get('/tenants/{slug}/menu', [MenuApiController::class, 'index']);
    $r->get('/tenants/{slug}/order-menu', [OrderApiController::class, 'menu']);
    $r->post('/tenants/{slug}/orders', [OrderApiController::class, 'store']);
    $r->post('/stripe/webhook', [WebhookController::class, 'handle']);
    // Review landing API (public)
    $r->post('/tenants/{slug}/review', [ReviewLandingController::class, 'submitRating']);
    $r->post('/tenants/{slug}/review/feedback', [ReviewLandingController::class, 'submitFeedback']);
});

// --- EMAIL UNSUBSCRIBE (public, GDPR) ---
$router->get('/email/unsubscribe/{token}', [UnsubscribeController::class, 'show'], ['ratelimit']);

// --- MANAGE RESERVATION (magic link, public) ---
$router->get('/manage/{token}', [ManageReservationController::class, 'show']);
$router->post('/manage/{token}/cancel', [ManageReservationController::class, 'cancel'], ['csrf']);

// --- DELIVERY BOARD (public, token-based) ---
$router->get('/delivery/{token}', [DeliveryBoardController::class, 'show']);
$router->post('/delivery/{token}/auth', [DeliveryBoardController::class, 'auth'], ['csrf']);
$router->get('/delivery/{token}/board', [DeliveryBoardController::class, 'board']);
$router->post('/delivery/{token}/complete/{id}', [DeliveryBoardController::class, 'complete'], ['csrf']);

// --- PUBLIC MENU (must be before /{slug} catch-all) ---
$router->get('/{slug}/menu', [MenuPageController::class, 'show']);

// --- PUBLIC ORDERING (must be before /{slug} catch-all) ---
$router->get('/{slug}/order', [OrderStoreController::class, 'show']);
$router->get('/{slug}/order/success', [OrderStoreController::class, 'success']);

// --- PUBLIC REVIEW LANDING (must be before /{slug} catch-all) ---
$router->get('/{slug}/review', [ReviewLandingController::class, 'show']);
$router->get('/{slug}/review/open', [ReviewLandingController::class, 'trackOpen']);

// --- PUBLIC BOOKING ROUTES (tenant-scoped, must be last) ---
$router->get('/{slug}', [BookingController::class, 'show']);
$router->get('/{slug}/booking/success', [BookingController::class, 'success']);
$router->get('/{slug}/booking/cancel', [BookingController::class, 'cancelPayment']);

// --- HOME ---
$router->get('/', [LoginController::class, 'showForm']);
