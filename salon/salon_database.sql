-- Create database
CREATE DATABASE IF NOT EXISTS salon_booking;
USE salon_booking;

-- Services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    price DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Customers table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) NOT NULL UNIQUE,
    dob DATE,
    city VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Availability settings table
CREATE TABLE availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    UNIQUE KEY unique_day (day_of_week)
);

-- Blocked dates table
CREATE TABLE blocked_dates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    block_date DATE NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Blocked time slots table
CREATE TABLE blocked_time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    block_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Appointments table
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    otp VARCHAR(6),
    otp_verified BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Invoices table
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('paid', 'pending', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'online') DEFAULT 'cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Invoice items table
CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    service_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- SMS settings table
CREATE TABLE sms_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) NOT NULL,
    sender_mask VARCHAR(50) NOT NULL,
    api_url VARCHAR(255) NOT NULL DEFAULT 'https://portal.richmo.lk/api/v1/sms/send/',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, password, name) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

-- Insert default availability (9 AM to 6 PM, Monday to Saturday)
INSERT INTO availability (day_of_week, start_time, end_time) VALUES
('Monday', '09:00:00', '18:00:00'),
('Tuesday', '09:00:00', '18:00:00'),
('Wednesday', '09:00:00', '18:00:00'),
('Thursday', '09:00:00', '18:00:00'),
('Friday', '09:00:00', '18:00:00'),
('Saturday', '09:00:00', '18:00:00'),
('Sunday', '10:00:00', '16:00:00');

-- Insert sample services
INSERT INTO services (name, description, duration, price) VALUES
('Nail Art', 'Professional nail art and manicure', 60, 1500.00),
('Waxing - Full Body', 'Complete body waxing service', 90, 3000.00),
('Threading - Face', 'Eyebrow and face threading', 30, 500.00),
('Hair Dressing', 'Hair styling and treatment', 120, 2500.00),
('Bridal Makeup', 'Complete bridal makeup package', 180, 15000.00),
('Hair Coloring', 'Professional hair coloring', 150, 4000.00),
('Facial Treatment', 'Facial cleansing and treatment', 60, 2000.00),
('Pedicure', 'Foot care and nail treatment', 45, 1200.00);

-- Insert default SMS settings (replace with your actual API key)
INSERT INTO sms_settings (api_key, sender_mask) 
VALUES ('YOUR_API_KEY_HERE', 'SalonSMS');