-- Database Migration Script for XY Cargo Backend
-- Run this script against your MySQL database to update the schema for parcels and shipments
-- Date: October 24, 2025

-- Add new fields to shipments table
ALTER TABLE shipments
ADD COLUMN waybill_number VARCHAR(100) UNIQUE AFTER shipment_id,
ADD COLUMN origin_warehouse_id INT AFTER warehouse_id,
ADD COLUMN destination_warehouse_id INT AFTER origin_warehouse_id,
ADD FOREIGN KEY (origin_warehouse_id) REFERENCES warehouses(warehouse_id) ON DELETE SET NULL,
ADD FOREIGN KEY (destination_warehouse_id) REFERENCES warehouses(warehouse_id) ON DELETE SET NULL,
ADD INDEX idx_waybill_number (waybill_number),
ADD INDEX idx_origin_warehouse (origin_warehouse_id),
ADD INDEX idx_destination_warehouse (destination_warehouse_id);

-- Update parcels table: remove waybill_number, add tracking_number, category, notes
ALTER TABLE parcels
DROP COLUMN waybill_number,
ADD COLUMN tracking_number VARCHAR(100) UNIQUE AFTER parcel_id,
ADD COLUMN category VARCHAR(100) AFTER payment_status,
ADD COLUMN notes TEXT AFTER category,
ADD INDEX idx_tracking_number (tracking_number);

-- Create parcel_documents table
CREATE TABLE IF NOT EXISTS parcel_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    parcel_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parcel_id) REFERENCES parcels(parcel_id) ON DELETE CASCADE,
    INDEX idx_parcel_id (parcel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Generate waybill numbers for existing shipments
-- This will create waybill numbers like 'WB-2025-0001', 'WB-2025-0002', etc.
SET @row_number = 0;
UPDATE shipments
SET waybill_number = CONCAT('WB-', YEAR(CURDATE()), '-', LPAD(@row_number:=@row_number+1, 4, '0'))
WHERE waybill_number IS NULL;

-- Optional: Generate tracking numbers for existing parcels
-- This will create tracking numbers like 'TR001234567', 'TR001234568', etc.
SET @tracking_base = 1234567;
UPDATE parcels
SET tracking_number = CONCAT('XY', LPAD(@tracking_base:=@tracking_base+1, 9, '0'))
WHERE tracking_number IS NULL;

-- Verify the changes
SELECT 'Shipments table structure updated' as status;
DESCRIBE shipments;

SELECT 'Parcels table structure updated' as status;
DESCRIBE parcels;

SELECT 'Parcel documents table created' as status;
DESCRIBE parcel_documents;