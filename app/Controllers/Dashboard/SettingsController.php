<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\TenantResolver;
use App\Core\Validator;
use App\Models\DeliveryZone;
use App\Models\PushSubscription;
use App\Models\Tenant;
use App\Services\AuditLog;

class SettingsController
{
    /** Hub Impostazioni — pagina a griglia che raggruppa tutte le sezioni. */
    public function index(Request $request): void
    {
        view('dashboard/settings/index', [
            'title'      => 'Impostazioni',
            'activeMenu' => 'settings',
            'tenant'     => TenantResolver::current(),
        ], 'dashboard');
    }

    public function general(Request $request): void
    {
        view('dashboard/settings/general', [
            'title'      => 'Impostazioni',
            'activeMenu' => 'settings',
            'tenant'     => TenantResolver::current(),
        ], 'dashboard');
    }

    public function updateGeneral(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $data = $request->all();

        // Step orari attuale, PRIMA dell'update: serve per avvisare il
        // ristoratore se lo cambia. Gli orari del widget vengono dalla
        // griglia "Orari e Coperti" (tabella time_slots), non dallo step:
        // senza risalvare la griglia il widget resta sui vecchi orari.
        $oldTimeStep = (int)(TenantResolver::current()['time_step'] ?? 30);

        $v = Validator::make($data)
            ->required('name', 'Nome ristorante')
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->between('table_duration', 15, 300, 'Durata tavolo (minuti)')
            ->between('time_step', 5, 120, 'Intervallo orario (minuti)');

        if (isset($data['cancellation_policy']) && mb_strlen($data['cancellation_policy']) > 2000) {
            flash('danger', 'La policy di cancellazione non può superare 2000 caratteri.');
            Response::redirect(url('dashboard/settings/general'));
        }

        if (isset($data['booking_instructions']) && mb_strlen($data['booking_instructions']) > 1000) {
            flash('danger', 'Le istruzioni per il cliente non possono superare 1000 caratteri.');
            Response::redirect(url('dashboard/settings/general'));
        }

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Response::redirect(url('dashboard/settings/general'));
        }

        // Validate segment thresholds: occasionale < abituale < vip
        $segOcc = max(1, (int)($data['segment_occasionale'] ?? 2));
        $segAbi = max(2, (int)($data['segment_abituale'] ?? 4));
        $segVip = max(3, (int)($data['segment_vip'] ?? 10));

        if ($segOcc >= $segAbi || $segAbi >= $segVip) {
            flash('danger', 'Le soglie segmento devono essere crescenti: Occasionale < Abituale < VIP.');
            Response::redirect(url('dashboard/settings/general'));
        }

