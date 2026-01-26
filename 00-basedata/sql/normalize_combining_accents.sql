-- Normalize decomposed accents (combining marks) to precomposed characters (NFC)
-- Focused on common Latin letters and Hungarian accents.
-- Safe for MySQL and MariaDB (no REGEXP_REPLACE used).

SET collation_connection = 'utf8mb4_bin';

SET @c_acute   := CONVERT(0xCC81 USING utf8mb4) COLLATE utf8mb4_bin; -- U+0301
SET @c_grave   := CONVERT(0xCC80 USING utf8mb4) COLLATE utf8mb4_bin; -- U+0300
SET @c_circ    := CONVERT(0xCC82 USING utf8mb4) COLLATE utf8mb4_bin; -- U+0302
SET @c_tilde   := CONVERT(0xCC83 USING utf8mb4) COLLATE utf8mb4_bin; -- U+0303
SET @c_diaer   := CONVERT(0xCC88 USING utf8mb4) COLLATE utf8mb4_bin; -- U+0308
SET @c_double  := CONVERT(0xCC8B USING utf8mb4) COLLATE utf8mb4_bin; -- U+030B
SET @c_cedilla := CONVERT(0xCCA7 USING utf8mb4) COLLATE utf8mb4_bin; -- U+0327

-- Reusable updates for each mapping
-- Books: title/subtitle/series/notes/loaned_to
-- Authors: name/first_name/last_name/sort_name
-- Publishers/Subjects: name

SET @from := CONCAT('a', @c_acute); SET @to := 'á';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('e', @c_acute); SET @to := 'é';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('i', @c_acute); SET @to := 'í';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('o', @c_acute); SET @to := 'ó';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('u', @c_acute); SET @to := 'ú';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('o', @c_diaer); SET @to := 'ö';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('u', @c_diaer); SET @to := 'ü';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('o', @c_double); SET @to := 'ő';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('u', @c_double); SET @to := 'ű';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('a', @c_diaer); SET @to := 'ä';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('a', @c_grave); SET @to := 'à';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('e', @c_grave); SET @to := 'è';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('i', @c_grave); SET @to := 'ì';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('o', @c_grave); SET @to := 'ò';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('u', @c_grave); SET @to := 'ù';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('a', @c_circ); SET @to := 'â';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('e', @c_circ); SET @to := 'ê';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('i', @c_circ); SET @to := 'î';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('o', @c_circ); SET @to := 'ô';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('u', @c_circ); SET @to := 'û';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('a', @c_tilde); SET @to := 'ã';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('o', @c_tilde); SET @to := 'õ';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('n', @c_tilde); SET @to := 'ñ';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('c', @c_cedilla); SET @to := 'ç';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('y', @c_acute); SET @to := 'ý';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('y', @c_diaer); SET @to := 'ÿ';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('A', @c_acute); SET @to := 'Á';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('E', @c_acute); SET @to := 'É';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('I', @c_acute); SET @to := 'Í';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('O', @c_acute); SET @to := 'Ó';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('U', @c_acute); SET @to := 'Ú';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('O', @c_diaer); SET @to := 'Ö';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('U', @c_diaer); SET @to := 'Ü';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('O', @c_double); SET @to := 'Ő';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('U', @c_double); SET @to := 'Ű';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('A', @c_diaer); SET @to := 'Ä';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('A', @c_grave); SET @to := 'À';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('E', @c_grave); SET @to := 'È';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('I', @c_grave); SET @to := 'Ì';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('O', @c_grave); SET @to := 'Ò';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('U', @c_grave); SET @to := 'Ù';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('A', @c_circ); SET @to := 'Â';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('E', @c_circ); SET @to := 'Ê';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('I', @c_circ); SET @to := 'Î';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('O', @c_circ); SET @to := 'Ô';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('U', @c_circ); SET @to := 'Û';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('A', @c_tilde); SET @to := 'Ã';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('O', @c_tilde); SET @to := 'Õ';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('N', @c_tilde); SET @to := 'Ñ';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('C', @c_cedilla); SET @to := 'Ç';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('Y', @c_acute); SET @to := 'Ý';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

SET @from := CONCAT('Y', @c_diaer); SET @to := 'Ÿ';
UPDATE Books SET title = REPLACE(title, @from, @to), subtitle = REPLACE(subtitle, @from, @to), series = REPLACE(series, @from, @to), notes = REPLACE(notes, @from, @to), loaned_to = REPLACE(loaned_to, @from, @to);
UPDATE Authors SET name = REPLACE(name, @from, @to), first_name = REPLACE(first_name, @from, @to), last_name = REPLACE(last_name, @from, @to), sort_name = REPLACE(sort_name, @from, @to);
UPDATE Publishers SET name = REPLACE(name, @from, @to);
UPDATE Subjects SET name = REPLACE(name, @from, @to);

