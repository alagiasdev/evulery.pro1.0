-- Add manage_token for magic link reservation management
ALTER TABLE reservations ADD COLUMN manage_token VARCHAR(64) DEFAULT NULL AFTER source;
ALTER TABLE reservations ADD UNIQUE INDEX idx_manage_token (manage_token);