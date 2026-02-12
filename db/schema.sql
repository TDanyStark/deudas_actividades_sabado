-- Schema for iglesia deudas app

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    role ENUM('admin', 'responsable') NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_date DATE NOT NULL,
    token VARCHAR(64) NOT NULL,
    token_expires_at DATETIME NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assignments_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    total_value DECIMAL(10,2) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activities_assignment FOREIGN KEY (assignment_id) REFERENCES assignments(id)
);

CREATE TABLE debtors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    debtor_name VARCHAR(120) NOT NULL,
    units INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_debtors_activity FOREIGN KEY (activity_id) REFERENCES activities(id)
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    debtor_id INT NOT NULL,
    paid_amount DECIMAL(10,2) NOT NULL,
    paid_by_user_id INT NULL,
    paid_by_role ENUM('admin', 'responsable') NOT NULL,
    paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_debtor FOREIGN KEY (debtor_id) REFERENCES debtors(id)
);

-- Create an admin user by generating a password hash in PHP:
-- php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
-- INSERT INTO users (name, email, role, password_hash, active) VALUES ('Admin', 'admin@iglesia.local', 'admin', 'PASTE_HASH_HERE', 1);
