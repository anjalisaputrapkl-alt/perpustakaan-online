-- ==========================================
-- TABEL NOTIFICATIONS
-- ==========================================

CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    student_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('borrow', 'return_request', 'return_confirm', 'late_warning', 'info', 'new_book') NOT NULL DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_student_school (student_id, school_id),
    KEY idx_read_status (is_read),
    KEY idx_type (type),
    KEY idx_created_at (created_at),
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Untuk query unread notifications
CREATE INDEX idx_student_unread ON notifications(student_id, is_read, created_at DESC);

-- Untuk query by type
CREATE INDEX idx_student_type ON notifications(student_id, type, created_at DESC);
