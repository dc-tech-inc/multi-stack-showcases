DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS leads;
DROP TABLE IF EXISTS properties;

CREATE TABLE properties (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  city VARCHAR(60) NOT NULL,
  neighborhood VARCHAR(60) NOT NULL,
  price_usd INT UNSIGNED NOT NULL,
  bedrooms TINYINT UNSIGNED NOT NULL,
  bathrooms TINYINT UNSIGNED NOT NULL,
  area_m2 SMALLINT UNSIGNED NOT NULL,
  status ENUM('available','reserved','sold') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_properties_search (status, city, price_usd)
) ENGINE=InnoDB;

CREATE TABLE leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30) NULL,
  property_id INT UNSIGNED NULL,
  stage ENUM('new','contacted','visit_scheduled','won','lost') NOT NULL DEFAULT 'new',
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_leads_stage (stage),
  CONSTRAINT fk_leads_property FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE visits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED NOT NULL,
  property_id INT UNSIGNED NOT NULL,
  scheduled_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_visit_slot (property_id, scheduled_at),
  CONSTRAINT fk_visits_lead FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE,
  CONSTRAINT fk_visits_property FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE
) ENGINE=InnoDB;
