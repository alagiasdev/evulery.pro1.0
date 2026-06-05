-- ====================================================================
-- Gestione Rider — anagrafica fattorini + assegnazione ordini delivery
--
-- MVP: nessun login dedicato per rider (continuano a usare la board
-- pubblica condivisa con PIN del ristoratore). Qui aggiungiamo solo
-- l'anagrafica e il link ordine->rider per attribuzione e statistiche.
-- ====================================================================

CREATE TABLE IF NOT EXISTS riders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    -- Palette fissa di 8 colori scelta dal ristoratore per il badge
    -- (no color picker libero per evitare sfumature ambigue tra rider)
    color_hex VARCHAR(7) NOT NULL DEFAULT '#6c757d',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_riders_tenant (tenant_id, is_active),
    CONSTRAINT fk_riders_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aggiunta a orders: link al rider e timestamp dell'assegnazione.
-- rider_assigned_at e' il punto da cui parte il calcolo "tempo medio
-- consegna" nelle statistiche. ON DELETE SET NULL: se elimino un rider
-- (caso raro, prefibile disattivarlo), gli ordini storici restano
-- conservati senza link.
ALTER TABLE orders
    ADD COLUMN rider_id INT UNSIGNED DEFAULT NULL AFTER status,
    ADD COLUMN rider_assigned_at DATETIME DEFAULT NULL AFTER rider_id,
    ADD INDEX idx_orders_rider (rider_id),
    ADD CONSTRAINT fk_orders_rider FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE SET NULL;
