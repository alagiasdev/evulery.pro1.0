<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Customer;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Reservation;
use App\Models\Table;
use App\Models\TimeSlot;

/**
 * Popolatore di DATI DEMO per un tenant vetrina (es. trattoria-genovese).
 *
 * SICUREZZA:
 *  - Opera SOLO sul tenant col slug indicato; se non esiste, si ferma.
 *  - I clienti demo sono marcati dall'email @demo.evulery.local + tag "demo":
 *    il refresh cancella SOLO quelli (+ le loro prenotazioni), mai dati reali.
 *
 * MODELLO:
 *  - Setup (una-tantum, idempotente): meal categories, slot, tavoli, menu.
 *    Creati solo se mancanti -> ri-eseguibile senza duplicare.
 *  - Dati rolling (refresh ad ogni run): 30 clienti + ~100 prenotazioni con
 *    date RELATIVE a oggi (passato/oggi/futuro) -> demo sempre "attuale".
 *
 * Usato da scripts/seed-demo.php (CLI) e dal bottone super-admin.
 */
class DemoSeeder
{
    private const DEMO_DOMAIN = 'demo.evulery.local';

    private \PDO $db;
    /** @var int[] tavoli gia' assegnati a prenotazioni di oggi (evita doppio uso) */
    private array $usedTablesToday = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * @param string $slug     slug del tenant demo (es. "trattoria-genovese")
     * @param bool   $cleanOnly se true: ripulisce soltanto, senza rigenerare
     * @return array riepilogo operazioni
     */
    public function run(string $slug, bool $cleanOnly = false): array
    {
        $tenant = $this->findTenant($slug);
        if (!$tenant) {
            throw new \RuntimeException("Tenant '{$slug}' non trovato: seeding annullato.");
        }
        // Guardia primaria: SOLO tenant marcati is_demo=1 nel DB. Un tenant reale
        // (is_demo=0, il default) e' RIFIUTATO da qualunque entry point.
        if ((int) $tenant['is_demo'] !== 1) {
            throw new \RuntimeException("Tenant '{$slug}' non e' marcato come demo (is_demo=0): seeding RIFIUTATO (protezione tenant reali).");
        }
        $tid = (int) $tenant['id'];
        $out = ['tenant' => $tenant['name'], 'slug' => $slug, 'tenant_id' => $tid];

        // Refresh: rimuove sempre il set demo precedente (rolling).
        $out['rimossi'] = $this->cleanDemo($tid);
        if ($cleanOnly) {
            return $out;
        }

        // Setup non temporale (idempotente).
        $out['setup'] = $this->ensureSetup($tid);

        // Dati rolling relativi a oggi.
        $customers = $this->seedCustomers($tid);
        $out['clienti'] = count($customers);
        $out['prenotazioni'] = $this->seedReservations($tid, $customers);
        $this->refreshCustomerStats($tid);

        return $out;
    }

    // ---------------------------------------------------------------- tenant

    private function findTenant(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, slug, is_demo FROM tenants WHERE slug = :s LIMIT 1');
        $stmt->execute(['s' => $slug]);
        return $stmt->fetch() ?: null;
    }

    // ----------------------------------------------------------------- clean

