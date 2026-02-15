<?php

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This installer must be run from the CLI.\n");
    exit(1);
}

$root = __DIR__;
$config_path = $root . '/config.php';
$template_path = $root . '/config.sample.php';
$schema_path = $root . '/00-basedata/sql/schema.sql';

function fail(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function prompt(string $label, ?string $default = null): string {
    $suffix = $default !== null ? " [{$default}]" : '';
    fwrite(STDOUT, $label . $suffix . ': ');
    $line = fgets(STDIN);
    if ($line === false) {
        return $default ?? '';
    }
    $line = trim($line);
    if ($line === '' && $default !== null) {
        return $default;
    }
    return $line;
}

function prompt_hidden(string $label): string {
    if (stripos(PHP_OS, 'WIN') === 0) {
        return prompt($label);
    }

    fwrite(STDOUT, $label . ': ');
    $stty = shell_exec('stty -g');
    shell_exec('stty -echo');
    $line = fgets(STDIN);
    if ($stty !== null) {
        shell_exec('stty ' . $stty);
    }
    fwrite(STDOUT, "\n");

    if ($line === false) {
        return '';
    }
    return trim($line);
}

function password_policy_rules(string $username = ''): array {
    return [
        [
            'message' => 'Password must be at least 12 characters.',
            'check' => static fn (string $password): bool => strlen($password) >= 12,
        ],
        [
            'message' => 'Password must include a lowercase letter.',
            'check' => static fn (string $password): bool => (bool)preg_match('/[a-z]/', $password),
        ],
        [
            'message' => 'Password must include an uppercase letter.',
            'check' => static fn (string $password): bool => (bool)preg_match('/[A-Z]/', $password),
        ],
        [
            'message' => 'Password must include a digit.',
            'check' => static fn (string $password): bool => (bool)preg_match('/[0-9]/', $password),
        ],
        [
            'message' => 'Password must include a special character.',
            'check' => static fn (string $password): bool => (bool)preg_match('/[^a-zA-Z0-9]/', $password),
        ],
        [
            'message' => 'Password cannot contain the username.',
            'check' => static fn (string $password): bool => $username === '' || stripos($password, $username) === false,
        ],
    ];
}

function password_policy_messages(string $username = ''): array {
    return array_map(
        static fn (array $rule): string => $rule['message'],
        password_policy_rules($username)
    );
}

function password_policy_errors(string $password, string $username = ''): array {
    $errors = [];
    foreach (password_policy_rules($username) as $rule) {
        if (!($rule['check'])($password)) {
            $errors[] = $rule['message'];
        }
    }
    return $errors;
}

function split_sql_statements(string $sql): array {
    $statements = [];
    $buffer = '';

    foreach (preg_split('/\r?\n/', $sql) as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--')) {
            continue;
        }
        $buffer .= $line . "\n";
        if (preg_match('/;\s*$/', $line)) {
            $statements[] = trim($buffer);
            $buffer = '';
        }
    }

    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }

    return $statements;
}

if (file_exists($config_path)) {
    fail('config.php already exists. Aborting to avoid overwriting credentials.');
}

$min_php = 80000;
if (PHP_VERSION_ID < $min_php) {
    fail('PHP 8.0+ is required for this installer.');
}

$required_ext = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
$missing_ext = [];
foreach ($required_ext as $ext) {
    if (!extension_loaded($ext)) {
        $missing_ext[] = $ext;
    }
}
if ($missing_ext) {
    fail('Missing required PHP extensions: ' . implode(', ', $missing_ext));
}

function clean_directory(string $dir): void {
    $items = scandir($dir);
    if ($items === false) {
        fail("Unable to read directory: {$dir}");
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            clean_directory($path);
            if (!rmdir($path)) {
                fail("Failed to remove directory: {$path}");
            }
        } else {
            if (!unlink($path)) {
                fail("Failed to remove file: {$path}");
            }
        }
    }
}

function ensure_writable_dir(string $dir): void {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true)) {
            fail("Failed to create directory: {$dir}");
        }
    } else {
        $confirm = strtolower(prompt("Directory {$dir} exists. Clean contents? (y/N)", 'n'));
        if (in_array($confirm, ['y', 'yes'], true)) {
            clean_directory($dir);
        }
    }

    if (!is_writable($dir)) {
        fail("Directory is not writable: {$dir}");
    }
}

$writable_dirs = [
    $root . '/public/uploads',
    $root . '/public/user-assets',
];
foreach ($writable_dirs as $dir) {
    ensure_writable_dir($dir);
}

if (!is_writable($root)) {
    fail('Project root is not writable; cannot create config.php.');
}

