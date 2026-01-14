-- BookCatalog v2.2.0 schema (canonical)

CREATE TABLE IF NOT EXISTS Users (
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(64) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','reader') NOT NULL DEFAULT 'reader',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  force_password_change TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME NULL,
  PRIMARY KEY (user_id),
  UNIQUE KEY uniq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS UserPreferences (
  user_id INT UNSIGNED NOT NULL,
  logo_path VARCHAR(255) NULL,
  bg_color CHAR(7) NULL,
  fg_color CHAR(7) NULL,
  text_size VARCHAR(16) NOT NULL DEFAULT 'medium',
  per_page INT NOT NULL DEFAULT 25,
  show_cover TINYINT(1) NOT NULL DEFAULT 1,
  show_subtitle TINYINT(1) NOT NULL DEFAULT 1,
  show_series TINYINT(1) NOT NULL DEFAULT 1,
  show_is_hungarian TINYINT(1) NOT NULL DEFAULT 1,
  show_publisher TINYINT(1) NOT NULL DEFAULT 1,
  show_year TINYINT(1) NOT NULL DEFAULT 1,
  show_status TINYINT(1) NOT NULL DEFAULT 1,
  show_placement TINYINT(1) NOT NULL DEFAULT 1,
  show_isbn TINYINT(1) NOT NULL DEFAULT 0,
  show_loaned_to TINYINT(1) NOT NULL DEFAULT 0,
  show_loaned_date TINYINT(1) NOT NULL DEFAULT 0,
  show_subjects TINYINT(1) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_userprefs_user
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Publishers (
  publisher_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY (publisher_id),
  UNIQUE KEY uniq_publishers_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Subjects (
  subject_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY (subject_id),
  UNIQUE KEY uniq_subjects_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Authors (
  author_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NULL,
  first_name VARCHAR(255) NULL,
  last_name VARCHAR(255) NULL,
  sort_name VARCHAR(255) NULL,
  is_hungarian TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (author_id),
  KEY idx_authors_sort_name (sort_name),
  KEY idx_authors_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Placement (
  placement_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  bookcase_no INT NOT NULL,
  shelf_no INT NOT NULL,
  PRIMARY KEY (placement_id),
  UNIQUE KEY uniq_placement (bookcase_no, shelf_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Books (
  book_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(512) NOT NULL,
  subtitle VARCHAR(512) NULL,
  series VARCHAR(255) NULL,
  publisher_id INT UNSIGNED NULL,
  year_published INT NULL,
  isbn VARCHAR(64) NULL,
  lccn VARCHAR(64) NULL,
  cover_image VARCHAR(255) NULL,
  cover_thumb VARCHAR(255) NULL,
  placement_id INT UNSIGNED NULL,
  loaned_to VARCHAR(255) NULL,
  loaned_date DATE NULL,
  PRIMARY KEY (book_id),
  KEY idx_books_publisher (publisher_id),
  KEY idx_books_placement (placement_id),
  KEY idx_books_year (year_published),
  CONSTRAINT fk_books_publisher
    FOREIGN KEY (publisher_id) REFERENCES Publishers(publisher_id)
    ON DELETE SET NULL,
  CONSTRAINT fk_books_placement
    FOREIGN KEY (placement_id) REFERENCES Placement(placement_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Books_Authors (
  book_id INT UNSIGNED NOT NULL,
  author_id INT UNSIGNED NOT NULL,
  author_ord INT NOT NULL DEFAULT 0,
  PRIMARY KEY (book_id, author_id),
  KEY idx_books_authors_author (author_id),
  CONSTRAINT fk_books_authors_book
    FOREIGN KEY (book_id) REFERENCES Books(book_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_books_authors_author
    FOREIGN KEY (author_id) REFERENCES Authors(author_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Books_Subjects (
  book_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (book_id, subject_id),
  KEY idx_books_subjects_subject (subject_id),
  CONSTRAINT fk_books_subjects_book
    FOREIGN KEY (book_id) REFERENCES Books(book_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_books_subjects_subject
    FOREIGN KEY (subject_id) REFERENCES Subjects(subject_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS AuthEvents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  username_snapshot VARCHAR(190) NOT NULL,
  event_type VARCHAR(32) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  user_agent VARCHAR(512) NULL,
  details TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_authevents_user (user_id),
  KEY idx_authevents_type (event_type),
  KEY idx_authevents_created (created_at),
  CONSTRAINT fk_authevents_user
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS SystemInfo (
  key_name VARCHAR(64) NOT NULL,
  value TEXT NOT NULL,
  PRIMARY KEY (key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
