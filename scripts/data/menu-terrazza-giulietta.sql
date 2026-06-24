-- Import menù per tenant 'terrazza-giulietta' — generato da scripts/import_menu.php
-- ATTENZIONE: eseguire UNA SOLA VOLTA (il menù del tenant deve essere vuoto).
-- Se @tid risulta NULL lo slug è errato: gli INSERT falliranno (nessun dato sporco).

SET NAMES utf8mb4;
SET @tid := (SELECT id FROM tenants WHERE slug = 'terrazza-giulietta' LIMIT 1);

INSERT INTO menu_categories (tenant_id, parent_id, name, description, icon, is_wine, sort_order, is_active)
VALUES (@tid, NULL, 'Per iniziare', '', 'bi-egg-fried', 0, 0, 1);
SET @c := LAST_INSERT_ID();
INSERT INTO menu_items (tenant_id, category_id, name, description, price, allergens, is_available, is_daily_special, sort_order) VALUES
(@tid, @c, 'Selezione con fiore farcito, baccalà mantecato, insalata carciofi, burratina pomodorini confit', '', 24.00, '["milk","nuts"]', 1, 0, 0),
(@tid, @c, 'Totano su vellutata di patate, zenzero e rosmarino', '', 18.00, '["milk","nuts"]', 1, 0, 1),
(@tid, @c, 'Insalata di carciofi con grana e olio al basilico', '', 17.00, '["milk"]', 1, 0, 2),
(@tid, @c, 'Baccalà mantecato su polenta taragna e rostì di patate', '', 17.00, '["gluten","fish"]', 1, 0, 3),
(@tid, @c, 'Burratina di Andria con alici del Mar Cantabrico su valeriana e datterini', '', 17.00, '["fish","milk"]', 1, 0, 4),
(@tid, @c, 'Prosciutto iberico (spalla) qualità oro su pizza scrocchiarella romana', '', 24.00, '["gluten","milk"]', 1, 0, 5),
(@tid, @c, 'Fiori di zucchina farciti con ricotta su fonduta allo zafferano', '', 16.00, '["milk"]', 1, 0, 6),
(@tid, @c, 'Tarte tatin di cipolla al vapore caramellata con gelato di parmigiano', '', 18.00, '["gluten","milk"]', 1, 0, 7),
(@tid, @c, 'Bruschetta con pomodori datterini Sicilia e olio di pesto al basilico', '', 10.00, '["gluten","nuts"]', 1, 0, 8);

INSERT INTO menu_categories (tenant_id, parent_id, name, description, icon, is_wine, sort_order, is_active)
VALUES (@tid, NULL, 'Le paste', '', 'pasta-bowl', 0, 1, 1);
SET @c := LAST_INSERT_ID();
INSERT INTO menu_items (tenant_id, category_id, name, description, price, allergens, is_available, is_daily_special, sort_order) VALUES
(@tid, @c, 'Tonnarelli con ricciola del basso Tirreno, timo e datterini', '', 20.00, '["gluten","eggs","fish"]', 1, 0, 0),
(@tid, @c, 'Tonnarelli aglio olio e peperoncino con pomodorini alla brace, menta e pecorino', '', 18.00, '["gluten","eggs","milk"]', 1, 0, 1),
(@tid, @c, 'Gricia (pasta all''uovo artigianale) con ricotta di pecora', '', 18.00, '["gluten","eggs","milk"]', 1, 0, 2),
(@tid, @c, 'Pomodoro e basilico con parmigiano 63 mesi (pasta all''uovo)', '', 18.00, '["gluten","eggs","milk"]', 1, 0, 3),
(@tid, @c, 'Gnocchetti di patate, alici, maggiorana e capperi in burro fresco di Giulietta', '', 19.00, '["gluten","fish","milk"]', 1, 0, 4);

INSERT INTO menu_categories (tenant_id, parent_id, name, description, icon, is_wine, sort_order, is_active)
VALUES (@tid, NULL, 'Secondi', '', 'bi-fire', 0, 2, 1);
SET @c := LAST_INSERT_ID();
INSERT INTO menu_items (tenant_id, category_id, name, description, price, allergens, is_available, is_daily_special, sort_order) VALUES
(@tid, @c, 'Polpette di vitella al sugo', '', 18.00, '["gluten","milk"]', 1, 0, 0),
(@tid, @c, 'Vitel tonnè', '', 18.00, '["eggs","fish"]', 1, 0, 1),
(@tid, @c, 'Suprema di pollo, con aromatiche erbette, rostì di patate, maionese al lime', '', 22.00, '["eggs"]', 1, 0, 2),
(@tid, @c, 'Baccalà alla romana, pomodoro, cipolla e olive su polentina grigliata', '', 22.00, '["celery"]', 1, 0, 3),
(@tid, @c, 'Filetto di orata in crosta di zucchine romanesche e rostì di patate', '', 22.00, '["eggs"]', 1, 0, 4);

INSERT INTO menu_categories (tenant_id, parent_id, name, description, icon, is_wine, sort_order, is_active)
VALUES (@tid, NULL, 'I contorni', '', 'bi-flower1', 0, 3, 1);
SET @c := LAST_INSERT_ID();
INSERT INTO menu_items (tenant_id, category_id, name, description, price, allergens, is_available, is_daily_special, sort_order) VALUES
(@tid, @c, 'Insalata verde e olio al basilico', '', 9.00, '["nuts"]', 1, 0, 0),
(@tid, @c, 'Insalata con pomodorini di Sicilia e olio al basilico', '', 10.00, '["nuts"]', 1, 0, 1),
(@tid, @c, 'Cicoria ripassata', '', 8.00, NULL, 1, 0, 2),
(@tid, @c, 'Patate al sale affumicato con maionese al lime', '', 8.00, '["eggs"]', 1, 0, 3);

INSERT INTO menu_categories (tenant_id, parent_id, name, description, icon, is_wine, sort_order, is_active)
VALUES (@tid, NULL, 'I dolci', '', 'bi-cake2', 0, 4, 1);
SET @c := LAST_INSERT_ID();
INSERT INTO menu_items (tenant_id, category_id, name, description, price, allergens, is_available, is_daily_special, sort_order) VALUES
(@tid, @c, 'Tiramisù con cantucci alle nocciole', '', 10.00, '["gluten","milk","nuts"]', 1, 0, 0),
(@tid, @c, 'Crumble con crema chantilly, cantucci alle nocciole, mele e cannella', '', 10.00, '["gluten","eggs","milk","nuts"]', 1, 0, 1),
(@tid, @c, 'Crème brûlée', '', 10.00, '["eggs","milk"]', 1, 0, 2),
(@tid, @c, 'Gelato al cioccolato del Perù con mirtilli', '', 9.00, NULL, 1, 0, 3);

