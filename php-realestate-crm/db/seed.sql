-- Entirely fictional demo data. Names, e-mails (example.com) and phones (555-01xx) are invented.
INSERT INTO properties (title, city, neighborhood, price_usd, bedrooms, bathrooms, area_m2, status) VALUES
('Sunny two-bedroom apartment', 'Springfield', 'Maple Ridge', 215000, 2, 1, 78, 'available'),
('Renovated family house with garden', 'Springfield', 'Oak Park', 389000, 4, 3, 190, 'available'),
('Compact studio near the station', 'Riverton', 'Old Town', 129000, 1, 1, 34, 'available'),
('Penthouse with city view', 'Riverton', 'Harbor Row', 720000, 3, 2, 142, 'reserved'),
('Three-bedroom townhouse', 'Lakeside', 'Pine Hollow', 298000, 3, 2, 121, 'available'),
('Cozy cottage by the lake', 'Lakeside', 'Water Lane', 345000, 2, 1, 96, 'sold');

INSERT INTO leads (name, email, phone, property_id, stage, notes) VALUES
('Alex Example', 'alex@example.com', '555-0101', 1, 'new', 'Asked about parking.'),
('Sam Sample', 'sam@example.com', '555-0102', 2, 'contacted', NULL),
('Jordan Demo', 'jordan@example.com', NULL, NULL, 'new', 'General inquiry.');
