-- Connect and choose database
USE esp32_smart_energy;

-- Create table
CREATE TABLE energy_readings ( 
  id int(11) NOT NULL AUTO_INCREMENT,
  device_name varchar(50) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  voltage decimal(10,2) DEFAULT NULL,
  current decimal(10,2) DEFAULT NULL,
  power decimal(10,2) DEFAULT NULL,
  energy_consumed decimal(10,2) DEFAULT NULL,
  status_read_sensor_pzem varchar(255) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;