<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\AuditLog;

class CustomersController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $search = $request->query('q');
        $segment = $request->query('segment');
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 25;
        $tenant = TenantResolver::current();
        $thresholds = [
            'occ' => (int)($tenant['segment_occasionale'] ?? 2),
            'abi' => (int)($tenant['segment_abituale'] ?? 4),
            'vip' => (int)($tenant['segment_vip'] ?? 10),
        ];

        $customerModel = new Customer();

        // Segment stats (single SQL query, no full load)
        $stats = $customerModel->segmentCounts($tenantId, $thresholds);

        // Count + paginated fetch with search + segment filter
        $total = $customerModel->countByTenantFiltered($tenantId, $search, $segment, $thresholds);

        // Build base URL preserving filters
        $baseParams = [];
        if ($search) $baseParams[] = 'q=' . urlencode($search);
        if ($segment) $baseParams[] = 'segment=' . urlencode($segment);
        $baseUrl = url('dashboard/customers') . ($baseParams ? '?' . implode('&', $baseParams) : '');

        $paginator = new Paginator($total, $perPage, $page, $baseUrl);
        $customers = $customerModel->findByTenantPaginated(
            $tenantId, $search, $segment, $thresholds,
            $paginator->limit(), $paginator->offset()
        );

        view('dashboard/customers/index', [
            'title'      => 'Clienti',
            'activeMenu' => 'customers',
            'customers'  => $customers,
            'search'     => $search,
            'segment'    => $segment,
            'stats'      => $stats,
            'tenant'     => $tenant,
            'pagination' => $paginator->links(),
        ], 'dashboard');
    }

    public function searchJson(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $q = trim($request->query('q', ''));

        if (mb_strlen($q) < 2) {
            Response::json(['success' => true, 'data' => []]);
        }

        $customers = (new Customer())->findByTenant($tenantId, $q);
        $tenant = TenantResolver::current();
        $thOcc = (int)($tenant['segment_occasionale'] ?? 2);
        $thAbi = (int)($tenant['segment_abituale'] ?? 4);
        $thVip = (int)($tenant['segment_vip'] ?? 10);
        $results = [];

        foreach (array_slice($customers, 0, 10) as $c) {
            $bookings = (int)$c['total_bookings'];
            $segment = 'nuovo';
            if ($bookings >= $thVip) $segment = 'vip';
            elseif ($bookings >= $thAbi) $segment = 'abituale';
            elseif ($bookings >= $thOcc) $segment = 'occasionale';

            $results[] = [
                'id'             => $c['id'],
                'first_name'     => $c['first_name'],
                'last_name'      => $c['last_name'],
                'email'          => $c['email'],
                'phone'          => $c['phone'],
                'total_bookings' => $bookings,
                'total_noshow'   => (int)$c['total_noshow'],
                'segment'        => $segment,
                'is_blocked'     => (bool)($c['is_blocked'] ?? false),
            ];
        }

        Response::json(['success' => true, 'data' => $results]);
    }

    public function stats(Request $request): void
    {
        $tenant = TenantResolver::current();
        $canUseStats = tenant_can('statistics');

        if (!$canUseStats) {
            view('dashboard/customers/stats', [
                'title'        => 'Statistiche Clienti',
                'activeMenu'   => 'customers',
                'tenant'       => $tenant,
                'canUseStats'  => false,
                'dateFrom'     => date('Y-m-d', strtotime('-90 days')),
                'dateTo'       => date('Y-m-d'),
                'kpi'          => ['total' => 0, 'new_in_period' => 0, 'avg_bookings' => 0, 'return_rate' => 0, 'noshow_rate' => 0],
                'topClients'   => [],
                'segments'     => ['nuovo' => 0, 'occasionale' => 0, 'abituale' => 0, 'vip' => 0, 'totale' => 0],
                'thresholds'   => ['occ' => 2, 'abi' => 4, 'vip' => 10],
            ], 'dashboard');
            return;
        }

        $tenantId = Auth::tenantId();

        // Period filter (default: last 90 days)
        $dateTo = $request->query('to', date('Y-m-d'));
        $dateFrom = $request->query('from', date('Y-m-d', strtotime('-90 days')));

        // Validate dates
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-d', strtotime('-90 days'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) $dateTo = date('Y-m-d');

        $thresholds = [
            'occ' => (int)($tenant['segment_occasionale'] ?? 2),
            'abi' => (int)($tenant['segment_abituale'] ?? 4),
            'vip' => (int)($tenant['segment_vip'] ?? 10),
        ];

        $customerModel = new Customer();
        $kpi = $customerModel->getStats($tenantId, $dateFrom, $dateTo);
        $topClients = $customerModel->getTopByFrequency($tenantId, $dateFrom, $dateTo);
        $segments = $customerModel->segmentCounts($tenantId, $thresholds);

        view('dashboard/customers/stats', [
            'title'      => 'Statistiche Clienti',
            'activeMenu' => 'customers',
            'canUseStats' => true,
            'tenant'     => $tenant,
            'dateFrom'   => $dateFrom,
            'dateTo'     => $dateTo,
            'kpi'        => $kpi,
            'topClients' => $topClients,
            'segments'   => $segments,
            'thresholds' => $thresholds,
        ], 'dashboard');
    }

    public function show(Request $request): void
    {
        $id = (int)$request->param('id');
        $customer = (new Customer())->findById($id);

        if (!$customer || (int)$customer['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Cliente non trovato.');
            Response::redirect(url('dashboard/customers'));
        }

        $reservations = (new Reservation())->findByCustomer($id, (int)Auth::tenantId());

        view('dashboard/customers/show', [
            'title'        => $customer['first_name'] . ' ' . $customer['last_name'],
            'activeMenu'   => 'customers',
            'customer'     => $customer,
            'reservations' => $reservations,
            'tenant'       => TenantResolver::current(),
        ], 'dashboard');
    }

    public function updateNotes(Request $request): void
    {
        $id = (int)$request->param('id');
        $customer = (new Customer())->findById($id);

        if (!$customer || (int)$customer['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Cliente non trovato.');
            Response::redirect(url('dashboard/customers'));
        }

        $notes = substr($request->input('notes', ''), 0, 2000);
        (new Customer())->updateNotes($id, $notes);

        AuditLog::log(AuditLog::CUSTOMER_NOTES_UPDATED, "Cliente ID: {$id}", Auth::id(), Auth::tenantId());

        flash('success', 'Note aggiornate.');
        Response::redirect(url("dashboard/customers/{$id}"));
    }

    public function import(Request $request): void
    {
        $tenant = TenantResolver::current();
        $step = $request->query('step', '1');
        $preview = [];
        $headers = [];
        $filename = '';

        // Step 2: se c'è un file in sessione, mostra preview
        if ($step === '2' && !empty($_SESSION['import_csv_file'])) {
            $filename = $_SESSION['import_csv_file'];
            $mapping = [
                'first_name'        => (int)($request->query('col_first_name', 0)),
                'last_name'         => (int)($request->query('col_last_name', 1)),
                'email'             => (int)($request->query('col_email', 2)),
                'phone'             => (int)($request->query('col_phone', 3)),
                'birthday'          => (int)($request->query('col_birthday', -1)),
                'last_visit'        => (int)($request->query('col_last_visit', -1)),
                'total_bookings'    => (int)($request->query('col_total_bookings', -1)),
                'tags'              => (int)($request->query('col_tags', -1)),
                'marketing_consent' => (int)($request->query('col_marketing_consent', -1)),
                'notes'             => (int)($request->query('col_notes', -1)),
            ];
            $_SESSION['import_csv_mapping'] = $mapping;

            if (file_exists($filename)) {
                $delim = $this->detectDelimiter($filename);
                $handle = fopen($filename, 'r');
                $headers = fgetcsv($handle, 0, $delim);
                $headerCount = $headers ? count($headers) : 0;
                $rows = [];
                $i = 0;
                while (($row = fgetcsv($handle, 0, $delim)) !== false && $i < 100) {
                    $rows[] = $this->fixWrappedRow($row, $headerCount, $delim);
                    $i++;
                }
                fclose($handle);
                $preview = $rows;
            }
        }

        view('dashboard/customers/import', [
            'title'      => 'Importa clienti',
            'activeMenu' => 'customers',
            'tenant'     => $tenant,
            'step'       => $step,
            'preview'    => $preview,
            'headers'    => $headers ?: [],
            'mapping'    => $_SESSION['import_csv_mapping'] ?? null,
            'filename'   => $filename,
        ], 'dashboard');
    }

    public function processImport(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $action = $request->input('action', 'upload');

        if ($action === 'upload') {
            // Step 1: Upload CSV
            if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                flash('danger', 'Errore nel caricamento del file.');
                Response::redirect(url('dashboard/customers/import'));
                return;
            }

            $file = $_FILES['csv_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['csv', 'txt'])) {
                flash('danger', 'Formato non supportato. Usa un file .csv');
                Response::redirect(url('dashboard/customers/import'));
                return;
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                flash('danger', 'File troppo grande (max 5 MB).');
                Response::redirect(url('dashboard/customers/import'));
                return;
            }

            // Save to temp
            $tmpPath = sys_get_temp_dir() . '/evulery_import_' . $tenantId . '_' . time() . '.csv';
            move_uploaded_file($file['tmp_name'], $tmpPath);
            $_SESSION['import_csv_file'] = $tmpPath;

            // Read headers for mapping
            $delimiter = $this->detectDelimiter($tmpPath);
            $handle = fopen($tmpPath, 'r');
            $headers = fgetcsv($handle, 0, $delimiter);
            fclose($handle);

            if (!$headers || count($headers) < 2) {
                flash('danger', 'Il file non contiene colonne sufficienti.');
                @unlink($tmpPath);
                unset($_SESSION['import_csv_file']);
                Response::redirect(url('dashboard/customers/import'));
                return;
            }

            // Auto-detect column mapping
            $mapping = $this->autoDetectMapping($headers);
            $_SESSION['import_csv_mapping'] = $mapping;

            view('dashboard/customers/import', [
                'title'      => 'Importa clienti',
                'activeMenu' => 'customers',
                'tenant'     => TenantResolver::current(),
                'step'       => 'map',
                'headers'    => $headers,
                'mapping'    => $mapping,
                'preview'    => $this->readPreview($tmpPath, $delimiter, 5),
                'filename'   => $tmpPath,
            ], 'dashboard');
            return;
        }

        if ($action === 'confirm') {
            // Step 2: Process import
            $filename = $_SESSION['import_csv_file'] ?? '';
            if (!$filename || !file_exists($filename)) {
                flash('danger', 'File di importazione scaduto. Ricarica il CSV.');
                Response::redirect(url('dashboard/customers/import'));
                return;
            }

            $mapping = [
                'first_name'        => (int)($request->input('col_first_name', -1)),
                'last_name'         => (int)($request->input('col_last_name', -1)),
                'email'             => (int)($request->input('col_email', -1)),
                'phone'             => (int)($request->input('col_phone', -1)),
                'birthday'          => (int)($request->input('col_birthday', -1)),
                'last_visit'        => (int)($request->input('col_last_visit', -1)),
                'total_bookings'    => (int)($request->input('col_total_bookings', -1)),
                'tags'              => (int)($request->input('col_tags', -1)),
                'marketing_consent' => (int)($request->input('col_marketing_consent', -1)),
                'notes'             => (int)($request->input('col_notes', -1)),
            ];

            if ($mapping['email'] < 0 && $mapping['phone'] < 0) {
                flash('danger', 'Devi mappare almeno la colonna email o telefono.');
                Response::redirect(url('dashboard/customers/import'));
                return;
            }

            $delimiter = $this->detectDelimiter($filename);
            $handle = fopen($filename, 'r');
            fgetcsv($handle, 0, $delimiter); // skip header

            $customerModel = new Customer();
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = 0;

            $headerCount = count($headers ?? []);
            // Re-read headers if not available
            if ($headerCount === 0) {
                $hHandle = fopen($filename, 'r');
                $hdr = fgetcsv($hHandle, 0, $delimiter);
                $headerCount = $hdr ? count($hdr) : 0;
                fclose($hHandle);
            }

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $row = $this->fixWrappedRow($row, $headerCount, $delimiter);
                $firstName = $mapping['first_name'] >= 0 ? trim($row[$mapping['first_name']] ?? '') : '';
                $lastName  = $mapping['last_name'] >= 0 ? trim($row[$mapping['last_name']] ?? '') : '';
                $email     = $mapping['email'] >= 0 ? strtolower(trim($row[$mapping['email']] ?? '')) : '';
                $phone     = $mapping['phone'] >= 0 ? trim($row[$mapping['phone']] ?? '') : '';

                // Validate
                if (!$firstName && !$lastName) { $skipped++; continue; }
                if (!$email && !$phone) { $skipped++; continue; }
                if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors++; continue; }

                // Deduplica per email (se presente) o telefono
                $existing = null;
                if ($email) {
                    $existing = $customerModel->findByTenantAndEmail($tenantId, $email);
                }
                if (!$existing && $phone) {
                    $existing = $customerModel->findByTenantAndPhone($tenantId, $phone);
                }

                if ($existing) {
                    $updated++;
                    continue; // Non sovrascrivere dati esistenti
                }

                // Build data array with extended fields
                $data = [
                    'first_name' => $firstName ?: 'N/D',
                    'last_name'  => $lastName ?: 'N/D',
                    'email'      => $email ?: '',
                    'phone'      => $phone ?: '',
                    'source'     => 'import',
                ];

                // Birthday (dd/mm/yyyy → Y-m-d)
                if ($mapping['birthday'] >= 0) {
                    $bRaw = trim($row[$mapping['birthday']] ?? '');
                    if ($bRaw) {
                        $bDate = $this->parseDate($bRaw);
                        if ($bDate) $data['birthday'] = $bDate;
                    }
                }

                // Last visit (dd/mm/yyyy → Y-m-d)
                if ($mapping['last_visit'] >= 0) {
                    $lvRaw = trim($row[$mapping['last_visit']] ?? '');
                    if ($lvRaw) {
                        $lvDate = $this->parseDate($lvRaw);
                        if ($lvDate) $data['last_visit'] = $lvDate;
                    }
                }

                // Total bookings
                if ($mapping['total_bookings'] >= 0) {
                    $tb = (int)trim($row[$mapping['total_bookings']] ?? '0');
                    if ($tb > 0) $data['total_bookings'] = $tb;
                }

                // Tags (comma-separated string → JSON array)
                if ($mapping['tags'] >= 0) {
                    $tagsRaw = trim($row[$mapping['tags']] ?? '');
                    if ($tagsRaw) {
                        $tagsArr = array_filter(array_map('trim', explode(',', $tagsRaw)));
                        if (!empty($tagsArr)) $data['tags'] = array_values($tagsArr);
                    }
                }

                // Marketing consent: CSV "1" = subscribed → unsubscribed=0; CSV "0" → unsubscribed=1
                if ($mapping['marketing_consent'] >= 0) {
                    $consent = trim($row[$mapping['marketing_consent']] ?? '');
                    if ($consent !== '') {
                        $data['unsubscribed'] = in_array(strtolower($consent), ['1', 'true', 'si', 'sì', 'yes'], true) ? 0 : 1;
                    }
                }

                // Notes
                if ($mapping['notes'] >= 0) {
                    $notesRaw = trim($row[$mapping['notes']] ?? '');
                    if ($notesRaw) $data['notes'] = mb_substr($notesRaw, 0, 2000);
                }

                $customerModel->createImported($tenantId, $data);
                $created++;
            }

            fclose($handle);
            @unlink($filename);
            unset($_SESSION['import_csv_file'], $_SESSION['import_csv_mapping']);

            AuditLog::log(AuditLog::SETTINGS_UPDATED, "Import clienti: {$created} nuovi, {$updated} esistenti, {$skipped} saltati, {$errors} errori", Auth::id(), $tenantId);

            $msg = "{$created} clienti importati";
            if ($updated > 0) $msg .= ", {$updated} già esistenti";
            if ($skipped > 0) $msg .= ", {$skipped} righe incomplete";
            if ($errors > 0) $msg .= ", {$errors} errori";
            flash('success', $msg . '.');
            Response::redirect(url('dashboard/customers'));
            return;
        }

        Response::redirect(url('dashboard/customers/import'));
    }

    /**
     * Fix rows where the entire line is wrapped in a single quoted field
     * (common in Plateform CSV exports where Tags contain commas).
     */
    private function fixWrappedRow(array $row, int $expectedCols, string $delimiter): array
    {
        if (count($row) <= 2 && $expectedCols > 2 && !empty($row[0])) {
            $reparsed = str_getcsv($row[0], $delimiter);
            if (count($reparsed) >= $expectedCols - 1) {
                return $reparsed;
            }
        }
        return $row;
    }

    private function detectDelimiter(string $filepath): string
    {
        $line = fgets(fopen($filepath, 'r'));
        $semicolons = substr_count($line, ';');
        $commas = substr_count($line, ',');
        $tabs = substr_count($line, "\t");
        if ($semicolons > $commas && $semicolons > $tabs) return ';';
        if ($tabs > $commas) return "\t";
        return ',';
    }

    private function autoDetectMapping(array $headers): array
    {
        $mapping = [
            'first_name' => -1, 'last_name' => -1, 'email' => -1, 'phone' => -1,
            'birthday' => -1, 'last_visit' => -1, 'total_bookings' => -1,
            'tags' => -1, 'marketing_consent' => -1, 'notes' => -1,
        ];
        $patterns = [
            'first_name'        => ['nome', 'first_name', 'firstname', 'first name', 'name'],
            'last_name'         => ['cognome', 'last_name', 'lastname', 'last name', 'surname'],
            'email'             => ['email', 'e-mail', 'mail', 'email address'],
            'phone'             => ['telefono', 'phone', 'tel', 'cellulare', 'mobile', 'cell'],
            'birthday'          => ['nascita', 'birthday', 'birth', 'data nascita', 'date of birth', 'compleanno'],
            'last_visit'        => ['ultima presenza', 'last visit', 'ultima visita', 'last_visit'],
            'total_bookings'    => ['presenze', 'visite', 'bookings', 'visits', 'total_bookings'],
            'tags'              => ['tags', 'etichette', 'categorie', 'labels'],
            'marketing_consent' => ['consenso marketing', 'marketing', 'consent', 'newsletter'],
            'notes'             => ['note', 'notes', 'commenti', 'comments'],
        ];

        foreach ($headers as $i => $h) {
            $h = strtolower(trim($h));
            foreach ($patterns as $field => $pList) {
                if ($mapping[$field] !== -1) continue;
                foreach ($pList as $p) {
                    if (str_contains($h, $p)) { $mapping[$field] = $i; break; }
                }
            }
        }

        return $mapping;
    }

    private function parseDate(string $raw): ?string
    {
        // Try dd/mm/yyyy
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        // Try yyyy-mm-dd
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $raw)) {
            return $raw;
        }
        // Try dd-mm-yyyy
        if (preg_match('#^(\d{1,2})-(\d{1,2})-(\d{4})$#', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        return null;
    }

    private function readPreview(string $filepath, string $delimiter, int $max = 5): array
    {
        $handle = fopen($filepath, 'r');
        $headers = fgetcsv($handle, 0, $delimiter);
        $headerCount = $headers ? count($headers) : 0;
        $rows = [];
        $i = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false && $i < $max) {
            $row = $this->fixWrappedRow($row, $headerCount, $delimiter);
            $rows[] = $row;
            $i++;
        }
        fclose($handle);
        return $rows;
    }

    public function toggleBlock(Request $request): void
    {
        $id = (int)$request->param('id');
        $customerModel = new Customer();
        $customer = $customerModel->findById($id);

        if (!$customer || (int)$customer['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Cliente non trovato.');
            Response::redirect(url('dashboard/customers'));
        }

        if ($customer['is_blocked']) {
            $customerModel->unblock($id);
            flash('success', $customer['first_name'] . ' ' . $customer['last_name'] . ' è stato sbloccato.');
        } else {
            $customerModel->block($id);
            flash('warning', $customer['first_name'] . ' ' . $customer['last_name'] . ' è stato bloccato. Non potrà prenotare.');
        }

        AuditLog::log(AuditLog::CUSTOMER_BLOCKED, "Cliente ID: {$id}", Auth::id(), Auth::tenantId());

        Response::redirect(url("dashboard/customers/{$id}"));
    }

    public function resubscribe(Request $request): void
    {
        $id = (int)$request->param('id');
        $customerModel = new Customer();
        $customer = $customerModel->findById($id);

        if (!$customer || (int)$customer['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Cliente non trovato.');
            Response::redirect(url('dashboard/customers'));
        }

        if (!empty($customer['unsubscribed'])) {
            $db = \App\Core\Database::getInstance();
            $db->prepare('UPDATE customers SET unsubscribed = 0, unsubscribed_at = NULL WHERE id = :id')
               ->execute(['id' => $id]);
            flash('success', $customer['first_name'] . ' ' . $customer['last_name'] . ' è stato re-iscritto alle comunicazioni.');
        }

        Response::redirect(url("dashboard/customers/{$id}"));
    }

    public function updateBirthday(Request $request): void
    {
        $id = (int)$request->param('id');
        $customerModel = new Customer();
        $customer = $customerModel->findById($id);

        if (!$customer || (int)$customer['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Cliente non trovato.');
            Response::redirect(url('dashboard/customers'));
            return;
        }

        $birthday = $request->input('birthday', '');
        $birthday = $birthday ? $birthday : null;

        // Validate date format
        if ($birthday && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) {
            flash('danger', 'Data non valida.');
            Response::redirect(url("dashboard/customers/{$id}"));
            return;
        }

        $customerModel->updateBirthday($id, $birthday);
        flash('success', 'Data di nascita aggiornata.');
        Response::redirect(url("dashboard/customers/{$id}"));
    }

    public function addTag(Request $request): void
    {
        $id = (int)$request->param('id');
        $customerModel = new Customer();
        $customer = $customerModel->findById($id);

        if (!$customer || (int)$customer['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Cliente non trovato.');
            Response::redirect(url('dashboard/customers'));
            return;
        }

        $tag = trim(mb_substr($request->input('tag', ''), 0, 50));
        if ($tag) {
            $customerModel->addTag($id, $tag);
        }
        Response::redirect(url("dashboard/customers/{$id}"));
    }

    public function removeTag(Request $request): void
    {
        $id = (int)$request->param('id');
        $customerModel = new Customer();
        $customer = $customerModel->findById($id);

        if (!$customer || (int)$customer['tenant_id'] !== (int)Auth::tenantId()) {
            flash('danger', 'Cliente non trovato.');
            Response::redirect(url('dashboard/customers'));
            return;
        }

        $tag = $request->input('tag', '');
        if ($tag) {
            $customerModel->removeTag($id, $tag);
        }
        Response::redirect(url("dashboard/customers/{$id}"));
    }
}
