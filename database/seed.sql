-- Dummy password_hash values; real ones come from PHP password_hash() later
INSERT INTO users (name, email, phone, password_hash, role) VALUES
('Admin User',     'admin@medq.test',   '0300-0000001', 'dummyhash', 'admin'),
('Reception Desk', 'desk@medq.test',    '0300-0000002', 'dummyhash', 'receptionist'),
('Dr. Ayesha Khan','ayesha@medq.test',  '0300-0000003', 'dummyhash', 'doctor'),
('Dr. Bilal Ahmed','bilal@medq.test',   '0300-0000004', 'dummyhash', 'doctor'),
('Ali Raza',       'ali@medq.test',     '0300-0000005', 'dummyhash', 'patient'),
('Sara Malik',     'sara@medq.test',    '0300-0000006', 'dummyhash', 'patient');

INSERT INTO departments (dept_name) VALUES
('General Medicine'), ('Cardiology'), ('Pediatrics');

INSERT INTO doctors (user_id, dept_id, specialization, room_no, avg_consult_mins) VALUES
(3, 1, 'General Physician', 'R-101', 10),
(4, 2, 'Cardiologist',      'R-205', 15);

INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, max_tokens) VALUES
(1, 'Mon', '09:00', '13:00', 30),
(1, 'Wed', '09:00', '13:00', 30),
(2, 'Tue', '14:00', '18:00', 20),
(2, 'Thu', '14:00', '18:00', 20);

INSERT INTO patients (user_id, name, phone, date_of_birth, gender) VALUES
(5,    'Ali Raza',    '0300-0000005', '1998-04-12', 'male'),
(6,    'Sara Malik',  '0300-0000006', '2001-09-30', 'female'),
(NULL, 'Walk-in Guest','0311-1234567', '1960-01-15', 'male');   -- no account

INSERT INTO tokens (doctor_id, patient_id, token_date, token_no, booking_type, priority, status, called_at, completed_at) VALUES
(1, 1, CURDATE(), 1, 'online',  'normal',    'completed',       NOW() - INTERVAL 30 MINUTE, NOW() - INTERVAL 20 MINUTE),
(1, 2, CURDATE(), 2, 'online',  'normal',    'in_consultation', NOW() - INTERVAL 5 MINUTE,  NULL),
(1, 3, CURDATE(), 3, 'walk_in', 'senior',    'waiting',         NULL, NULL);

INSERT INTO notifications (token_id, recipient_user_id, type, status, sent_at) VALUES
(1, 5, 'token_booked', 'sent', NOW()),
(2, 6, 'your_turn',    'sent', NOW());

INSERT INTO audit_log (user_id, action) VALUES
(1, 'Database seeded'),
(2, 'Created walk-in token #3');
