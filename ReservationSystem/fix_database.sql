-- Oprava databáze - AUTO_INCREMENT a vyčištění špatných dat
USE rezervace;

-- 1. Smazání všech rezervací s ID 0 nebo neplatnými daty
DELETE FROM reservations WHERE id = 0 OR user_id = 0;

-- 2. Reset AUTO_INCREMENT pro tabulku reservations
ALTER TABLE reservations AUTO_INCREMENT = 1;

-- 3. Kontrola struktury tabulky reservations
DESCRIBE reservations;

-- 4. Kontrola AUTO_INCREMENT nastavení
SHOW CREATE TABLE reservations;

-- 5. Zobrazení všech rezervací po vyčištění
SELECT * FROM reservations;

-- 6. Zobrazení všech uživatelů pro kontrolu
SELECT id, username, role FROM users;

-- 7. Oprava AUTO_INCREMENT pro tabulku users (pokud je potřeba)
-- ALTER TABLE users AUTO_INCREMENT = 1;

-- 8. Kontrola, jestli nejsou uživatelé s ID 0
SELECT * FROM users WHERE id = 0;
