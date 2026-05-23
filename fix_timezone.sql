USE attendance_system;
SET time_zone = '+08:00';

UPDATE system_settings SET setting_value = 'Asia/Manila' WHERE setting_key = 'timezone';
