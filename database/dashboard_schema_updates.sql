-- Schema Updates for Client Dashboard
-- Run these queries to update existing database to support dashboard functionality

-- Add destination_city_id to shipments table
ALTER TABLE shipments
ADD COLUMN destination_city_id INT AFTER destination_country,
ADD FOREIGN KEY (destination_city_id) REFERENCES cities(city_id) ON DELETE SET NULL,
ADD INDEX idx_destination_city (destination_city_id);

-- Create client_notifications table
CREATE TABLE IF NOT EXISTS client_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT, -- Can reference shipment_id, invoice_id, etc.
    reference_type VARCHAR(50), -- 'shipment', 'invoice', 'payment', etc.
    is_read BOOLEAN DEFAULT FALSE,
    icon VARCHAR(50) DEFAULT 'bell',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE,
    INDEX idx_client_id (client_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Insert some sample notifications for testing (remove in production)
-- INSERT INTO client_notifications (client_id, type, title, message, reference_type, icon) VALUES
-- (1, 'shipment_update', 'Shipment Update', 'Your shipment LS2024001 is now in transit', 'shipment', 'package'),
-- (1, 'delivery_confirmed', 'Delivery Confirmed', 'Your shipment LS2024003 has been delivered successfully', 'shipment', 'check'),
-- (1, 'payment_due', 'Payment Due', 'Invoice #INV-2024-001 is due in 3 days', 'invoice', 'alert');