-- DROP Table 'users', 'game_states';
CREATE TABLE `game_states` (
    `id` int(10) UNSIGNED NOT NULL,
    `user_id` int(10) UNSIGNED NOT NULL,
    `name` varchar(25) NOT NULL,
    `map_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`map_json`)),
    `xp` int(11) DEFAULT 0,
    `curMana` int(11) DEFAULT 100
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
CREATE TABLE `users` (
    `id` int(10) UNSIGNED NOT NULL,
    `username` varchar(50) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `email` varchar(30) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `last_login_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
ALTER TABLE `game_states`
ADD PRIMARY KEY (`id`),
    ADD KEY `game_states_ibfk_1` (`user_id`);
ALTER TABLE `users`
ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `username` (`username`);
ALTER TABLE `game_states`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 6;
ALTER TABLE `users`
MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 40;
ALTER TABLE `game_states`
ADD CONSTRAINT `game_states_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE users
ADD COLUMN profile_pic_path VARCHAR(255) NULL;
COMMIT;