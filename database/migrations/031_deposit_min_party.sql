-- Caparra condizionale: attiva solo da N persone in su
-- NULL = sempre attiva (comportamento attuale), valore > 0 = soglia minima
ALTER TABLE tenants
    ADD COLUMN deposit_min_party_size TINYINT UNSIGNED DEFAULT NULL AFTER deposit_mode;
