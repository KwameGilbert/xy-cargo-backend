-- Shipping Pricing Tables

-- Service Types
CREATE TABLE service_types (
    service_id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(50) NOT NULL,
    service_code VARCHAR(20) UNIQUE NOT NULL, -- 'standard_air', 'express_air', 'sea'
    delivery_time_range VARCHAR(50),
    multiplier DECIMAL(3,2) DEFAULT 1.00,
    minimum_weight_kg DECIMAL(5,2) NULL,
    minimum_volume_cbm DECIMAL(5,2) NULL,
    minimum_charge_usd DECIMAL(8,2) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Destinations
CREATE TABLE destinations (
    destination_id INT PRIMARY KEY AUTO_INCREMENT,
    destination_name VARCHAR(100) NOT NULL,
    destination_code VARCHAR(20) UNIQUE NOT NULL, -- 'lusaka', 'ndola'
    country VARCHAR(50) DEFAULT 'Zambia',
    surcharge_usd DECIMAL(5,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Product Categories
CREATE TABLE product_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    category_code VARCHAR(50) UNIQUE NOT NULL, -- 'normal', 'wigs', 'phones', etc.
    unit_type ENUM('kg', 'piece', 'cbm') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Pricing Rates
CREATE TABLE pricing_rates (
    rate_id INT PRIMARY KEY AUTO_INCREMENT,
    service_id INT NOT NULL,
    destination_id INT NOT NULL,
    category_id INT NOT NULL,
    base_rate_usd DECIMAL(8,2) NOT NULL,
    unit_type ENUM('kg', 'piece', 'cbm') NOT NULL,
    effective_date DATE NOT NULL,
    expiry_date DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (service_id) REFERENCES service_types(service_id),
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id),
    FOREIGN KEY (category_id) REFERENCES product_categories(category_id),
    UNIQUE KEY unique_rate (service_id, destination_id, category_id, effective_date)
);

-- Insert sample data
INSERT INTO service_types (service_name, service_code, delivery_time_range, multiplier, minimum_weight_kg, minimum_volume_cbm, minimum_charge_usd) VALUES
('Standard Air Freight', 'standard_air', '10-17 business days', 1.00, NULL, NULL, NULL),
('Express Air Freight', 'express_air', '7-9 business days', 1.50, 5.00, NULL, NULL),
('Sea Freight', 'sea', '30-45 business days', 1.00, NULL, 0.10, 50.00);

INSERT INTO destinations (destination_name, destination_code, surcharge_usd) VALUES
('Lusaka', 'lusaka', 0.00),
('Ndola', 'ndola', 2.00);

INSERT INTO product_categories (category_name, category_code, unit_type) VALUES
('Normal Goods', 'normal', 'kg'),
('Wigs and Hair', 'wigs', 'kg'),
('Mobile Phones', 'phones', 'piece'),
('Battery Goods/Cosmetics/Toner/Medicine', 'battery', 'kg'),
('Laptops and iPads', 'laptop', 'kg'),
('General Goods (Sea)', 'sea_general', 'cbm'),
('Special Goods (Sea)', 'sea_special', 'cbm');

-- Insert pricing rates (current rates as of today)
INSERT INTO pricing_rates (service_id, destination_id, category_id, base_rate_usd, unit_type, effective_date) VALUES
-- Standard Air - Lusaka
(1, 1, 1, 12.00, 'kg', CURDATE()), -- Normal goods
(1, 1, 2, 14.00, 'kg', CURDATE()), -- Wigs
(1, 1, 3, 11.00, 'piece', CURDATE()), -- Phones
(1, 1, 4, 14.00, 'kg', CURDATE()), -- Battery goods
(1, 1, 5, 16.00, 'kg', CURDATE()), -- Laptops

-- Standard Air - Ndola (+$2)
(1, 2, 1, 14.00, 'kg', CURDATE()), -- Normal goods
(1, 2, 2, 16.00, 'kg', CURDATE()), -- Wigs
(1, 2, 3, 13.00, 'piece', CURDATE()), -- Phones
(1, 2, 4, 16.00, 'kg', CURDATE()), -- Battery goods
(1, 2, 5, 18.00, 'kg', CURDATE()), -- Laptops

-- Sea Freight - Lusaka
(3, 1, 6, 300.00, 'cbm', CURDATE()), -- General goods
(3, 1, 7, 330.00, 'cbm', CURDATE()), -- Special goods

-- Sea Freight - Ndola
(3, 2, 6, 330.00, 'cbm', CURDATE()), -- General goods
(3, 2, 7, 360.00, 'cbm', CURDATE()); -- Special goods


