-- Insert sample notifications for testing
INSERT INTO client_notifications (client_id, type, title, message, reference_id, reference_type, is_read, icon) VALUES
(1, 'info', 'Welcome to XY Cargo', 'Welcome to our logistics platform! Your account has been successfully created.', NULL, NULL, false, 'bell'),
(1, 'package', 'Parcel Shipped', 'Your parcel #12345 has been shipped and is on its way to the destination.', 1, 'parcel', false, 'package'),
(1, 'payment', 'Payment Received', 'We have received your payment of $150.00 for parcel #12345.', 1, 'payment', true, 'dollar'),
(1, 'alert', 'Delivery Delayed', 'There is a slight delay in the delivery of parcel #12345 due to weather conditions.', 1, 'parcel', false, 'alert'),
(1, 'check', 'Parcel Delivered', 'Your parcel #12345 has been successfully delivered to the recipient.', 1, 'parcel', true, 'check'),
(1, 'urgent', 'Action Required', 'Please update your shipping address for pending parcels.', NULL, NULL, false, 'alert');