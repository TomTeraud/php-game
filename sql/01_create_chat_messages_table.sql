CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `client_resource_id` VARCHAR(255) NOT NULL,
  `message_text` TEXT NOT NULL,
  `received_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);