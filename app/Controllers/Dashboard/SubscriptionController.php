<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\AuditLog;

/**
 * Dashboard -> Abbonamento. Il piano del ristorante e i dati per la fattura.
 *
 * PERCHE' ESISTE, invece di tenere tutto nel Profilo: il Profilo dice "chi sei
 * tu" — nome, email, password. Ragione sociale e partita IVA dicono "chi e'
 * l'azienda che paga": sono due argomenti diversi, e tenerli sulla stessa
 * pagina aveva prodotto due pulsanti "Salva" affiancati. In tutte le altre
 * undici pagine della dashboard il pulsante di salvataggio e' UNO, quindi
 * chiunque abbia imparato quella regola premeva quello sbagliato — e infatti
 * e' successo alla prima prova.
 *
 * Qui ci si arriva dalla card "Il tuo piano" nel Profilo, che prima non aveva
 * nessun link. Non sta nella barra laterale di proposito: e' una pagina che si
 * apre due volte l'anno e toglierebbe peso a quelle che servono ogni sera.
 *
 * Accesso: fuori dalle ALLOWED_PREFIXES di StaffMiddleware, quindi lo staff e'
 * escluso dall'intera pagina (fail-safe). In piu' '/billing' e' fra i
 * BLOCKED_SUFFIXES, che copre anche il salvataggio.
 *
 * Qui atterreranno il checkout e lo storico delle fatture (lotti D e G del
 * piano): l'indirizzo e' gia' quello giusto.
 */
class SubscriptionController
{
    public function index(Request $request): void
    {
        $tenant = TenantResolver::current();
        $plan = null;
        $subscription = null;

        if ($tenant && $tenant['plan_id']) {
            $plan = (new Plan())->findById((int) $tenant['plan_id']);

            $stmt = Database::getInstance()->prepare(
                'SELECT * FROM subscriptions WHERE tenant_id = :tid ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute(['tid' => $tenant['id']]);
            $subscription = $stmt->fetch() ?: null;
        }

        view('dashboard/subscription/index', [
            'title'        => 'Abbonamento',
            'activeMenu'   => 'profile',
            'tenant'       => $tenant,
            'plan'         => $plan,
            'subscription' => $subscription,
        ], 'dashboard');
    }

    /**
     * Dati di fatturazione del ristorante.
     *
     * Stanno sul TENANT, non sull'utente: per questo non passano da
     * ProfileController, che scrive su `users` ed e' condiviso con l'area
     * admin (dove il tenant non esiste).
     */
    public function updateBilling(Request $request): void
    {
        $indietro = url('dashboard/abbonamento');
        $tenant = TenantResolver::current();

        if (!$tenant) {
            flash('danger', 'Nessun ristorante associato a questo account.');
            Response::redirect($indietro);
            return;
        }

        $d = $request->all();
        $v = fn(string $k) => trim((string) ($d[$k] ?? ''));

        $dati = [
            'billing_name'     => $v('billing_name'),
            'billing_vat'      => strtoupper(preg_replace('/\s+/', '', $v('billing_vat')) ?? ''),
            'billing_tax_code' => strtoupper(preg_replace('/\s+/', '', $v('billing_tax_code')) ?? ''),
            'billing_address'  => $v('billing_address'),
            'billing_city'     => $v('billing_city'),
            'billing_zip'      => preg_replace('/\s+/', '', $v('billing_zip')) ?? '',
            'billing_province' => strtoupper($v('billing_province')),
            'billing_sdi'      => strtoupper(preg_replace('/\s+/', '', $v('billing_sdi')) ?? ''),
            'billing_pec'      => strtolower($v('billing_pec')),
        ];

        // Tutto vuoto = svuotare la sezione. E' un'operazione legittima, non un
        // modulo compilato male: si salva e basta.
        $vuoto = count(array_filter($dati, fn($x) => $x !== '')) === 0;

        if (!$vuoto) {
            $errore = $this->validaFatturazione($dati);
            if ($errore !== null) {
                flash('danger', $errore);
                Response::redirect($indietro);
                return;
            }
        }

        // Le colonne sono NULL-abili: una stringa vuota in un campo fiscale non
        // significa "vuoto", significa "non lo so". Meglio NULL.
        $daSalvare = array_map(fn($x) => $x === '' ? null : $x, $dati);

        (new Tenant())->update((int) $tenant['id'], $daSalvare);

        AuditLog::log(
            'tenant_billing_updated',
            $vuoto ? 'Dati di fatturazione svuotati' : 'Dati di fatturazione aggiornati',
            Auth::id(),
            (int) $tenant['id']
        );

        flash('success', $vuoto ? 'Dati di fatturazione rimossi.' : 'Dati di fatturazione salvati.');
        Response::redirect($indietro);
    }

    /** @param array<string,string> $d  @return string|null messaggio d'errore */
    private function validaFatturazione(array $d): ?string
    {
        if ($d['billing_name'] === '') {
            return 'Manca la ragione sociale.';
        }
        if (!Tenant::partitaIvaValida($d['billing_vat'])) {
            return 'La partita IVA non è valida: devono essere 11 cifre e l\'ultima è di controllo.';
        }
        if ($d['billing_tax_code'] !== '' && !Tenant::codiceFiscaleValido($d['billing_tax_code'])) {
            return 'Il codice fiscale non è valido: 16 caratteri, oppure 11 cifre per le società.';
        }
        if ($d['billing_address'] === '' || $d['billing_city'] === '') {
            return 'Servono indirizzo e città della sede legale.';
        }
        if (!preg_match('/^\d{5}$/', $d['billing_zip'])) {
            return 'Il CAP deve essere di 5 cifre.';
        }
        if (!preg_match('/^[A-Z]{2}$/', $d['billing_province'])) {
            return 'La provincia deve essere la sigla di due lettere (es. NA).';
        }

        // SDI *oppure* PEC: e' la regola vera della fattura elettronica, ed e'
        // il motivo per cui nessuno dei due campi ha l'asterisco nel modulo.
        $haSdi = $d['billing_sdi'] !== '';
        $haPec = $d['billing_pec'] !== '';
        if (!$haSdi && !$haPec) {
            return 'Serve il codice SDI oppure la PEC: basta uno dei due.';
        }
        if ($haSdi && !preg_match('/^[A-Z0-9]{7}$/', $d['billing_sdi'])) {
            return 'Il codice SDI deve essere di 7 caratteri.';
        }
        if ($haPec && !filter_var($d['billing_pec'], FILTER_VALIDATE_EMAIL)) {
            return 'La PEC non è un indirizzo valido.';
        }

        return null;
    }
}
