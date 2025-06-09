-- Oprava user_id v rezervacích
USE rezervace;

-- 1. Zobrazení všech uživatelů a jejich ID
SELECT id, username, role FROM users ORDER BY id;

-- 2. Zobrazení aktuálních rezervací s neplatnými user_id
SELECT id, classroom_id, user_id, time_started, status FROM reservations;

-- 3. Oprava user_id - přiřadíme rezervace prvnímu uživateli (můžete změnit podle potřeby)
-- Najdeme první platné user ID
SET @first_user_id = (SELECT MIN(id) FROM users WHERE id > 0);

-- Aktualizujeme všechny rezervace s user_id = 0 na první platné user ID
UPDATE reservations 
SET user_id = @first_user_id 
WHERE user_id = 0;

-- 4. Kontrola po opravě
SELECT r.id, r.classroom_id, r.user_id, u.username, r.time_started, r.status 
FROM reservations r 
LEFT JOIN users u ON r.user_id = u.id 
ORDER BY r.id;

-- 5. Zobrazení statistik
SELECT 
    COUNT(*) as total_reservations,
    COUNT(DISTINCT user_id) as unique_users,
    MIN(user_id) as min_user_id,
    MAX(user_id) as max_user_id
FROM reservations;
