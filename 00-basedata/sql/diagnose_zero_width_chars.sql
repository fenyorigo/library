-- Diagnostic report: find zero-width/format characters in catalog text columns.
-- Targets: U+200B, U+200C, U+200D, U+FEFF, U+2060

SET collation_connection = 'utf8mb4_bin';

SET @zwsp  := CONVERT(0xE2808B USING utf8mb4) COLLATE utf8mb4_bin; -- U+200B
SET @zwnj  := CONVERT(0xE2808C USING utf8mb4) COLLATE utf8mb4_bin; -- U+200C
SET @zwj   := CONVERT(0xE2808D USING utf8mb4) COLLATE utf8mb4_bin; -- U+200D
SET @bom   := CONVERT(0xEFBBBF USING utf8mb4) COLLATE utf8mb4_bin; -- U+FEFF
SET @wj    := CONVERT(0xE281A0 USING utf8mb4) COLLATE utf8mb4_bin; -- U+2060

-- Summary counts
SELECT 'Books.title' AS field,
       SUM(COALESCE(INSTR(title COLLATE utf8mb4_bin, @zwsp), 0) > 0) AS zwsp,
       SUM(COALESCE(INSTR(title COLLATE utf8mb4_bin, @zwnj), 0) > 0) AS zwnj,
       SUM(COALESCE(INSTR(title COLLATE utf8mb4_bin, @zwj), 0) > 0) AS zwj,
       SUM(COALESCE(INSTR(title COLLATE utf8mb4_bin, @bom), 0) > 0) AS bom,
       SUM(COALESCE(INSTR(title COLLATE utf8mb4_bin, @wj), 0) > 0) AS wj
FROM Books
UNION ALL
SELECT 'Books.subtitle',
       SUM(COALESCE(INSTR(subtitle COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(subtitle COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(subtitle COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(subtitle COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(subtitle COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM Books
UNION ALL
SELECT 'Books.series',
       SUM(COALESCE(INSTR(series COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(series COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(series COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(series COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(series COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM Books
UNION ALL
SELECT 'Books.isbn',
       SUM(COALESCE(INSTR(isbn COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(isbn COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(isbn COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(isbn COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(isbn COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM Books
UNION ALL
SELECT 'Books.lccn',
       SUM(COALESCE(INSTR(lccn COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(lccn COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(lccn COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(lccn COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(lccn COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM Books
UNION ALL
SELECT 'Books.notes',
       SUM(COALESCE(INSTR(notes COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(notes COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(notes COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(notes COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(notes COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM Books
UNION ALL
SELECT 'Books.loaned_to',
       SUM(COALESCE(INSTR(loaned_to COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(loaned_to COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(loaned_to COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(loaned_to COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(loaned_to COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM Books
UNION ALL
SELECT 'Authors.name',
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM Authors
UNION ALL
SELECT 'Authors.first_name',
       SUM(COALESCE(INSTR(first_name COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(first_name COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(first_name COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(first_name COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(first_name COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM Authors
UNION ALL
SELECT 'Authors.last_name',
       SUM(COALESCE(INSTR(last_name COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(last_name COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(last_name COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(last_name COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(last_name COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM Authors
UNION ALL
SELECT 'Authors.sort_name',
       SUM(COALESCE(INSTR(sort_name COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(sort_name COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(sort_name COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(sort_name COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(sort_name COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM Authors
UNION ALL
SELECT 'Publishers.name',
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM Publishers
UNION ALL
SELECT 'Subjects.name',
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(name COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM Subjects
UNION ALL
SELECT 'duplicate_review.note',
       SUM(COALESCE(INSTR(note COLLATE utf8mb4_bin, @zwsp), 0) > 0),
       SUM(COALESCE(INSTR(note COLLATE utf8mb4_bin, @zwnj), 0) > 0),
       SUM(COALESCE(INSTR(note COLLATE utf8mb4_bin, @zwj), 0) > 0),
       SUM(COALESCE(INSTR(note COLLATE utf8mb4_bin, @bom), 0) > 0),
       SUM(COALESCE(INSTR(note COLLATE utf8mb4_bin, @wj), 0) > 0)
FROM duplicate_review;

-- Sample rows (up to 20) for quick inspection.
SELECT book_id, title, subtitle
FROM Books
WHERE INSTR(title COLLATE utf8mb4_bin, @zwsp) > 0
   OR INSTR(title COLLATE utf8mb4_bin, @zwnj) > 0
   OR INSTR(title COLLATE utf8mb4_bin, @zwj) > 0
   OR INSTR(title COLLATE utf8mb4_bin, @bom) > 0
   OR INSTR(title COLLATE utf8mb4_bin, @wj) > 0
   OR INSTR(subtitle COLLATE utf8mb4_bin, @zwsp) > 0
   OR INSTR(subtitle COLLATE utf8mb4_bin, @zwnj) > 0
   OR INSTR(subtitle COLLATE utf8mb4_bin, @zwj) > 0
   OR INSTR(subtitle COLLATE utf8mb4_bin, @bom) > 0
   OR INSTR(subtitle COLLATE utf8mb4_bin, @wj) > 0
LIMIT 20;