if (!is_readable($template_path)) {
    fail('Missing config.sample.php template.');
}

if (!is_readable($schema_path)) {
    fail('Missing schema.sql at 00-basedata/sql/schema.sql.');
}

$host = prompt('MySQL host', '127.0.0.1');
$port = prompt('MySQL port', '3306');
$admin_user = prompt('MySQL admin user');
$admin_pass = prompt_hidden('MySQL admin password');

if ($admin_user === '') {
    fail('MySQL admin user is required.');
}

try {
    $admin_dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, (int)$port);
    $admin_pdo = new PDO($admin_dsn, $admin_user, $admin_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fail('Unable to connect to MySQL with admin credentials: ' . $e->getMessage());
}

$dbname = prompt('Catalog database name', 'books');
if (!preg_match('/^[A-Za-z0-9_]+$/', $dbname)) {
    fail('Database name must contain only letters, numbers, and underscores.');
}

$st = $admin_pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
$st->execute([$dbname]);
$exists = (bool)$st->fetchColumn();

if ($exists) {
    fwrite(STDOUT, "Database '{$dbname}' already exists.\n");
    $reuse = strtolower(prompt('Reuse existing database? (y/N)', 'n'));
    if (!in_array($reuse, ['y', 'yes'], true)) {
        fail('Aborting at user request.');
    }
} else {
    try {
        $admin_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (Throwable $e) {
        fail('Failed to create database: ' . $e->getMessage());
    }
}

try {
    $db_dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, (int)$port, $dbname);
    $db_pdo = new PDO($db_dsn, $admin_user, $admin_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fail('Unable to connect to the target database: ' . $e->getMessage());
}

$schema_sql = file_get_contents($schema_path);
if ($schema_sql === false) {
    fail('Failed to read schema.sql.');
}

try {
    $statements = split_sql_statements($schema_sql);
    foreach ($statements as $stmt) {
        if ($stmt !== '') {
            $db_pdo->exec($stmt);
        }
    }
} catch (Throwable $e) {
    fail('Schema creation failed: ' . $e->getMessage());
}

$app_version = '2.4.1 (installer build)';
$schema_version = '2.2.0';
$install_date = gmdate('c');

try {
    $info_stmt = $db_pdo->prepare(
        "INSERT INTO SystemInfo (key_name, value) VALUES (?, ?)\n"
        . "ON DUPLICATE KEY UPDATE value = VALUES(value)"
    );
    $info_stmt->execute(['app_version', $app_version]);
    $info_stmt->execute(['schema_version', $schema_version]);
    $info_stmt->execute(['install_date', $install_date]);
} catch (Throwable $e) {
    fail('Failed to populate SystemInfo: ' . $e->getMessage());
}

$use_dedicated = strtolower(prompt('Create dedicated DB user for BookCatalog? (Y/n)', 'y'));
$create_dedicated = !in_array($use_dedicated, ['n', 'no'], true);

if ($create_dedicated) {
    $app_db_user = prompt('BookCatalog DB username', 'bookcatalog_app');
    if ($app_db_user === '') {
        fail('Database username cannot be empty.');
    }

    $app_db_host = prompt('BookCatalog DB user host', $host);
    fwrite(STDOUT, "BookCatalog DB user password requirements:\n");
    foreach (password_policy_messages($app_db_user) as $message) {
        fwrite(STDOUT, "- {$message}\n");
    }
    fwrite(STDOUT, "\n");
    $app_db_pass = prompt_hidden('BookCatalog DB user password');
    $app_db_pass_confirm = prompt_hidden('Confirm BookCatalog DB user password');
    if ($app_db_pass === '' || $app_db_pass !== $app_db_pass_confirm) {
        fail('DB user passwords do not match or are empty.');
    }

    try {
        $user_q = $admin_pdo->quote($app_db_user);
        $pass_q = $admin_pdo->quote($app_db_pass);
        $user_hosts = array_unique([$app_db_host, $host]);
        foreach ($user_hosts as $user_host) {
            $host_q = $admin_pdo->quote($user_host);
            $admin_pdo->exec("CREATE USER IF NOT EXISTS {$user_q}@{$host_q} IDENTIFIED BY {$pass_q}");
            $admin_pdo->exec(
                "GRANT SELECT, INSERT, UPDATE, DELETE ON `{$dbname}`.* TO {$user_q}@{$host_q}"
            );
        }
        $admin_pdo->exec('FLUSH PRIVILEGES');
    } catch (Throwable $e) {
        fail('Failed to create/grant DB user: ' . $e->getMessage());
    }
} else {
    fwrite(STDOUT, "Warning: Using admin credentials for the app is not recommended.\n");
    $app_db_user = $admin_user;
    $app_db_pass = $admin_pass;
}

$admin_username = '';
while ($admin_username === '') {
    $admin_username = prompt('Initial catalog admin username');
    if ($admin_username === '') {
        fwrite(STDOUT, "Catalog admin username is required.\n");
        continue;
    }

    $check = $db_pdo->prepare('SELECT user_id FROM Users WHERE username = ? LIMIT 1');
    $check->execute([$admin_username]);
    if ($check->fetchColumn()) {
        fwrite(STDOUT, "That username already exists. Choose another.\n");
        $admin_username = '';
    }
}

$admin_password = '';
fwrite(STDOUT, "Password requirements:\n");
foreach (password_policy_messages($admin_username) as $message) {
    fwrite(STDOUT, "- {$message}\n");
}
fwrite(STDOUT, "\n");
while ($admin_password === '') {
    $admin_password = prompt_hidden('Initial catalog admin password');
    $admin_password_confirm = prompt_hidden('Confirm catalog admin password');

    if ($admin_password === '' || $admin_password !== $admin_password_confirm) {
        fwrite(STDOUT, "Passwords do not match.\n");
        $admin_password = '';
        continue;
    }

    $errors = password_policy_errors($admin_password, $admin_username);
    if ($errors) {
        fwrite(STDOUT, "Password policy errors:\n");
        foreach ($errors as $err) {
            fwrite(STDOUT, "- {$err}\n");
        }
        $admin_password = '';
    }
}

$hash = password_hash($admin_password, PASSWORD_DEFAULT);
if ($hash === false) {
    fail('Failed to hash admin password.');
}

try {
    $insert_user = $db_pdo->prepare(
        'INSERT INTO Users (username, password_hash, role, is_active, force_password_change, created_at) '
        . 'VALUES (?, ?, \'admin\', 1, 0, NOW())'
    );
    $insert_user->execute([$admin_username, $hash]);
    $admin_user_id = (int)$db_pdo->lastInsertId();

    $prefs_insert = $db_pdo->prepare(
        'INSERT INTO UserPreferences '
        . '(user_id, logo_path, bg_color, fg_color, text_size, per_page, '
        . 'show_cover, show_subtitle, show_series, show_is_hungarian, show_publisher, '
        . 'show_year, show_status, show_placement, show_isbn, show_loaned_to, '
        . 'show_loaned_date, show_subjects, updated_at) '
        . 'VALUES (?, NULL, NULL, NULL, \'medium\', 25, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, NOW()) '
        . 'ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)'
    );
    $prefs_insert->execute([$admin_user_id]);
} catch (Throwable $e) {
    fail('Failed to create initial admin user: ' . $e->getMessage());
}

$template = file_get_contents($template_path);
if ($template === false) {
    fail('Unable to read config.sample.php.');
}

$replacements = [
    'DB_HOST' => $host,
    'DB_PORT' => (string)$port,
    'DB_NAME' => $dbname,
    'DB_USER' => $app_db_user,
    'DB_PASS' => $app_db_pass,
];

$config_body = str_replace(array_keys($replacements), array_values($replacements), $template);
if (file_put_contents($config_path, $config_body) === false) {
    fail('Failed to write config.php.');
}

@chmod($config_path, 0600);

try {
    $app_dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, (int)$port, $dbname);
    $app_pdo = new PDO($app_dsn, $app_db_user, $app_db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $table_check = $app_pdo->query("SHOW TABLES LIKE 'Users'")->fetchColumn();
    if (!$table_check) {
        fail('Final check failed: Users table is missing.');
    }
    $user_check = $app_pdo->prepare('SELECT user_id FROM Users WHERE username = ? LIMIT 1');
    $user_check->execute([$admin_username]);
    if (!$user_check->fetchColumn()) {
        fail('Final check failed: admin user not found.');
    }
} catch (Throwable $e) {
    fail('Final check failed: ' . $e->getMessage());
}

fwrite(STDOUT, "\nInstallation complete.\n\n");
fwrite(STDOUT, "Login at:\nhttps://yourhost/\n\n");
fwrite(STDOUT, "Catalog admin user:\n{$admin_username}\n\n");
fwrite(STDOUT, "Next steps:\n");
fwrite(STDOUT, "- cd frontend && npm install && npm run build\n");
fwrite(STDOUT, "- Configure your Apache/Nginx vhost to point to public/\n");
fwrite(STDOUT, "- Ensure public/uploads and public/user-assets are writable\n");
