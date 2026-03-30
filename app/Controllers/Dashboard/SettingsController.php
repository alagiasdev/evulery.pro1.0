<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Core\Validator;
use App\Models\DeliveryZone;
use App\Models\Tenant;
use App\Services\AuditLog;

class SettingsController
{
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

        $v = Validator::make($data)
            ->required('name', 'Nome ristorante')
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->between('table_duration', 15, 300, 'Durata tavolo (minuti)')
            ->between('time_step', 5, 120, 'Intervallo orario (minuti)');

        if (isset($data['cancellation_policy']) && mb_strlen($data['cancellation_policy']) > 2000) {
            flash('danger', 'La policy di cancellazione non può superare 2000 caratteri.');
            Response::redirect(url('dashboard/settings'));
        }

        if (isset($data['booking_instructions']) && mb_strlen($data['booking_instructions']) > 1000) {
            flash('danger', 'Le istruzioni per il cliente non possono superare 1000 caratteri.');
            Response::redirect(url('dashboard/settings'));
        }

        if ($v->fails()) {
            flash('danger', $v->firstError());
            Response::redirect(url('dashboard/settings'));
        }

        // Validate segment thresholds: occasionale < abituale < vip
        $segOcc = max(1, (int)($data['segment_occasionale'] ?? 2));
        $segAbi = max(2, (int)($data['segment_abituale'] ?? 4));
        $segVip = max(3, (int)($data['segment_vip'] ?? 10));

        if ($segOcc >= $segAbi || $segAbi >= $segVip) {
            flash('danger', 'Le soglie segmento devono essere crescenti: Occasionale < Abituale < VIP.');
            Response::redirect(url('dashboard/settings'));
        }