    private function cleanDemo(int $tid): array
    {
        // Cancella SOLO i clienti demo (is_demo=1). I clienti reali (is_demo=0)
        // non vengono MAI toccati: protezione assoluta anche se il tenant fosse
        // marcato demo per errore.
        $stmt = $this->db->prepare('SELECT id FROM customers WHERE tenant_id = :t AND is_demo = 1');
        $stmt->execute(['t' => $tid]);
        $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));

        $res = 0;
        if ($ids) {
            $in = implode(',', $ids);
            $this->db->exec(
                "DELETE rt FROM reservation_tables rt
                 JOIN reservations r ON rt.reservation_id = r.id
                 WHERE r.tenant_id = {$tid} AND r.customer_id IN ({$in})"
            );
            $res = (int) $this->db->exec(
                "DELETE FROM reservations WHERE tenant_id = {$tid} AND customer_id IN ({$in})"
            );
            $this->db->exec("DELETE FROM customers WHERE tenant_id = {$tid} AND id IN ({$in})");
        }
        return ['clienti' => count($ids), 'prenotazioni' => $res];
    }

    // ----------------------------------------------------------------- setup

    private function ensureSetup(int $tid): array
    {
        $done = [];

        // 1) Fasce pasto
        if ((int) $this->db->query("SELECT COUNT(*) FROM meal_categories WHERE tenant_id = {$tid}")->fetchColumn() === 0) {
            $ins = $this->db->prepare(
                'INSERT INTO meal_categories (tenant_id, name, display_name, start_time, end_time, sort_order, is_active)
                 VALUES (:t, :n, :d, :s, :e, :o, 1)'
            );
            $ins->execute(['t' => $tid, 'n' => 'pranzo', 'd' => 'Pranzo', 's' => '12:00:00', 'e' => '15:00:00', 'o' => 1]);
            $ins->execute(['t' => $tid, 'n' => 'cena', 'd' => 'Cena', 's' => '19:00:00', 'e' => '23:30:00', 'o' => 2]);
            $done[] = 'fasce pasto (Pranzo, Cena)';
        }

        // 2) Slot orari (0=Lun .. 6=Dom, convenzione HomeController date('N')-1)
        if ((int) $this->db->query("SELECT COUNT(*) FROM time_slots WHERE tenant_id = {$tid}")->fetchColumn() === 0) {
            $ts = new TimeSlot();
            $lunch = ['12:00:00', '12:30:00', '13:00:00', '13:30:00', '14:00:00'];
            $dinner = ['19:00:00', '19:30:00', '20:00:00', '20:30:00', '21:00:00', '21:30:00', '22:00:00'];
            for ($dow = 0; $dow <= 6; $dow++) {
                foreach ($lunch as $t) {
                    $ts->upsert($tid, $dow, $t, 40, true);
                }
                foreach ($dinner as $t) {
                    $ts->upsert($tid, $dow, $t, 60, true);
                }
            }
            $done[] = 'slot orari (7gg x pranzo/cena)';
        }

        // 3) Tavoli / Sala
        if ((int) $this->db->query("SELECT COUNT(*) FROM restaurant_tables WHERE tenant_id = {$tid}")->fetchColumn() === 0) {
            $tbl = new Table();
            $defs = [
                ['Tavolo 1', 2, 1, 'Sala', 'round'],
                ['Tavolo 2', 2, 1, 'Sala', 'round'],
                ['Tavolo 3', 4, 2, 'Sala', 'square'],
                ['Tavolo 4', 4, 2, 'Sala', 'square'],
                ['Tavolo 5', 4, 2, 'Sala', 'square'],
                ['Tavolo 6', 6, 3, 'Sala', 'square'],
                ['Tavolo 7', 6, 3, 'Sala', 'square'],
                ['Tavolo 8', 8, 4, 'Sala', 'square'],
                ['Dehors 1', 4, 2, 'Dehors', 'round'],
                ['Dehors 2', 6, 3, 'Dehors', 'square'],
            ];
            foreach ($defs as [$name, $cap, $min, $area, $shape]) {
                $tbl->create($tid, [
                    'name' => $name, 'capacity' => $cap, 'min_capacity' => $min,
                    'area' => $area, 'shape' => $shape, 'internal_note' => '',
                    'is_active' => 1, 'is_bookable_online' => 1, 'is_blocked' => 0,
                ]);
            }
            $done[] = 'tavoli (10)';
        }

        // 4) Menu ligure
        if ((int) $this->db->query("SELECT COUNT(*) FROM menu_categories WHERE tenant_id = {$tid}")->fetchColumn() === 0) {
            $this->seedLigurianMenu($tid);
            $done[] = 'menu ligure';
        }

        return $done ?: ['(setup gia\' presente)'];
    }

    private function seedLigurianMenu(int $tid): void
    {
        $mc = new MenuCategory();
        $mi = new MenuItem();

        $antipasti = $mc->create($tid, ['name' => 'Antipasti', 'icon' => 'bi-egg-fried', 'sort_order' => 1]);
        $primi     = $mc->create($tid, ['name' => 'Primi', 'icon' => 'bi-cup-hot', 'sort_order' => 2]);
        $secondi   = $mc->create($tid, ['name' => 'Secondi', 'icon' => 'bi-egg', 'sort_order' => 3]);
        $dolci     = $mc->create($tid, ['name' => 'Dolci', 'icon' => 'bi-cake2', 'sort_order' => 4]);
        $vini      = $mc->create($tid, ['name' => 'Vini', 'icon' => 'bi-cup', 'is_wine' => 1, 'sort_order' => 5]);

        $items = [
            [$antipasti, 'Focaccia di Recco', 'Col formaggio, cotta al momento', 7.5, null],
            [$antipasti, 'Acciughe ripiene fritte', 'Alla ligure', 9.0, null],
            [$antipasti, 'Torta pasqualina', 'Bietole, ricotta e uova', 8.0, null],
            [$antipasti, 'Farinata di ceci', 'Cotta nel forno a legna', 5.0, null],
            [$primi, 'Trofie al pesto', 'Pesto genovese DOP, patate e fagiolini', 12.0, null],
            [$primi, 'Pansoti al preboggion', 'Con salsa di noci', 13.0, null],
            [$primi, 'Trenette avvantaggiate', 'Al pesto, con farina integrale', 12.0, null],
            [$primi, 'Minestrone alla genovese', 'Verdure di stagione e pesto', 10.0, null],
            [$secondi, 'Coniglio alla ligure', 'Con olive taggiasche e pinoli', 16.0, null],
            [$secondi, 'Stoccafisso accomodato', 'Con patate e olive', 17.0, null],
            [$secondi, 'Cima alla genovese', 'Ripiena, servita fredda', 15.0, null],
            [$secondi, 'Totani ripieni', 'In umido', 16.0, null],
            [$dolci, 'Pandolce genovese', 'Basso, con canditi e pinoli', 6.0, null],
            [$dolci, 'Panera', 'Semifreddo al caffe', 6.0, null],
            [$dolci, 'Sacripantina', 'Pan di Spagna, crema e liquore', 6.5, null],
            [$vini, 'Vermentino Riviera Ligure DOC', 'Calice / bottiglia', 5.0, 22.0],
            [$vini, 'Pigato Riviera Ligure DOC', 'Calice / bottiglia', 5.0, 24.0],
            [$vini, 'Rossese di Dolceacqua DOC', 'Calice / bottiglia', 6.0, 26.0],
            [$vini, 'Ormeasco di Pornassio DOC', 'Calice / bottiglia', 5.5, 23.0],
        ];
        $sort = [];
        foreach ($items as [$cat, $name, $desc, $price, $bottle]) {
            $sort[$cat] = ($sort[$cat] ?? 0) + 1;
            $mi->create($tid, [
                'category_id' => $cat, 'name' => $name, 'description' => $desc,
                'price' => $price, 'price_bottle' => $bottle,
                'is_available' => 1, 'sort_order' => $sort[$cat],
            ]);
        }
    }

    // -------------------------------------------------------------- clienti

    private function seedCustomers(int $tid): array
    {
        $cm = new Customer();
        $firsts = ['Marco', 'Giulia', 'Luca', 'Francesca', 'Andrea', 'Chiara', 'Matteo', 'Sara', 'Davide', 'Elena', 'Simone', 'Martina', 'Alessandro', 'Valentina', 'Federico', 'Alice', 'Lorenzo', 'Giorgia', 'Riccardo', 'Beatrice', 'Stefano', 'Ilaria', 'Paolo', 'Serena', 'Nicola', 'Laura', 'Roberto', 'Silvia', 'Antonio', 'Cristina'];
        $lasts  = ['Rossi', 'Bianchi', 'Ferrari', 'Esposito', 'Russo', 'Romano', 'Colombo', 'Ricci', 'Marino', 'Greco', 'Bruno', 'Gallo', 'Conti', 'De Luca', 'Costa', 'Giordano', 'Mancini', 'Rizzo', 'Lombardi', 'Moretti', 'Barbieri', 'Fontana', 'Santoro', 'Mariani', 'Rinaldi', 'Caruso', 'Ferrara', 'Galli', 'Martini', 'Leone'];
        $extraTags = ['Cena', 'Pranzo', 'Tavolo vista', 'Celiaco', 'Vegetariano', 'Cliente storico', 'Allergie', 'Compleanni'];

        $segments = array_merge(
            array_fill(0, 5, 'vip'),
            array_fill(0, 8, 'abituale'),
            array_fill(0, 10, 'occasionale'),
            array_fill(0, 7, 'nuovo')
        );
        shuffle($segments);

        $customers = [];
        for ($i = 0; $i < 30; $i++) {
            $first = $firsts[$i];
            $last  = $lasts[$i];
            $seg   = $segments[$i];
            $email = $this->slugify($first) . '.' . $this->slugify($last) . ($i + 1) . '@' . self::DEMO_DOMAIN;

            $tags = ['demo'];
            if (rand(0, 100) < 45) {
                $tags[] = $extraTags[array_rand($extraTags)];
            }

            $id = $cm->createImported($tid, [
                'first_name' => $first,
                'last_name'  => $last,
                'email'      => $email,
                'phone'      => '3' . rand(20, 49) . rand(1000000, 9999999),
                'birthday'   => sprintf('%04d-%02d-%02d', rand(1962, 2001), rand(1, 12), rand(1, 28)),
                'tags'       => $tags,
                'source'     => 'booking',
                'is_demo'    => 1,
                'marketing_consent' => rand(0, 100) < 70 ? 1 : 0,
            ]);
            $customers[] = ['id' => $id, 'seg' => $seg];
        }
        return $customers;
    }

    // --------------------------------------------------------- prenotazioni

    private function seedReservations(int $tid, array $customers): int
    {
        $rm = new Reservation();
        $perSeg = ['vip' => [6, 9], 'abituale' => [3, 5], 'occasionale' => [1, 3], 'nuovo' => [1, 1]];
        $count = 0;

        foreach ($customers as $c) {
            [$lo, $hi] = $perSeg[$c['seg']];
            $n = rand($lo, $hi);
            for ($k = 0; $k < $n; $k++) {
                [$date, $time, $status, $dur] = $this->randomSlot();
                $party = $this->randomParty($c['seg']);
                $rid = $rm->create([
                    'tenant_id'        => $tid,
                    'customer_id'      => $c['id'],
                    'reservation_date' => $date,
                    'reservation_time' => $time,
                    'party_size'       => $party,
                    'status'           => $status,
                    'source'           => $this->randomSource(),
                    'duration_minutes' => $dur,
                ]);
                $count++;
                if ($date === date('Y-m-d') && in_array($status, ['confirmed', 'arrived'], true)) {
                    $this->assignTable($tid, $rid, $party);
                }
            }
        }
        return $count;
    }

    /** @return array{0:string,1:string,2:string,3:int} [date, time, status, duration] */
    private function randomSlot(): array
    {
        $r = rand(1, 100);
        if ($r <= 60) {
            $date = date('Y-m-d', strtotime('-' . rand(1, 70) . ' days'));
            $s = rand(1, 100);
            $status = $s <= 70 ? 'arrived' : ($s <= 82 ? 'confirmed' : ($s <= 92 ? 'cancelled' : 'noshow'));
        } elseif ($r <= 70) {
            $date = date('Y-m-d');
            $s = rand(1, 100);
            $status = $s <= 55 ? 'confirmed' : ($s <= 80 ? 'pending' : 'arrived');
        } else {
            $date = date('Y-m-d', strtotime('+' . rand(1, 21) . ' days'));
            $status = rand(1, 100) <= 70 ? 'confirmed' : 'pending';
        }

        if (rand(0, 100) <= 35) {
            $time = ['12:00:00', '12:30:00', '13:00:00', '13:30:00', '14:00:00'][rand(0, 4)];
            $dur = 90;
        } else {
            $time = ['19:00:00', '19:30:00', '20:00:00', '20:30:00', '21:00:00', '21:30:00', '22:00:00'][rand(0, 6)];
            $dur = 120;
        }
        return [$date, $time, $status, $dur];
    }

    private function randomParty(string $seg): int
    {
        $w = ['vip' => [2, 2, 4, 4, 6, 8], 'abituale' => [2, 2, 3, 4, 4, 6]];
        $pool = $w[$seg] ?? [2, 2, 2, 3, 4, 4];
        return $pool[array_rand($pool)];
    }

    private function randomSource(): string
    {
        $w = ['widget', 'widget', 'widget', 'widget', 'phone', 'phone', 'walkin', 'dashboard'];
        return $w[array_rand($w)];
    }

    private function assignTable(int $tid, int $rid, int $party): void
    {
        $stmt = $this->db->prepare(
            'SELECT id, capacity, min_capacity FROM restaurant_tables
             WHERE tenant_id = :t AND is_active = 1 ORDER BY capacity ASC'
        );
        $stmt->execute(['t' => $tid]);
        foreach ($stmt->fetchAll() as $tb) {
            $id = (int) $tb['id'];
            if (in_array($id, $this->usedTablesToday, true)) {
                continue;
            }
            if ((int) $tb['capacity'] >= $party && (int) $tb['min_capacity'] <= $party) {
                $this->db->prepare('INSERT INTO reservation_tables (reservation_id, table_id, is_auto) VALUES (:r, :tab, 1)')
                    ->execute(['r' => $rid, 'tab' => $id]);
                $this->usedTablesToday[] = $id;
                return;
            }
        }
    }

    // Aggiorna total_bookings + last_visit dei clienti demo dai dati reali.
    private function refreshCustomerStats(int $tid): void
    {
        $u1 = $this->db->prepare(
            "UPDATE customers c SET c.total_bookings = (
                SELECT COUNT(*) FROM reservations r
                WHERE r.customer_id = c.id AND r.tenant_id = :t1 AND r.status NOT IN ('cancelled','noshow')
             ) WHERE c.tenant_id = :t2 AND c.is_demo = 1"
        );
        $u1->execute(['t1' => $tid, 't2' => $tid]);

        $u2 = $this->db->prepare(
            "UPDATE customers c SET c.last_visit = (
                SELECT MAX(r.reservation_date) FROM reservations r
                WHERE r.customer_id = c.id AND r.tenant_id = :t1
                  AND r.status IN ('arrived','confirmed') AND r.reservation_date <= CURDATE()
             ) WHERE c.tenant_id = :t2 AND c.is_demo = 1"
        );
        $u2->execute(['t1' => $tid, 't2' => $tid]);
    }

    private function slugify(string $s): string
    {
        $s = strtolower(trim($s));
        $s = strtr($s, ['à' => 'a', 'è' => 'e', 'é' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', ' ' => '-', "'" => '']);
        return preg_replace('/[^a-z0-9-]/', '', $s);
    }
}
