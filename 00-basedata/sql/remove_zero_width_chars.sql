-- Remove zero-width/format characters from catalog text columns.
-- Targets: U+200B, U+200C, U+200D, U+FEFF, U+2060
-- Safe for MySQL and MariaDB (no REGEXP_REPLACE used).

SET @zwsp  := CONVERT(0xE2808B USING utf8mb4); -- U+200B
SET @zwnj  := CONVERT(0xE2808C USING utf8mb4); -- U+200C
SET @zwj   := CONVERT(0xE2808D USING utf8mb4); -- U+200D
SET @bom   := CONVERT(0xEFBBBF USING utf8mb4); -- U+FEFF
SET @wj    := CONVERT(0xE281A0 USING utf8mb4); -- U+2060

-- Books
UPDATE Books
SET
  title = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, ''),
  subtitle = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(subtitle, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, ''),
  series = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(series, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, ''),
  isbn = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(isbn, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, ''),
  lccn = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(lccn, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, ''),
  notes = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(notes, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, ''),
  cover_image = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cover_image, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, ''),
  cover_thumb = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cover_thumb, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, ''),
  loaned_to = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(loaned_to, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, '');

-- Authors
UPDATE Authors
SET
  name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, ''),
  first_name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(first_name, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, ''),
  last_name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(last_name, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, ''),
  sort_name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(sort_name, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, '');

-- Publishers
UPDATE Publishers
SET
  name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, '');

-- Subjects
UPDATE Subjects
SET
  name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, '');

-- duplicate_review notes (if you use them)
UPDATE duplicate_review
SET
  note = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(note, @zwsp, ''), @zwnj, ''), @zwj, ''), @bom, ''), @wj, '');

