<?php

use Illuminate\Support\Str;

/**
 * MySQL / MariaDB PDO SSL options driven entirely by .env so you can switch
 * local ↔ Aiven ↔ another host without changing application code.
 *
 * Env knobs:
 * - DB_URL                  Optional full DSN (overrides host/port/user/pass/db when set)
 * - DB_SSL=true             Require TLS (needed for Aiven and most managed MySQL)
 * - MYSQL_ATTR_SSL_CA=path  Optional CA bundle (Aiven "Download CA certificate")
 * - DB_SSL_VERIFY=false     Skip server-cert verify when you have no CA file
 */
$mysqlSslOptions = static function (): array {
    if (! extension_loaded('pdo_mysql')) {
        return [];
    }

    $sslCaConstant = defined('Pdo\\Mysql::ATTR_SSL_CA')
        ? constant('Pdo\\Mysql::ATTR_SSL_CA')
        : PDO::MYSQL_ATTR_SSL_CA;

    $sslVerifyConstant = defined('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')
        ? constant('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')
        : PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT;

    $ca = env('MYSQL_ATTR_SSL_CA', env('DB_SSL_CA'));
    $wantSsl = filter_var(env('DB_SSL', false), FILTER_VALIDATE_BOOLEAN) || filled($ca);

    if (! $wantSsl) {
        return [];
    }

    $options = [
        // Empty-string / true CA still turns TLS on for managed MySQL (Aiven, etc.)
        $sslCaConstant => filled($ca) ? (string) $ca : true,
    ];

    $verify = env('DB_SSL_VERIFY', env('MYSQL_ATTR_SSL_VERIFY_SERVER_CERT'));
    if ($verify !== null && $verify !== '') {
        $options[$sslVerifyConstant] = filter_var($verify, FILTER_VALIDATE_BOOLEAN);
    } elseif (! filled($ca)) {
        $options[$sslVerifyConstant] = false;
    }

    return $options;
};

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Switch providers by changing .env only:
    |   DB_CONNECTION=mysql|mariadb|pgsql|sqlite
    |   DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD
    |   or a single DB_URL=mysql://user:pass@host:port/db
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'sns_erp_db'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => $mysqlSslOptions(),
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'sns_erp_db'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => $mysqlSslOptions(),
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'sns_erp_db'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', env('DB_SSL') ? 'require' : 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'sns_erp_db'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
        ],

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];
