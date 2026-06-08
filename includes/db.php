<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pawhouse_adoption');
define('DB_USER', 'root');
define('DB_PASS', '');         
define('DB_CHARSET', 'utf8mb4');
function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        die('<div style="font-family:sans-serif;padding:40px;color:#c92a2a;">'
            . '<h2>Database connection failed</h2>'
            . '<p>' . htmlspecialchars($e->getMessage()) . '</p>'
            . '<p>Make sure MySQL is running and the <code>pawhouse_adoption</code> database is imported.</p>'
            . '</div>');
    }
    return $pdo;
}
