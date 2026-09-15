<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\AppSetting;
use App\Services\AuditLog;

/**
 * Admin -> Impostazioni. Le configurazioni globali del prodotto, cioe' quelle
 * che non appartengono a un ristorante ma a Evulery.
 *
 * Oggi contiene quello che serve al checkout con bonifico: coordinate
 * bancarie, dicitura fiscale, marca da bollo e giorni di tolleranza.
 *
 * NOTA SUL MENU: la voce "Impostazioni" esisteva gia' nella barra laterale
 * dell'area admin, ma il link puntava a url('admin') — cioe' riportava alla
 * home. Questa pagina riempie quel vicolo cieco; nel layout e' cambiato solo
 * l'indirizzo del link.
 *
 * Perche' bollo, soglia e tolleranza stanno qui e non nel codice: sono valori
 * che si scoprono sbagliati usandoli. La tolleranza dipende da ogni quanto si
 * guarda l'home banking, il bollo da cosa dice il commercialista. Devono
 * potersi cambiare senza un deploy.
 */
class SettingsController
{
    /** Tolleranza: oltre i due mesi non e' piu' tolleranza, e' un regalo. */
    private const GRACE_MAX = 60;

    public function index(Request $request): void
    {
        $s = AppSetting::all();

        view('admin/settings/index', [
            'title'      => 'Impostazioni',
            'activeMenu' => 'settings',
            's'          => $s,
            // L'IBAN si salva compatto e si mostra a gruppi di quattro,
            // come si legge sugli estratti conto.
            'ibanVisibile' => $s['bank_iban'] !== ''
                ? AppSetting::ibanFormattato($s['bank_iban'])
                : '',
            'graceMax'   => self::GRACE_MAX,
        ], 'admin');
    }

    public function update(Request $request): void
    {
        $indietro = url('admin/settings');

        // --- IBAN: normalizzato (senza spazi, maiuscolo) e verificato mod-97.
        $iban = strtoupper(preg_replace('/\s+/', '', (string) $request->input('bank_iban', '')) ?? '');
        if ($iban !== '' && !AppSetting::ibanValido($iban)) {
            flash('danger', 'L\'IBAN non è valido: ricontrolla, potrebbe esserci una cifra sbagliata.');
            Response::redirect($indietro);
            return;
        }

        // --- Email mittente: se c'e', dev'essere un indirizzo vero.
        $emailFrom = trim((string) $request->input('billing_email_from', ''));
        if ($emailFrom !== '' && !filter_var($emailFrom, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'L\'email mittente non è un indirizzo valido.');
            Response::redirect($indietro);
            return;
        }

        // --- Numeri. La virgola decimale viene accettata: e' quello che
        //     scrive chiunque digiti "2,00" senza pensarci.
        $bollo  = $this->decimale($request->input('stamp_duty_amount', '0'));
        $soglia = $this->decimale($request->input('stamp_duty_threshold', '0'));

        // --- Tolleranza: limitata, perche' un 100 battuto per sbaglio
        //     regalerebbe tre mesi di servizio a chi non ha pagato.
        $grace = max(0, min(self::GRACE_MAX, (int) $request->input('grace_days', 10)));

        $nuovi = [
            'bank_holder'          => trim((string) $request->input('bank_holder', '')),
            'bank_iban'            => $iban,
            'bank_name'            => trim((string) $request->input('bank_name', '')),
            'bank_bic'             => strtoupper(trim((string) $request->input('bank_bic', ''))),
            'tax_notice'           => trim((string) $request->input('tax_notice', '')),
            'stamp_duty_amount'    => number_format($bollo, 2, '.', ''),
            'stamp_duty_threshold' => number_format($soglia, 2, '.', ''),
            'grace_days'           => (string) $grace,
            'billing_email_from'   => $emailFrom,
        ];

        // Cosa e' cambiato davvero: serve all'audit, che altrimenti direbbe
        // solo "ha salvato" anche quando non ha toccato niente.
        $prima = AppSetting::all();
        $cambiate = [];
        foreach ($nuovi as $k => $v) {
            if (($prima[$k] ?? '') !== $v) {
                $cambiate[] = $k;
            }
        }

        AppSetting::setMany($nuovi);

        if ($cambiate) {
            AuditLog::log(
                'app_settings_updated',
                'Impostazioni aggiornate: ' . implode(', ', $cambiate),
                Auth::id()
            );
        }

        flash('success', $cambiate ? 'Impostazioni salvate.' : 'Nessuna modifica da salvare.');
        Response::redirect($indietro);
    }

    /** Accetta sia "2.00" sia "2,00", e non si offende per gli spazi. */
    private function decimale(mixed $valore): float
    {
        $v = str_replace([' ', ','], ['', '.'], (string) $valore);
        return max(0, (float) $v);
    }
}
