INSERT OR IGNORE INTO users (username, role) VALUES
('dispatcher', 'dispatcher'),
('master1', 'master'),
('master2', 'master');

INSERT OR IGNORE INTO requests (client_name, phone, address, problem_text, status, assigned_to)
VALUES
('Иван Петров', '+79991234567', 'ул. Ленина, 10', 'Не работает холодильник', 'new', NULL),
('Анна Сидорова', '+79997654321', 'пр. Мира, 5', 'Течёт кран', 'assigned', 2);