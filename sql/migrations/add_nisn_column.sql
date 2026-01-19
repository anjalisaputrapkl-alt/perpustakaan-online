-- Add NISN column to users table for student login
ALTER TABLE `users` ADD COLUMN `nisn` VARCHAR(20) UNIQUE AFTER `email` COMMENT 'Nomor Induk Siswa Nasional for student login';

-- Add NISN column to members table
ALTER TABLE `members` ADD COLUMN `nisn` VARCHAR(20) UNIQUE COMMENT 'Nomor Induk Siswa Nasional' AFTER `member_no`;

-- Update users table role enum to include 'student'
ALTER TABLE `users` MODIFY COLUMN `role` enum('admin','librarian','student') DEFAULT 'librarian';
