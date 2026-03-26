<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Core\Validator;
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
            'notify_new_reservation'    => !empty($data['notify_new_reservation']) ? 1 : 0,
            'notify_cancellation'       => !empty($data['notify_cancellation']) ? 1 : 0,
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

        $updateData = [
            'deposit_enabled' => $wantsEnabled ? 1 : 0,
            'deposit_amount'  => !empty($data['deposit_amount']) ? (float)$data['deposit_amount'] : null,
            'deposit_mode'    => $depositMode,
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
}