-- Existing XAMPP/MariaDB installation only. Export a database backup before importing.
USE careconnect;
ALTER TABLE users MODIFY role ENUM('elder','family','customer','provider','admin') NOT NULL;
UPDATE users SET role='customer' WHERE role IN ('elder','family');
ALTER TABLE users MODIFY role ENUM('customer','provider','admin') NOT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS session_version INT UNSIGNED NOT NULL DEFAULT 0;
-- IDs, passwords, bookings, invitation codes and existing approved links remain intact.