        // Handle logo upload
        $logoUrl = null;
        if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoUrl = $this->handleLogoUpload($tenantId);
            if ($logoUrl === false) {
                Response::redirect(url('dashboard/settings'));
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

        $updateData = [
            'name'                 => $data['name'] ?? '',
            'email'                => $data['email'] ?? '',
            'phone'                => $data['phone'] ?? null,
            'address'              => $data['address'] ?? null,
            'cancellation_policy'  => $data['cancellation_policy'] ?? null,
            'booking_instructions' => $data['booking_instructions'] ?? null,
            'confirmation_mode'    => $confirmationMode,
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
        Response::redirect(url('dashboard/settings'));
    }

    /**
     * Handle logo file upload. Returns URL on success, false on error.
     */
    private function handleLogoUpload(int $tenantId): string|false
    {
        $file = $_FILES['logo'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowed)) {
            flash('danger', 'Formato logo non valido. Usa JPG, PNG, WebP o SVG.');
            return false;
        }

        if ($file['size'] > $maxSize) {
            flash('danger', 'Il logo non può superare 2 MB.');
            return false;
        }

        // Delete old logo
        $this->deleteOldLogo(TenantResolver::current());

        $ext = match ($mime) {
            'image/jpeg'    => 'jpg',
            'image/png'     => 'png',
            'image/webp'    => 'webp',
            'image/svg+xml' => 'svg',
            default         => 'png',
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
        view('dashboard/settings/notifications', [
            'title'      => 'Notifiche',
            'activeMenu' => 'settings-notifications',
            'tenant'     => TenantResolver::current(),
        ], 'dashboard');
    }

    public function updateNotifications(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $data = $request->all();

        $updateData = [
            'notify_new_reservation'    => !empty($data['notify_new_reservation']) ? 1 : 0,
            'notify_cancellation'       => !empty($data['notify_cancellation']) ? 1 : 0,
            'notif_title_new_reservation' => trim($data['notif_title_new_reservation'] ?? '') ?: null,
            'notif_body_new_reservation'  => trim($data['notif_body_new_reservation'] ?? '') ?: null,
            'notif_title_cancellation'    => trim($data['notif_title_cancellation'] ?? '') ?: null,
            'notif_body_cancellation'     => trim($data['notif_body_cancellation'] ?? '') ?: null,
            'notif_title_deposit'         => trim($data['notif_title_deposit'] ?? '') ?: null,
            'notif_body_deposit'          => trim($data['notif_body_deposit'] ?? '') ?: null,
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

        view('dashboard/settings/deposit', [
            'title'           => 'Caparra',
            'activeMenu'      => 'deposit',
            'tenant'          => $tenant,
            'canUseDeposit'   => $canUseDeposit,
            'stripeSkMasked'  => $stripeSkMasked,
            'stripePkMasked'  => $stripePkMasked,
            'stripeWhMasked'  => $stripeWhMasked,
        ], 'dashboard');
    }

    public function updateDeposit(Request $request): void
    {
        if (gate_service('deposit', url('dashboard/settings'))) return;

        $tenantId = Auth::tenantId();
        $tenant = TenantResolver::current();
        $data = $request->all();

        $wantsEnabled = !empty($data['deposit_enabled']);
        $depositMode = in_array($data['deposit_mode'] ?? '', ['per_table', 'per_person']) ? $data['deposit_mode'] : 'per_table';
        $depositType = in_array($data['deposit_type'] ?? '', ['info', 'link', 'stripe']) ? $data['deposit_type'] : 'info';

        // Guard: if stripe type and enabling, keys must exist
        if ($wantsEnabled && $depositType === 'stripe') {
            $hasKeys = !empty($data['stripe_sk']) && !str_contains($data['stripe_sk'], '...');
            $hasExisting = !empty($tenant['stripe_sk']);
            if (!$hasKeys && !$hasExisting) {
                flash('danger', 'Inserisci le chiavi Stripe per attivare la caparra con pagamento automatico.');
                Response::redirect(url('dashboard/settings/deposit'));
            }
        }

        $minParty = !empty($data['deposit_min_party_size']) ? (int)$data['deposit_min_party_size'] : null;
        if ($minParty !== null && ($minParty < 2 || $minParty > 20)) $minParty = null;

        $updateData = [
            'deposit_enabled' => $wantsEnabled ? 1 : 0,
            'deposit_amount'  => !empty($data['deposit_amount']) ? (float)$data['deposit_amount'] : null,
            'deposit_mode'    => $depositMode,
            'deposit_min_party_size' => $minParty,
            'deposit_type'    => $depositType,
            'deposit_bank_info'    => trim($data['deposit_bank_info'] ?? ''),
            'deposit_payment_link' => trim($data['deposit_payment_link'] ?? ''),
        ];

        // Handle Stripe keys — only when type is stripe, don't overwrite if masked/empty
        if ($depositType === 'stripe' && !empty($data['stripe_sk']) && !str_contains($data['stripe_sk'], '...')) {
            $sk = trim($data['stripe_sk']);
            if (!preg_match('/^sk_(live|test)_/', $sk)) {
                flash('danger', 'La Secret Key deve iniziare con sk_live_ o sk_test_');
                Response::redirect(url('dashboard/settings/deposit'));
            }
            $updateData['stripe_sk'] = encrypt_value($sk);
        }

        if ($depositType === 'stripe' && !empty($data['stripe_pk']) && !str_contains($data['stripe_pk'], '...')) {
            $pk = trim($data['stripe_pk']);
            if (!preg_match('/^pk_(live|test)_/', $pk)) {
                flash('danger', 'La Publishable Key deve iniziare con pk_live_ o pk_test_');
                Response::redirect(url('dashboard/settings/deposit'));
            }
            $updateData['stripe_pk'] = $pk; // Not encrypted (public key)
        }

        if ($depositType === 'stripe' && !empty($data['stripe_wh_secret']) && !str_contains($data['stripe_wh_secret'], '...')) {
            $wh = trim($data['stripe_wh_secret']);
            if (!str_starts_with($wh, 'whsec_')) {
                flash('danger', 'Il Webhook Secret deve iniziare con whsec_');
                Response::redirect(url('dashboard/settings/deposit'));
            }
            $updateData['stripe_wh_secret'] = encrypt_value($wh);
        }

        (new Tenant())->update($tenantId, $updateData);

        AuditLog::log(AuditLog::DEPOSIT_UPDATED, "Tipo: {$depositType}", Auth::id(), $tenantId);

        flash('success', 'Impostazioni caparra aggiornate.');
        Response::redirect(url('dashboard/settings/deposit'));
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