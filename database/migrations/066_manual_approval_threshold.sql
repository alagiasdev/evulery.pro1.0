-- Migration 066: soglia coperti per approvazione manuale obbligatoria
-- Anche con accettazione automatica attiva (confirmation_mode = 'auto'), le
-- prenotazioni dal widget pubblico con party_size >= soglia restano 'pending',
-- in attesa di conferma manuale del ristoratore (es. gruppi numerosi).
-- NULL = nessuna soglia → comportamento attuale invariato.
-- Stesso pattern di tenants.deposit_min_party_size.
ALTER TABLE tenants
    ADD COLUMN manual_approval_min_party_size TINYINT UNSIGNED DEFAULT NULL AFTER confirmation_mode;
