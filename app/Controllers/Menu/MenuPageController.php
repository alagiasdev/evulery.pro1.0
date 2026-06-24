<?php

namespace App\Controllers\Menu;

use App\Core\Request;
use App\Core\Response;
use App\Core\TenantResolver;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuTranslation;
use App\Models\Tenant;

class MenuPageController
{
    public function show(Request $request): void
    {
        $slug = $request->param('slug');
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findBySlug($slug);

        if (!$tenant || !$tenant['is_active']) {
            Response::notFound();
        }

        if (!$tenant['menu_enabled'] || !$tenantModel->canUseService((int)$tenant['id'], 'digital_menu')) {
            // Show friendly "menu unavailable" page with restaurant contacts
            $bookingEnabled = $tenantModel->canUseService((int)$tenant['id'], 'booking_widget');
            view('menu/unavailable', [
                'tenantName'    => $tenant['name'],
                'tenantLogo'    => $tenant['logo_url'] ?? null,
                'tenantPhone'   => $tenant['phone'] ?? '',
                'tenantEmail'   => $tenant['email'] ?? '',
                'tenantAddress' => $tenant['address'] ?? '',
                'bookingUrl'    => $bookingEnabled ? url($slug) : '',
            ]);
            return;
        }

        // Check subscription expiry — show suspended page
        $expiredSub = $tenantModel->getExpiredSubscription((int)$tenant['id']);
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

        TenantResolver::setCurrent($tenant);

        $itemModel = new MenuItem();
        $catModel  = new MenuCategory();

        $categories  = $itemModel->findAvailableGrouped($tenant['id']);
        $specials    = $itemModel->findDailySpecials($tenant['id']);
        $activeCats  = $catModel->findActiveByTenant($tenant['id']);
        $itemCounts  = $catModel->getItemCounts($tenant['id']);

        // ---- Multilingua (gated) ----
        $multilangOn = $tenantModel->canUseService((int)$tenant['id'], 'menu_multilang');
        $langs = $multilangOn ? MenuTranslation::parseLanguages($tenant['menu_languages'] ?? 'it') : ['it'];
        $lang = $this->resolveLang($request, $langs);

        $tenantTr = [];
        if ($lang !== 'it') {
            $tr       = new MenuTranslation();
            $catTr    = $tr->bulk((int)$tenant['id'], 'category', $lang);
            $itemTr   = $tr->bulk((int)$tenant['id'], 'item', $lang);
            $tenantTr = $tr->forEntity((int)$tenant['id'], 'tenant', (int)$tenant['id'], $lang);

            // In lingua diversa dall'italiano NASCONDIAMO le voci non tradotte:
            // un piatto senza NOME tradotto non compare; descrizione mancante = omessa
            // (mai testo IT in un menu EN). Categorie/sottocategorie vuote spariscono.
            $applyItem = function (array $it) use ($itemTr) {
                $t = $itemTr[(int)$it['id']] ?? [];
                if (empty($t['name'])) {
                    return null; // niente nome tradotto → nascosto
                }
                $it['name'] = $t['name'];
                $it['description'] = !empty($t['description']) ? $t['description'] : '';
                return $it;
            };

            $outCats = [];
            foreach ($categories as $cat) {
                $cName = $catTr[(int)$cat['id']]['name'] ?? '';
                if ($cName === '') {
                    continue; // categoria senza nome tradotto → nascosta (con i suoi piatti)
                }
                $cat['name'] = $cName;
                $cat['description'] = $catTr[(int)$cat['id']]['description'] ?? '';

                $items = [];
                foreach ($cat['items'] as $it) {
                    $a = $applyItem($it);
                    if ($a !== null) { $items[] = $a; }
                }
                $cat['items'] = $items;

                $subs = [];
                foreach ($cat['subcategories'] as $sub) {
                    $sName = $catTr[(int)$sub['id']]['name'] ?? '';
                    if ($sName === '') { continue; }
                    $sub['name'] = $sName;
                    $sItems = [];
                    foreach ($sub['items'] as $it) {
                        $a = $applyItem($it);
                        if ($a !== null) { $sItems[] = $a; }
                    }
                    if (empty($sItems)) { continue; } // sottocategoria vuota → nascosta
                    $sub['items'] = $sItems;
                    $subs[] = $sub;
                }
                $cat['subcategories'] = $subs;

                if (empty($cat['items']) && empty($cat['subcategories'])) {
                    continue; // categoria rimasta vuota → nascosta
                }
                $outCats[] = $cat;
            }
            $categories = $outCats;

            $outSpecials = [];
            foreach ($specials as $sp) {
                $a = $applyItem($sp);
                if ($a !== null) { $outSpecials[] = $a; }
            }
            $specials = $outSpecials;
        }

        // Etichetta sezione "in evidenza": traduzione → base tenant → default per lingua
        $featuredBase = trim((string)($tenant['menu_featured_label'] ?? ''));
        if ($lang !== 'it' && !empty($tenantTr['featured_label'])) {
            $featuredLabel = $tenantTr['featured_label'];
        } elseif ($featuredBase !== '') {
            $featuredLabel = $featuredBase;
        } else {
            $featuredLabel = $lang === 'en' ? 'Daily specials' : 'Piatti del giorno';
        }

        // Tagline: in lingua diversa mostro SOLO la versione tradotta (niente IT in EN)
        if ($lang === 'it') {
            $tagline = $tenant['menu_tagline'] ?? null;
        } else {
            $tagline = !empty($tenantTr['tagline']) ? $tenantTr['tagline'] : null;
        }

        view('menu/public', [
            'title'          => 'Menu - ' . $tenant['name'],
            'tenant'         => $tenant,
            'tenantName'     => $tenant['name'],
            'tenantLogo'     => $tenant['logo_url'] ?? null,
            'slug'           => $slug,
            'categories'     => $categories,
            'specials'       => $specials,
            'activeCats'     => $activeCats,
            'itemCounts'     => $itemCounts,
            'allergens'      => MenuItem::allergenLabels($lang),
            'allergenIcons'  => MenuItem::ALLERGEN_ICONS,
            'allergenColors' => MenuItem::ALLERGEN_COLORS,
            'heroImage'      => $tenant['menu_hero_image'] ?? null,
            'featuredLabel'  => $featuredLabel,
            'tagline'        => $tagline,
            'openingHours'   => $tenant['opening_hours'] ?? null,
            'phone'          => $tenant['phone'] ?? null,
            'address'        => $tenant['address'] ?? null,
            'lang'           => $lang,
            'menuLanguages'  => $langs,
            'langMeta'       => MenuTranslation::LANGUAGES,
            'ui'             => $this->uiStrings($lang),
        ]);
    }