        // Handle logo upload
        $logoUrl = null;
        if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoUrl = $this->handleLogoUpload($tenantId);
            if ($logoUrl === false) {
                Response::redirect(url('dashboard/settings/general'));
            }
        }

        // Handle logo removal
        if (!empty($data['remove_logo'])) {
            $currentTenant = TenantResolver::current();
            $this->deleteOldLogo($currentTenant);
            $logoUrl = '';
        }

        // Validate confirmation_mode
        $confirmationMode = ($data['confirmation_mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto';

        // Soglia approvazione manuale per gruppi numerosi (NULL = disattivata).
        // Stesso range della caparra (2-20). Validazione difensiva.
        $approvalMin = !empty($data['manual_approval_min_party_size']) ? (int)$data['manual_approval_min_party_size'] : null;
        if ($approvalMin !== null && ($approvalMin < 2 || $approvalMin > 20)) $approvalMin = null;

        // Validate website_url: opzionale. Se valorizzato deve essere un URL
        // valido; se manca lo schema lo aggiungiamo (https://) per default,
        // cosi' il ristoratore puo' digitare "miosito.it" senza pensieri.
        $websiteUrl = trim((string)($data['website_url'] ?? ''));
        if ($websiteUrl !== '') {
            if (!preg_match('/^https?:\/\//i', $websiteUrl)) {
                $websiteUrl = 'https://' . $websiteUrl;
            }
            if (!filter_var($websiteUrl, FILTER_VALIDATE_URL) || mb_strlen($websiteUrl) > 255) {
                flash('danger', 'L\'URL del sito web non è valido.');
                Response::redirect(url('dashboard/settings/general'));
            }
        } else {
            $websiteUrl = null;
        }

        $updateData = [
            'name'                 => $data['name'] ?? '',
            'email'                => $data['email'] ?? '',
            'phone'                => $data['phone'] ?? null,
            'address'              => $data['address'] ?? null,
            'website_url'          => $websiteUrl,
            'cancellation_policy'  => $data['cancellation_policy'] ?? null,
            'booking_instructions' => $data['booking_instructions'] ?? null,
            'confirmation_mode'    => $confirmationMode,
            'manual_approval_min_party_size' => $approvalMin,
            'table_duration'       => (int)($data['table_duration'] ?? 90),
            'time_step'            => (int)($data['time_step'] ?? 30),
            'segment_occasionale'  => $segOcc,
            'segment_abituale'     => $segAbi,
            'segment_vip'          => $segVip,
            'promo_widget_only'         => !empty($data['promo_widget_only']) ? 1 : 0,
            'pet_friendly'              => !empty($data['pet_friendly']) ? 1 : 0,
            'kids_friendly'             => !empty($data['kids_friendly']) ? 1 : 0,
        ];

        if ($logoUrl !== null) {
            $updateData['logo_url'] = $logoUrl ?: null;
        }

        (new Tenant())->update($tenantId, $updateData);

        AuditLog::log(AuditLog::SETTINGS_UPDATED, null, Auth::id(), $tenantId);

        // Refresh tenant in resolver
        $tenant = (new Tenant())->findById($tenantId);
        TenantResolver::setCurrent($tenant);

        flash('success', 'Impostazioni aggiornate.');

        // Step orari cambiato: flash dedicato, renderizzato come banner
        // ambra brand-coerente in general.php (il flash generico escapa
        // l'HTML e non permette il link a "Orari e Coperti").
        $newTimeStep = (int)($data['time_step'] ?? 30);
        if ($newTimeStep !== $oldTimeStep) {
            Session::flash('time_step_changed', ['from' => $oldTimeStep, 'to' => $newTimeStep]);
        }
        Response::redirect(url('dashboard/settings/general'));
    }

    /**
     * Handle logo file upload. Returns URL on success, false on error.
     */
    private function handleLogoUpload(int $tenantId): string|false
    {
        $file = $_FILES['logo'];
        // Solo formati bitmap: gli SVG sono XML e possono contenere
        // <script> embedded eseguibili se l'URL del file viene aperto
        // direttamente in una nuova tab (Apache serve come image/svg+xml).
        // Rimossi 2026-06-08 per chiudere il vettore XSS (audit finding #2).
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowed)) {
            flash('danger', 'Formato logo non valido. Usa JPG, PNG o WebP.');
            return false;
        }

        if ($file['size'] > $maxSize) {
            flash('danger', 'Il logo non può superare 2 MB.');
            return false;
        }

        // Delete old logo
        $this->deleteOldLogo(TenantResolver::current());

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'png',
        };

        $uploadDir = BASE_PATH . '/public/uploads/tenants/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'logo_' . $tenantId . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            flash('danger', 'Errore durante il caricamento del logo.');
            return false;
        }

        return url('uploads/tenants/' . $filename);
    }

    private function deleteOldLogo(array $tenant): void
    {
        if (empty($tenant['logo_url'])) {
            return;
        }
        // Extract relative path from URL
        $baseUrl = url('');
        $relative = str_replace($baseUrl, '', $tenant['logo_url']);
        $relative = ltrim($relative, '/');
        $oldPath = BASE_PATH . '/public/uploads/tenants/' . basename($relative);
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    public function notifications(Request $request): void
    {
        $tenantId = Auth::tenantId();

        // Elenco dispositivi push attualmente subscribed per il tenant (mostrati
        // nella sezione "Dispositivi collegati" + conteggio). Dato a livello tenant.
        $pushDevices = (new PushSubscription())->getByTenant($tenantId);

        view('dashboard/settings/notifications', [
            'title'           => 'Notifiche',
            'activeMenu'      => 'settings-notifications',
            'tenant'          => TenantResolver::current(),
            'pushDeviceCount' => count($pushDevices),
            'pushDevices'     => $pushDevices,
        ], 'dashboard');
    }

    public function updateNotifications(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $data = $request->all();

        // Volume: clamp 0-100 lato server (il range HTML potrebbe essere
        // bypassato, e l'audio API ha bisogno di valori validi).
        $volume = isset($data['notification_sound_volume']) ? (int)$data['notification_sound_volume'] : 70;
        $volume = max(0, min(100, $volume));

        $updateData = [
            'notify_new_reservation'    => !empty($data['notify_new_reservation']) ? 1 : 0,
            'notify_cancellation'       => !empty($data['notify_cancellation']) ? 1 : 0,
            'notif_title_new_reservation' => trim($data['notif_title_new_reservation'] ?? '') ?: null,
            'notif_body_new_reservation'  => trim($data['notif_body_new_reservation'] ?? '') ?: null,
            'notif_title_cancellation'    => trim($data['notif_title_cancellation'] ?? '') ?: null,
            'notif_body_cancellation'     => trim($data['notif_body_cancellation'] ?? '') ?: null,
            'notif_title_deposit'         => trim($data['notif_title_deposit'] ?? '') ?: null,
            'notif_body_deposit'          => trim($data['notif_body_deposit'] ?? '') ?: null,

            // Notifiche audio (Fase notifiche sonore)
            'notification_sound_enabled'  => !empty($data['notification_sound_enabled']) ? 1 : 0,
            'notification_sound_volume'   => $volume,
            'sound_on_new_reservation'    => !empty($data['sound_on_new_reservation']) ? 1 : 0,
            'sound_on_cancellation'       => !empty($data['sound_on_cancellation']) ? 1 : 0,
            'sound_on_deposit_received'   => !empty($data['sound_on_deposit_received']) ? 1 : 0,
            'sound_on_new_order'          => !empty($data['sound_on_new_order']) ? 1 : 0,
            'sound_on_new_feedback'       => !empty($data['sound_on_new_feedback']) ? 1 : 0,
        ];

        (new Tenant())->update($tenantId, $updateData);

        AuditLog::log(AuditLog::SETTINGS_UPDATED, 'Template notifiche aggiornati', Auth::id(), $tenantId);

        $tenant = (new Tenant())->findById($tenantId);
        TenantResolver::setCurrent($tenant);

        flash('success', 'Impostazioni notifiche aggiornate.');
        Response::redirect(url('dashboard/settings/notifications'));
    }

    public function deposit(Request $request): void
    {
        $tenant = TenantResolver::current();
        $canUseDeposit = tenant_can('deposit');

        // Mask Stripe keys for display
        $stripeSkMasked = '';
        if (!empty($tenant['stripe_sk'])) {
            $decrypted = decrypt_value($tenant['stripe_sk']);
            if ($decrypted) {
                $stripeSkMasked = substr($decrypted, 0, 8) . '...' . substr($decrypted, -4);
            }
        }
        $stripePkMasked = '';
        if (!empty($tenant['stripe_pk'])) {
            $pk = $tenant['stripe_pk'];
            $stripePkMasked = substr($pk, 0, 8) . '...' . substr($pk, -4);
        }
        $stripeWhMasked = '';
        if (!empty($tenant['stripe_wh_secret'])) {
            $decrypted = decrypt_value($tenant['stripe_wh_secret']);
            if ($decrypted) {
                $stripeWhMasked = substr($decrypted, 0, 8) . '...' . substr($decrypted, -4);
            }
        }

        // Meal categories per la caparra condizionale per fascia
        $mealCategories = (new \App\Models\MealCategory())->findAllByTenant((int)$tenant['id']);

        view('dashboard/settings/deposit', [
            'title'           => 'Caparra',
            'activeMenu'      => 'deposit',
            'tenant'          => $tenant,
            'canUseDeposit'   => $canUseDeposit,
            'stripeSkMasked'  => $stripeSkMasked,
            'stripePkMasked'  => $stripePkMasked,
            'stripeWhMasked'  => $stripeWhMasked,
            'mealCategories'  => $mealCategories,
        ], 'dashboard');
    }

    /**
     * Verifica via API Stripe (SOLO LETTURA) lo stato del webhook del tenant:
     * esiste un endpoint verso il nostro URL? è abilitato? è iscritto agli
     * eventi che ci servono (`checkout.session.completed`/`expired`)? Risponde
     * in JSON per il bottone "Verifica webhook". Non crea/modifica MAI nulla su
     * Stripe; gli errori API sono gestiti in modo morbido (mai 500).
     */
    public function verifyWebhook(Request $request): void
    {
        $tenant = TenantResolver::current();
        if (!tenant_can('deposit')) {
            Response::json(['status' => 'error', 'message' => 'Servizio non disponibile.']);
            return;
        }

        $sk = !empty($tenant['stripe_sk']) ? decrypt_value($tenant['stripe_sk']) : '';
        $secretSet = !empty($tenant['stripe_wh_secret']);

        if (!$sk) {
            Response::json(['status' => 'no-key', 'secret_set' => $secretSet]);
            return;
        }

        $required = ['checkout.session.completed', 'checkout.session.expired'];
        $ourPath  = '/api/v1/stripe/webhook';

        try {
            \Stripe\Stripe::setApiKey($sk);
            $endpoints = \Stripe\WebhookEndpoint::all(['limit' => 100]);

            $match = null;
            foreach ($endpoints->data as $ep) {
                if (str_contains((string)($ep->url ?? ''), $ourPath)) { $match = $ep; break; }
            }

            if (!$match) {
                Response::json(['status' => 'no-endpoint', 'secret_set' => $secretSet]);
                return;
            }
            if (($match->status ?? '') !== 'enabled') {
                Response::json(['status' => 'disabled', 'secret_set' => $secretSet]);
                return;
            }

            $events    = $match->enabled_events ?? [];
            $coversAll = in_array('*', $events, true);
            $missing   = $coversAll ? [] : array_values(array_diff($required, $events));

            if (empty($missing)) {
                Response::json(['status' => 'ok', 'secret_set' => $secretSet]);
            } else {
                Response::json(['status' => 'missing-event', 'missing' => $missing, 'secret_set' => $secretSet]);
            }
        } catch (\Exception $e) {
            app_log('Verifica webhook Stripe fallita (tenant ' . ($tenant['id'] ?? '?') . '): ' . $e->getMessage(), 'warning');
            Response::json(['status' => 'error', 'message' => 'Non è stato possibile verificare ora. Controlla la Secret Key e riprova.']);
        }
    }

    public function updateDeposit(Request $request): void
    {
        if (gate_service('deposit', url('dashboard/settings'))) return;

        $tenantId = Auth::tenantId();
        $tenant = TenantResolver::current();
        $data = $request->all();

        $wantsEnabled = !empty($data['deposit_enabled']);
        $depositMode = in_array($data['deposit_mode'] ?? '', ['per_table', 'per_person']) ? $data['deposit_mode'] : 'per_table';
        $depositType = in_array($data['deposit_type'] ?? '', ['info', 'link', 'stripe', 'guarantee']) ? $data['deposit_type'] : 'info';

        // I tipi 'stripe' e 'guarantee' richiedono entrambi le chiavi Stripe
        $needsStripeKeys = in_array($depositType, ['stripe', 'guarantee'], true);

        // Guard: se tipo Stripe/garanzia e si abilita, le chiavi devono esistere
        if ($wantsEnabled && $needsStripeKeys) {
            $hasKeys = !empty($data['stripe_sk']) && !str_contains($data['stripe_sk'], '...');
            $hasExisting = !empty($tenant['stripe_sk']);
            if (!$hasKeys && !$hasExisting) {
                flash('danger', 'Inserisci le chiavi Stripe per attivare questo tipo di caparra.');
                Response::redirect(url('dashboard/settings/deposit'));
            }
        }

        $minParty = !empty($data['deposit_min_party_size']) ? (int)$data['deposit_min_party_size'] : null;
        if ($minParty !== null && ($minParty < 2 || $minParty > 20)) $minParty = null;

        // Finestra di scadenza per la caparra richiesta manualmente sui gruppi
        // (Stripe/garanzia). Vuoto = nessuna scadenza. Valori ammessi in minuti.
        $allowedWindows = [60, 120, 180, 360, 720, 1440];
        $manualWindow = !empty($data['deposit_manual_window_minutes']) ? (int)$data['deposit_manual_window_minutes'] : null;
        if ($manualWindow !== null && !in_array($manualWindow, $allowedWindows, true)) $manualWindow = 120;

        // Giorni in cui la caparra è attiva (ISO 1=lun..7=dom). Vuoto → tutti (fallback sicuro).
        $validDays = [];
        foreach ((array)($data['deposit_days'] ?? []) as $d) {
            $d = (int)$d;
            if ($d >= 1 && $d <= 7) $validDays[] = $d;
        }
        if (empty($validDays)) $validDays = [1, 2, 3, 4, 5, 6, 7];
        sort($validDays);

        $updateData = [
            'deposit_enabled' => $wantsEnabled ? 1 : 0,
            'deposit_amount'  => !empty($data['deposit_amount']) ? (float)$data['deposit_amount'] : null,
            'deposit_mode'    => $depositMode,
            'deposit_min_party_size' => $minParty,
            'deposit_manual_window_minutes' => $manualWindow,
            'deposit_days'    => implode(',', $validDays),
            'deposit_type'    => $depositType,
            'deposit_bank_info'    => trim($data['deposit_bank_info'] ?? ''),
            'deposit_payment_link' => trim($data['deposit_payment_link'] ?? ''),
        ];

        // Handle Stripe keys — solo per tipi che le usano (stripe/garanzia), non sovrascrivere se mascherate/vuote
        if ($needsStripeKeys && !empty($data['stripe_sk']) && !str_contains($data['stripe_sk'], '...')) {
            $sk = trim($data['stripe_sk']);
            if (!preg_match('/^sk_(live|test)_/', $sk)) {
                flash('danger', 'La Secret Key deve iniziare con sk_live_ o sk_test_');
                Response::redirect(url('dashboard/settings/deposit'));
            }
            $updateData['stripe_sk'] = encrypt_value($sk);
        }

        if ($needsStripeKeys && !empty($data['stripe_pk']) && !str_contains($data['stripe_pk'], '...')) {
            $pk = trim($data['stripe_pk']);
            if (!preg_match('/^pk_(live|test)_/', $pk)) {
                flash('danger', 'La Publishable Key deve iniziare con pk_live_ o pk_test_');
                Response::redirect(url('dashboard/settings/deposit'));
            }
            $updateData['stripe_pk'] = $pk; // Not encrypted (public key)
        }

        if ($needsStripeKeys && !empty($data['stripe_wh_secret']) && !str_contains($data['stripe_wh_secret'], '...')) {
            $wh = trim($data['stripe_wh_secret']);
            if (!str_starts_with($wh, 'whsec_')) {
                flash('danger', 'Il Webhook Secret deve iniziare con whsec_');
                Response::redirect(url('dashboard/settings/deposit'));
            }
            $updateData['stripe_wh_secret'] = encrypt_value($wh);
        }

        (new Tenant())->update($tenantId, $updateData);

        // Fasce orarie in cui la caparra è attiva. Il form invia gli id delle categorie
        // su cui applicarla; vuoto → tutte (fallback sicuro, gestito dal model).
        $selectedCats = [];
        foreach ((array)($data['deposit_categories'] ?? []) as $catId) {
            $catId = (int)$catId;
            if ($catId > 0) $selectedCats[] = $catId;
        }
        (new \App\Models\MealCategory())->setDepositRequired($tenantId, $selectedCats);

        AuditLog::log(AuditLog::DEPOSIT_UPDATED, "Tipo: {$depositType}", Auth::id(), $tenantId);

        flash('success', 'Impostazioni caparra aggiornate.');
        Response::redirect(url('dashboard/settings/deposit'));
    }

    // ─── REVIEWS ──────────────────────────────────────────────

    public function reviews(Request $request): void
    {
        $tenant = TenantResolver::current();
        $canUse = tenant_can('review_management');

        view('dashboard/settings/reviews', [
            'title'      => 'Recensioni',
            'activeMenu' => 'settings-reviews',
            'tenant'     => $tenant,
            'canUse'     => $canUse,
        ], 'dashboard');
    }

    public function updateReviews(Request $request): void
    {
        if (gate_service('review_management', url('dashboard/settings/reviews'))) return;

        $tenantId = Auth::tenantId();
        $data = $request->all();

        $reviewEnabled = !empty($data['review_enabled']) ? 1 : 0;

        // Validate review_url if enabling
        $reviewUrl = trim($data['review_url'] ?? '');
        if ($reviewEnabled && $reviewUrl !== '' && !filter_var($reviewUrl, FILTER_VALIDATE_URL)) {
            flash('danger', 'Inserisci un URL valido per la piattaforma di recensioni.');
            Response::redirect(url('dashboard/settings/reviews'));
        }

        $delayHours = max(1, min(24, (int)($data['review_delay_hours'] ?? 2)));
        $quietHour = max(0, min(23, (int)($data['review_quiet_hour'] ?? 22)));
        $filterThreshold = max(3, min(5, (int)($data['review_filter_threshold'] ?? 4)));

        $updateData = [
            'review_enabled'          => $reviewEnabled,
            'review_url'              => $reviewUrl ?: null,
            'review_platform_label'   => trim($data['review_platform_label'] ?? '') ?: null,
            'review_delay_hours'      => $delayHours,
            'review_quiet_hour'       => $quietHour,
            'review_filter_enabled'   => !empty($data['review_filter_enabled']) ? 1 : 0,
            'review_filter_threshold' => $filterThreshold,
            'review_filter_message'   => trim($data['review_filter_message'] ?? '') ?: null,
            'review_email_subject'    => trim($data['review_email_subject'] ?? '') ?: null,
            'review_email_body'       => trim($data['review_email_body'] ?? '') ?: null,
            'review_email_cta'        => trim($data['review_email_cta'] ?? '') ?: null,
        ];

        (new Tenant())->update($tenantId, $updateData);

        AuditLog::log(AuditLog::REVIEW_SETTINGS_UPDATED, null, Auth::id(), $tenantId);

        $tenant = (new Tenant())->findById($tenantId);
        TenantResolver::setCurrent($tenant);

        flash('success', 'Impostazioni recensioni aggiornate.');
        Response::redirect(url('dashboard/settings/reviews'));
    }

    // ─── ORDERING ─────────────────────────────────────────────

    public function ordering(Request $request): void
    {
        $tenant = TenantResolver::current();
        $canUse = tenant_can('online_ordering');

        $deliveryZones = [];
        if ($canUse) {
            $deliveryZones = (new DeliveryZone())->findByTenant((int)$tenant['id']);
        }

        view('dashboard/settings/ordering', [
            'title'         => 'Ordini online',
            'activeMenu'    => 'settings-ordering',
            'tenant'        => $tenant,
            'canUse'        => $canUse,
            'deliveryZones' => $deliveryZones,
        ], 'dashboard');
    }

    public function updateOrdering(Request $request): void
    {
        if (gate_service('online_ordering', url('dashboard/settings/ordering'))) return;

        $tenantId = Auth::tenantId();
        $data = $request->all();

        $orderingMode = in_array($data['ordering_mode'] ?? '', ['takeaway', 'delivery', 'both'])
            ? $data['ordering_mode'] : 'takeaway';

        $deliveryMode = in_array($data['delivery_mode'] ?? '', ['simple', 'zones'])
            ? $data['delivery_mode'] : 'simple';

        // Build payment methods string
        $payments = [];
        if (!empty($data['payment_cash'])) $payments[] = 'cash';
        if (!empty($data['payment_stripe'])) $payments[] = 'stripe';
        if (empty($payments)) $payments[] = 'cash';

        $tenantModel = new Tenant();

        // Delivery board
        $boardEnabled = !empty($data['delivery_board_enabled']) ? 1 : 0;
        $boardPin = preg_replace('/[^0-9]/', '', trim($data['delivery_board_pin'] ?? ''));
        if (strlen($boardPin) < 4) $boardPin = null; // min 4 cifre

        // Genera token se abilitato e non esiste ancora, o se richiesto rigenera
        $currentTenant = $tenantModel->findById($tenantId);
        $boardToken = $currentTenant['delivery_board_token'] ?? null;
        if ($boardEnabled && (!$boardToken || !empty($data['regenerate_token']))) {
            $boardToken = $tenantModel->generateDeliveryToken();
        }
        // Se primo pin non impostato, genera automaticamente
        if ($boardEnabled && !$boardPin && !($currentTenant['delivery_board_pin'] ?? null)) {
            $boardPin = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        }

        $updateData = [
            'ordering_enabled'          => !empty($data['ordering_enabled']) ? 1 : 0,
            'ordering_mode'             => $orderingMode,
            'ordering_prep_minutes'     => max(5, (int)($data['ordering_prep_minutes'] ?? 30)),
            'ordering_min_amount'       => max(0, (float)($data['ordering_min_amount'] ?? 0)),
            'ordering_max_per_slot'     => max(1, (int)($data['ordering_max_per_slot'] ?? 10)),
            'ordering_payment_methods'  => implode(',', $payments),
            'ordering_pickup_interval'  => max(5, (int)($data['ordering_pickup_interval'] ?? 15)),
            'ordering_auto_accept'      => !empty($data['ordering_auto_accept']) ? 1 : 0,
            'ordering_hours'            => !empty($data['ordering_hours']) ? $data['ordering_hours'] : null,
            'delivery_mode'             => $deliveryMode,
            'delivery_fee'              => max(0, (float)($data['delivery_fee'] ?? 0)),
            'delivery_min_amount'       => max(0, (float)($data['delivery_min_amount'] ?? 0)),
            'delivery_description'      => trim($data['delivery_description'] ?? '') ?: null,
            'delivery_board_enabled'    => $boardEnabled,
            'delivery_board_token'      => $boardToken,
            'delivery_board_pin'        => $boardPin ?: ($currentTenant['delivery_board_pin'] ?? null),
        ];

        $tenantModel->update($tenantId, $updateData);

        AuditLog::log(AuditLog::SETTINGS_UPDATED, 'Impostazioni ordini aggiornate', Auth::id(), $tenantId);

        $tenant = $tenantModel->findById($tenantId);
        TenantResolver::setCurrent($tenant);

        flash('success', 'Impostazioni ordini aggiornate.');
        Response::redirect(url('dashboard/settings/ordering'));
    }

    // ─── DELIVERY ZONES ───────────────────────────────────────

    public function storeDeliveryZone(Request $request): void
    {
        if (gate_service('online_ordering', url('dashboard/settings/ordering'))) return;

        $tenantId = Auth::tenantId();
        $data = $request->all();

        $v = Validator::make($data)
            ->required('name', 'Nome zona');

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Response::redirect(url('dashboard/settings/ordering'));
        }

        // Parse postal codes (comma or newline separated)
        $codesRaw = $data['postal_codes'] ?? '';
        $codes = array_filter(array_map('trim', preg_split('/[,\n]+/', $codesRaw)));
        $codes = array_values(array_unique($codes));

        if (empty($codes)) {
            flash('danger', 'Inserisci almeno un CAP.');
            Response::redirect(url('dashboard/settings/ordering'));
        }

        (new DeliveryZone())->create($tenantId, [
            'name'         => $data['name'],
            'postal_codes' => $codes,
            'fee'          => (float)($data['fee'] ?? 0),
            'min_amount'   => (float)($data['min_amount'] ?? 0),
            'is_active'    => !empty($data['is_active']) ? 1 : 0,
            'sort_order'   => (int)($data['sort_order'] ?? 0),
        ]);

        flash('success', 'Zona di consegna creata.');
        Response::redirect(url('dashboard/settings/ordering'));
    }

    public function updateDeliveryZone(Request $request): void
    {
        if (gate_service('online_ordering', url('dashboard/settings/ordering'))) return;

        $tenantId = Auth::tenantId();
        $zoneId = (int)$request->param('id');
        $data = $request->all();

        $zone = (new DeliveryZone())->findById($zoneId, $tenantId);
        if (!$zone) {
            flash('danger', 'Zona non trovata.');
            Response::redirect(url('dashboard/settings/ordering'));
        }

        $codesRaw = $data['postal_codes'] ?? '';
        $codes = array_filter(array_map('trim', preg_split('/[,\n]+/', $codesRaw)));
        $codes = array_values(array_unique($codes));

        (new DeliveryZone())->update($zoneId, $tenantId, [
            'name'         => $data['name'] ?? $zone['name'],
            'postal_codes' => $codes,
            'fee'          => (float)($data['fee'] ?? 0),
            'min_amount'   => (float)($data['min_amount'] ?? 0),
            'is_active'    => !empty($data['is_active']) ? 1 : 0,
            'sort_order'   => (int)($data['sort_order'] ?? 0),
        ]);

        flash('success', 'Zona aggiornata.');
        Response::redirect(url('dashboard/settings/ordering'));
    }

    public function deleteDeliveryZone(Request $request): void
    {
        if (gate_service('online_ordering', url('dashboard/settings/ordering'))) return;

        $tenantId = Auth::tenantId();
        $zoneId = (int)$request->param('id');

        (new DeliveryZone())->delete($zoneId, $tenantId);

        flash('success', 'Zona eliminata.');
        Response::redirect(url('dashboard/settings/ordering'));
    }
}