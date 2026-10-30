-- Pabasa Development Environment
-- Initialize dedicated testing database and restricted test user

CREATE DATABASE IF NOT EXISTS `testing`;
CREATE USER IF NOT EXISTS 'pabasa_test'@'%' IDENTIFIED BY 'local_test_password';
GRANT ALL PRIVILEGES ON `testing`.* TO 'pabasa_test'@'%';
FLUSH PRIVILEGES;

