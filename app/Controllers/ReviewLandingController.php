<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\ReviewRequest;
use App\Models\Tenant;
use App\Services\NotificationService;

class ReviewLandingController
{
    /**
     * GET /{slug}/review — Landing pubblica.
     *
     * Modalità:
     * - ?t=TOKEN  → email tracciata (identifica cliente + prenotazione)
     * - no token  → anonima (embed/QR/NFC)
     * - ?embed=1  → layout minimale
     * - ?source=qr|nfc|embed → salva in review_requests.source
     */
    public function show(Request $request): void
    {
        $slug = $request->param('slug');
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::notFound();
        }

        // Check subscription
        $expiredSub = $tenantModel->getExpiredSubscription((int) $tenant['id']);
        if ($expiredSub) {
            view('booking/suspended', [
                'tenantName'    => $tenant['name'],
                'tenantLogo'    => $tenant['logo_url'],
                'tenantPhone'   => $tenant['phone'] ?? '',
                'tenantEmail'   => $tenant['email'] ?? '',
                'tenantAddress' => $tenant['address'] ?? '',
            ]);
            return;
        }

        // Check service
        if (!$tenant['review_enabled'] || !$tenantModel->canUseService((int) $tenant['id'], 'review_management')) {
            Response::notFound();
        }

        $embed = $request->query('embed') === '1';
        $token = $request->query('t', '');
        $model = new ReviewRequest();

        // Determine state
        $state = 'rating';       // default: show stars
        $reviewRequest = null;

        if ($token !== '') {
            // Email tracked mode
            $reviewRequest = $model->findByToken($token);

            if (!$reviewRequest || (int) $reviewRequest['tenant_id'] !== (int) $tenant['id']) {
                $state = 'expired';
            } elseif ($reviewRequest['rating'] !== null) {
                $state = 'already';
            } else {
                // Check 30-day expiry
                $createdAt = strtotime($reviewRequest['created_at']);
                if ($createdAt && (time() - $createdAt) > 30 * 86400) {
                    $state = 'expired';
                } else {
                    // If filter disabled, redirect directly to review_url
                    if (!$tenant['review_filter_enabled'] && !empty($tenant['review_url'])) {
                        $state = 'direct';
                    } else {
                        $state = 'rating';
                    }
                }
            }
        } else {
            // Anonymous mode (embed/QR/NFC)
            if (!$tenant['review_filter_enabled'] && !empty($tenant['review_url'])) {
                $state = 'direct';
            } else {
                $state = 'rating';
            }
        }

        $viewData = [
            'title'         => 'Recensione - ' . $tenant['name'],
            'tenant'        => $tenant,
            'slug'          => $slug,
            'token'         => $token,
            'state'         => $state,
            'embed'         => $embed,
            'reviewRequest' => $reviewRequest,
            'apiBaseUrl'    => url('api/v1'),
        ];

        if ($embed) {
            view('reviews/landing', $viewData);
        } else {
            view('reviews/landing', $viewData);
        }
    }

    /**
     * POST /api/v1/tenants/{slug}/review — Salva rating.
     * Returns JSON: {redirect: url} or {showFeedback: true}
     */
    public function submitRating(Request $request): void
    {
        header('Content-Type: application/json');

        $slug = $request->param('slug');
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findBySlug($slug);

        if (!$tenant || !$tenant['is_active'] || !$tenant['review_enabled']) {
            http_response_code(404);
            echo json_encode(['error' => 'Non trovato']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $rating = (int) ($data['rating'] ?? 0);
        $token = trim($data['token'] ?? '');
        $source = $data['source'] ?? 'embed';

        if ($rating < 1 || $rating > 5) {
            http_response_code(422);
            echo json_encode(['error' => 'Voto non valido']);
            exit;
        }

        $model = new ReviewRequest();
        $tenantId = (int) $tenant['id'];

        if ($token !== '') {
            // Email tracked mode — find existing request
            $rr = $model->findByToken($token);
            if (!$rr || (int) $rr['tenant_id'] !== $tenantId) {
                http_response_code(404);
                echo json_encode(['error' => 'Token non valido']);
                exit;
            }
            if ($rr['rating'] !== null) {
                http_response_code(409);
                echo json_encode(['error' => 'Già valutato']);
                exit;
            }
            $model->saveRating((int) $rr['id'], $rating);
            $model->markClicked((int) $rr['id']);
            $rrId = (int) $rr['id'];
        } else {
            // Anonymous mode — create new request
            $allowedSources = ['embed', 'qr', 'nfc'];
            if (!in_array($source, $allowedSources)) {
                $source = 'embed';
            }
            $rrId = $model->create([
                'tenant_id' => $tenantId,
                'source'    => $source,
                'rating'    => null, // will save below
            ]);
            $model->saveRating($rrId, $rating);
        }

        // Decide: redirect to platform or show feedback form
        $threshold = (int) ($tenant['review_filter_threshold'] ?? 4);

        if ($tenant['review_filter_enabled'] && $rating < $threshold) {
            // Below threshold → show feedback form
            echo json_encode([
                'showFeedback'  => true,
                'reviewId'      => $rrId,
                'filterMessage' => $tenant['review_filter_message'] ?? 'Ci dispiace! Dicci cosa possiamo migliorare',
            ]);
        } else {
            // At or above threshold → redirect to platform
            $redirect = $tenant['review_url'] ?? '';
            echo json_encode([
                'redirect'  => $redirect,
                'reviewId'  => $rrId,
            ]);
        }
        exit;
    }

    /**
     * POST /api/v1/tenants/{slug}/review/feedback — Salva feedback text.
     */
    public function submitFeedback(Request $request): void
    {
        header('Content-Type: application/json');

        $slug = $request->param('slug');
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findBySlug($slug);

        if (!$tenant || !$tenant['is_active'] || !$tenant['review_enabled']) {
            http_response_code(404);
            echo json_encode(['error' => 'Non trovato']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $reviewId = (int) ($data['review_id'] ?? 0);
        $text = trim($data['feedback_text'] ?? '');

        if ($reviewId < 1 || $text === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Feedback non valido']);
            exit;
        }

        $model = new ReviewRequest();
        $tenantId = (int) $tenant['id'];

        $rr = $model->findById($reviewId, $tenantId);
        if (!$rr) {
            http_response_code(404);
            echo json_encode(['error' => 'Richiesta non trovata']);
            exit;
        }

        $model->saveFeedbackText($reviewId, substr($text, 0, 2000));

        // Bell notification to tenant
        try {
            (new NotificationService())->notifyNewFeedback($rr, $tenant);
        } catch (\Throwable $e) {
            // Don't fail the request
            app_log("Review feedback notification error: " . $e->getMessage());
        }

        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * GET /{slug}/review/open?t=TOKEN — Tracking pixel 1x1.
     */
    public function trackOpen(Request $request): void
    {
        $token = $request->query('t', '');
        if ($token !== '') {
            $model = new ReviewRequest();
            $rr = $model->findByToken($token);
            if ($rr) {
                $model->markOpened((int) $rr['id']);
            }
        }

        // Return 1x1 transparent GIF
        header('Content-Type: image/gif');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        // Smallest valid GIF
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }
}
