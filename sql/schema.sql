CREATE DATABASE IF NOT EXISTS biblioteca
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE biblioteca;

DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','librarian') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  author VARCHAR(120) NOT NULL,
  isbn VARCHAR(20) UNIQUE,
  category VARCHAR(80),
  year INT,
  total_copies INT NOT NULL DEFAULT 1,
  available_copies INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  book_id INT NOT NULL,
  loan_date DATE NOT NULL,
  due_date DATE NOT NULL,
  return_date DATE NULL,
  status ENUM('borrowed','returned') NOT NULL DEFAULT 'borrowed',
  CONSTRAINT fk_loans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_loans_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

CREATE INDEX idx_books_search ON books(title, author, category);
CREATE INDEX idx_loans_user ON loans(user_id, status);
CREATE INDEX idx_loans_book ON loans(book_id, status);

-- DEMO: parolele sunt: 1234 (hash-ul e pentru "1234")
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin Bibliotecar', 'admin@demo.local',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
', 'librarian'),
('Ion Popa', 'user@demo.local',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO books (title, author, isbn, category, year, total_copies, available_copies) VALUES
('Amintiri din copilărie', 'Ion Creangă', '978000000001', 'Literatură', 1892, 3, 3),
('Baltagul', 'Mihail Sadoveanu', '978000000002', 'Literatură', 1930, 2, 2),
('Introducere în Informatică', 'E. Autor', '978000000003', 'Educație', 2020, 5, 5);
