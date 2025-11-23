
CREATE DATABASE IF NOT EXISTS hotel_booking;
USE hotel_booking;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    -- verification fields
    id_document VARCHAR(255) DEFAULT NULL,
    id_selfie VARCHAR(255) DEFAULT NULL,
    gender VARCHAR(16) DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    phone VARCHAR(32) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    oauth_provider VARCHAR(50) DEFAULT NULL,
    oauth_id VARCHAR(255) DEFAULT NULL,
    is_blocked TINYINT(1) DEFAULT 0,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (email)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('superadmin', 'staff') DEFAULT 'staff',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) UNIQUE NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    status ENUM('available', 'booked', 'maintenance') DEFAULT 'available',
    description TEXT,
    image VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending',
    date_booked TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    payment_method ENUM('gcash', 'paypal', 'credit_card', 'cash') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'approved', 'rejected', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS booking_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    action ENUM('created', 'updated', 'cancelled', 'completed', 'rejected') NOT NULL,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admin_audit (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT NULL,
    booking_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    note TEXT,
    ip VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id) ON DELETE SET NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    from_admin TINYINT(1) DEFAULT 0,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    date_sent TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS analytics (
    analytics_id INT AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    total_bookings INT DEFAULT 0,
    approved_bookings INT DEFAULT 0,
    pending_bookings INT DEFAULT 0,
    rejected_bookings INT DEFAULT 0,
    total_income DECIMAL(10,2) DEFAULT 0.00
);


DELIMITER $$
DROP PROCEDURE IF EXISTS ensure_users_profile_columns$$
CREATE PROCEDURE ensure_users_profile_columns()
BEGIN
    DECLARE cnt INT DEFAULT 0;
   
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'addreess';
    IF cnt > 0 THEN
        SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'address';
        IF cnt = 0 THEN
            SET @s = CONCAT('ALTER TABLE `', DATABASE(), '`.`users` CHANGE COLUMN `addreess` `address` VARCHAR(255) DEFAULT NULL');
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        END IF;
    END IF;

   
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar';
    IF cnt = 0 THEN
        ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL;
    END IF;

    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'id_document';
    IF cnt = 0 THEN
        ALTER TABLE users ADD COLUMN id_document VARCHAR(255) DEFAULT NULL;
    END IF;

    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'id_selfie';
    IF cnt = 0 THEN
        ALTER TABLE users ADD COLUMN id_selfie VARCHAR(255) DEFAULT NULL;
    END IF;

    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_verified';
    IF cnt = 0 THEN
        ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0;
    END IF;

    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'verification_requested';
    IF cnt = 0 THEN
        ALTER TABLE users ADD COLUMN verification_requested TINYINT(1) DEFAULT 0;
    END IF;

    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'verification_requested_at';
    IF cnt = 0 THEN
        ALTER TABLE users ADD COLUMN verification_requested_at DATETIME DEFAULT NULL;
    END IF;

    
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'gender';
    IF cnt = 0 THEN
        ALTER TABLE users ADD COLUMN gender VARCHAR(16) DEFAULT NULL;
    END IF;

   
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'date_of_birth';
    IF cnt = 0 THEN
        ALTER TABLE users ADD COLUMN date_of_birth DATE DEFAULT NULL;
    END IF;

    
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'phone';
    IF cnt = 0 THEN
        ALTER TABLE users ADD COLUMN phone VARCHAR(32) DEFAULT NULL;
    END IF;

   
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'address';
    IF cnt = 0 THEN
        ALTER TABLE users ADD COLUMN address VARCHAR(255) DEFAULT NULL;
    END IF;
END$$
CALL ensure_users_profile_columns()$$
DROP PROCEDURE IF EXISTS ensure_users_profile_columns$$
DELIMITER ;

