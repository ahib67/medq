
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS tokens;
DROP TABLE IF EXISTS doctor_schedules;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    user_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    phone         VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('patient','doctor','receptionist','admin') NOT NULL DEFAULT 'patient',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE audit_log (
    log_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NULL,
    action     VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_audit_user (user_id)
) ENGINE=InnoDB;


CREATE TABLE departments (
    dept_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dept_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE patients (
    patient_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NULL UNIQUE,
    name          VARCHAR(100) NOT NULL,
    phone         VARCHAR(20),
    date_of_birth DATE,
    gender        ENUM('male','female','other'),
    CONSTRAINT fk_patient_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE doctors (
    doctor_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL UNIQUE,
    dept_id          INT UNSIGNED NOT NULL,
    specialization   VARCHAR(100),
    room_no          VARCHAR(20),
    avg_consult_mins SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    CONSTRAINT fk_doctor_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_doctor_dept FOREIGN KEY (dept_id)
        REFERENCES departments(dept_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE doctor_schedules (
    schedule_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doctor_id   INT UNSIGNED NOT NULL,
    day_of_week ENUM('Mon','Tue','Wed','Thu','Fri','Sat','Sun') NOT NULL,
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL,
    max_tokens  SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    CONSTRAINT fk_sched_doctor FOREIGN KEY (doctor_id)
        REFERENCES doctors(doctor_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_sched_time CHECK (end_time > start_time),
    UNIQUE KEY uq_doctor_day_start (doctor_id, day_of_week, start_time)
) ENGINE=InnoDB;

CREATE TABLE tokens (
    token_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doctor_id    INT UNSIGNED NOT NULL,
    patient_id   INT UNSIGNED NOT NULL,
    token_date   DATE NOT NULL,
    token_no     SMALLINT UNSIGNED NOT NULL,
    booking_type ENUM('online','walk_in') NOT NULL DEFAULT 'online',
    priority     ENUM('normal','senior','emergency') NOT NULL DEFAULT 'normal',
    status       ENUM('waiting','in_consultation','completed','cancelled','no_show')
                 NOT NULL DEFAULT 'waiting',
    booked_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    called_at    DATETIME NULL,
    completed_at DATETIME NULL,

    active_flag  TINYINT GENERATED ALWAYS AS
                 (IF(status = 'in_consultation', 1, NULL)) STORED,

    CONSTRAINT fk_token_doctor FOREIGN KEY (doctor_id)
        REFERENCES doctors(doctor_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_token_patient FOREIGN KEY (patient_id)
        REFERENCES patients(patient_id) ON DELETE RESTRICT ON UPDATE CASCADE,

    UNIQUE KEY uq_token_per_day (doctor_id, token_date, token_no),
    UNIQUE KEY uq_one_active (doctor_id, token_date, active_flag),
    INDEX idx_current_lookup (doctor_id, token_date, status),
    INDEX idx_patient (patient_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    notif_id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_id          INT UNSIGNED NULL,
    recipient_user_id INT UNSIGNED NOT NULL,
    type              ENUM('token_booked','near_turn','your_turn','delayed','cancelled') NOT NULL,
    status            ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    sent_at           DATETIME NULL,
    CONSTRAINT fk_notif_token FOREIGN KEY (token_id)
        REFERENCES tokens(token_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_notif_user FOREIGN KEY (recipient_user_id)
        REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_notif_user (recipient_user_id),
    INDEX idx_notif_token (token_id)
) ENGINE=InnoDB;
