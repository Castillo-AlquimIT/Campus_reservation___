-- Create database
CREATE DATABASE campus_reservation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_reservation;

-- Users with roles: student, faculty, admin
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','faculty','admin') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms
CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  location VARCHAR(150),
  capacity INT DEFAULT 0,
  attributes JSON NULL,
  is_active TINYINT(1) DEFAULT 1
);

-- Equipment
CREATE TABLE equipment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  category VARCHAR(100),
  quantity INT NOT NULL DEFAULT 1,
  is_active TINYINT(1) DEFAULT 1
);

-- Reservations (room or equipment via nullable foreign keys)
CREATE TABLE reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  room_id INT NULL,
  equipment_id INT NULL,
  title VARCHAR(150) NOT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  status ENUM('pending','approved','rejected','completed','cancelled','archived')  NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  notes TEXT NULL,
  CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_res_room FOREIGN KEY (room_id) REFERENCES rooms(id),
  CONSTRAINT fk_res_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id),
  CONSTRAINT chk_res_target CHECK ((room_id IS NOT NULL) XOR (equipment_id IS NOT NULL)),
  INDEX idx_res_room_time (room_id, start_datetime, end_datetime),
  INDEX idx_res_equipment_time (equipment_id, start_datetime, end_datetime),
  INDEX idx_res_user (user_id)
);

-- Approvals log (who changed status)
CREATE TABLE approvals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reservation_id INT NOT NULL,
  actor_user_id INT NOT NULL,
  from_status ENUM('pending','approved','rejected','completed','cancelled') NOT NULL,
  to_status ENUM('pending','approved','rejected','completed','cancelled') NOT NULL,
  remarks VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_app_res FOREIGN KEY (reservation_id) REFERENCES reservations(id),
  CONSTRAINT fk_app_actor FOREIGN KEY (actor_user_id) REFERENCES users(id)
);

-- Utilization logs (optional, can aggregate from reservations)
CREATE TABLE utilization_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reservation_id INT NOT NULL,
  resource_type ENUM('room','equipment') NOT NULL,
  resource_id INT NOT NULL,
  user_id INT NOT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_util_res FOREIGN KEY (reservation_id) REFERENCES reservations(id),
  INDEX idx_util_resource (resource_type, resource_id, start_datetime, end_datetime)
);

-- Seed an admin user (replace hash with a real bcrypt hash)
INSERT INTO users (name, email, password_hash, role)
VALUES ('Admin', 'admin@example.com', '$2y$10$wqgO7JvYbU6O9s3uYz1j4eCqk0H7Tj0eKqVw0M1i2l3Q4r5S6t7u8', 'admin');

-- Example rooms and equipment
INSERT INTO rooms (name, location, capacity) VALUES
('Room A101', 'Building A, 1st Floor', 40),
('Room B202', 'Building B, 2nd Floor', 60);

INSERT INTO equipment (name, category, quantity) VALUES
('Projector P1', 'Projector', 2),
('Laptop L1', 'Laptop', 10);

select * from users;
ALTER TABLE reservations 
MODIFY status ENUM('pending','approved','rejected','completed','cancelled','archived') 
NOT NULL DEFAULT 'pending';

