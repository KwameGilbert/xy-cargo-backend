-- Add new columns to shipments table
ALTER TABLE shipments
ADD COLUMN carrier VARCHAR(100) DEFAULT 'Not Assigned' AFTER destination_country,
ADD COLUMN estimated_arrival TIMESTAMP NULL AFTER departure_date,
ADD COLUMN actual_arrival TIMESTAMP NULL AFTER estimated_arrival,
ADD COLUMN total_parcels INT DEFAULT 0,
ADD COLUMN total_weight DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN total_volume DECIMAL(10,3) DEFAULT 0.000,
ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0.00;

-- Create shipment_timeline table
CREATE TABLE IF NOT EXISTS shipment_timeline (
    timeline_id INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    location VARCHAR(255) NOT NULL,
    date TIMESTAMP NOT NULL,
    notes TEXT,
    staff_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(shipment_id) ON DELETE CASCADE,
    INDEX idx_shipment_id (shipment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indices for common queries
CREATE INDEX idx_shipment_status ON shipments(status);
CREATE INDEX idx_shipment_dates ON shipments(departure_date, estimated_arrival, actual_arrival);