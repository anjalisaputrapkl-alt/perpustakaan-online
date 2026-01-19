-- Sample Data untuk Featured Sections
-- Masukkan data ini ke database setelah membuat tabel books

-- Pastikan school_id = 1 sudah ada di tabel schools

-- Sample Fiksi Books
INSERT INTO books (school_id, title, author, category, created_at, view_count) VALUES
(1, 'Harry Potter and The Philosopher Stone', 'J.K. Rowling', 'Fiksi', NOW(), 150),
(1, 'The Lord of The Rings', 'J.R.R Tolkien', 'Fiksi', NOW(), 120),
(1, 'Percy Jackson and The Lightning Thief', 'Rick Riordan', 'Fiksi', NOW(), 95),
(1, 'The Hunger Games', 'Suzanne Collins', 'Fiksi', NOW(), 110),
(1, 'Divergent', 'Veronica Roth', 'Fiksi', NOW(), 85),
(1, 'The Maze Runner', 'James Dashner', 'Fiksi', NOW(), 75);

-- Sample Nonfiksi Books
INSERT INTO books (school_id, title, author, category, created_at, view_count) VALUES
(1, 'Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', 'Nonfiksi', NOW(), 130),
(1, 'Atomic Habits', 'James Clear', 'Nonfiksi', NOW(), 140),
(1, 'Thinking, Fast and Slow', 'Daniel Kahneman', 'Nonfiksi', NOW(), 100),
(1, 'The Selfish Gene', 'Richard Dawkins', 'Nonfiksi', NOW(), 90),
(1, 'Educated', 'Tara Westover', 'Nonfiksi', NOW(), 115),
(1, 'A Brief History of Time', 'Stephen Hawking', 'Nonfiksi', NOW(), 80);

-- Sample Referensi Books
INSERT INTO books (school_id, title, author, category, created_at, view_count) VALUES
(1, 'Oxford Advanced Learners Dictionary', 'Oxford', 'Referensi', NOW(), 200),
(1, 'Merriam-Webster Dictionary', 'Merriam-Webster', 'Referensi', NOW(), 180),
(1, 'Encyclopedia Britannica', 'Britannica', 'Referensi', NOW(), 160),
(1, 'Elements of Style', 'William Strunk Jr.', 'Referensi', NOW(), 95),
(1, 'The Chicago Manual of Style', 'University of Chicago', 'Referensi', NOW(), 85),
(1, 'APA Publication Manual', 'American Psychological Association', 'Referensi', NOW(), 70);

-- Sample Komik Books
INSERT INTO books (school_id, title, author, category, created_at, view_count) VALUES
(1, 'One Piece Vol. 1', 'Eiichiro Oda', 'Komik', NOW(), 220),
(1, 'Naruto Vol. 1', 'Masashi Kishimoto', 'Komik', NOW(), 210),
(1, 'Attack on Titan Vol. 1', 'Hajime Isayama', 'Komik', NOW(), 190),
(1, 'My Hero Academia Vol. 1', 'Kohei Horikoshi', 'Komik', NOW(), 185),
(1, 'Demon Slayer Vol. 1', 'Koyoharu Gotouge', 'Komik', NOW(), 175),
(1, 'Tokyo Ghoul Vol. 1', 'Sui Ishida', 'Komik', NOW(), 160);