    /** Lingua richiesta: ?lang=xx valido → Accept-Language → 'it'. */
    private function resolveLang(Request $request, array $langs): string
    {
        $q = $request->input('lang');
        if ($q && in_array($q, $langs, true)) {
            return $q;
        }
        $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        foreach (explode(',', $accept) as $part) {
            $code = strtolower(substr(trim($part), 0, 2));
            if (in_array($code, $langs, true)) {
                return $code;
            }
        }
        return 'it';
    }

    /** Stringhe fisse dell'interfaccia pubblica per lingua (fallback IT). */
    private function uiStrings(string $lang): array
    {
        $strings = [
            'it' => [
                'menu_eyebrow' => 'Menù', 'cta_eyebrow' => 'Prenotazioni',
                'browse' => 'Sfoglia il menu', 'search' => 'Cerca nel menu...',
                'dish_1' => 'piatto', 'dish_n' => 'piatti', 'wine_1' => 'etichetta', 'wine_n' => 'etichette',
                'glass' => 'Calice', 'bottle' => 'Bottiglia',
                'wine_note' => 'Tutti i vini contengono solfiti. Carta soggetta a variazioni di annata e disponibilità.',
                'allergen_title' => 'Informazioni sugli Allergeni',
                'allergen_legal' => 'Ai sensi del Reg. UE 1169/2011. Per ulteriori informazioni rivolgiti al personale di sala.',
                'cta_title' => 'Ti abbiamo fatto venire fame?', 'cta_sub' => 'Prenota per la prossima cena o il prossimo pranzo',
                'cta_btn' => 'Prenota un tavolo', 'empty' => 'Il menu non è ancora disponibile.',
            ],
            'en' => [
                'menu_eyebrow' => 'Menu', 'cta_eyebrow' => 'Reservations',
                'browse' => 'Browse the menu', 'search' => 'Search the menu...',
                'dish_1' => 'dish', 'dish_n' => 'dishes', 'wine_1' => 'wine', 'wine_n' => 'wines',
                'glass' => 'Glass', 'bottle' => 'Bottle',
                'wine_note' => 'All wines contain sulphites. List subject to vintage and availability changes.',
                'allergen_title' => 'Allergen Information',
                'allergen_legal' => 'Pursuant to EU Reg. 1169/2011. For more information please ask our staff.',
                'cta_title' => 'Feeling hungry?', 'cta_sub' => 'Book your next lunch or dinner',
                'cta_btn' => 'Book a table', 'empty' => 'The menu is not available yet.',
            ],
        ];
        return $strings[$lang] ?? $strings['it'];
    }
}
