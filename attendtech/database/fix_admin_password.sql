-- ============================================================
-- AttendTech — Admin Password Fix
-- Run this in phpMyAdmin > attendance_system > SQL tab
-- Default credentials after running this:
--   Username: admin
--   Password: admin123
-- ============================================================

USE attendance_system;

UPDATE admins
SET password = '$2y$10$VnkBPbA1WJLr574cCnrznO4oJMrcFszcxdLBhDINAX2eGzNDcB0gK'
WHERE username = 'admin';

-- Verify it updated
SELECT id, username, full_name, email FROM admins WHERE username = 'admin';
