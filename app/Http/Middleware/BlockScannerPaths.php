<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockScannerPaths
{
    /**
     * Path prefixes targeted by scanners.
     */
    protected array $blockedPrefixes = [
        // WordPress
        'wp-admin',
        'wp-login',
        'wp-includes',
        'wp-content',
        'wp-config',
        'wp-cron',
        'xmlrpc',

        // Other CMS
        'administrator',
        'joomla',
        'drupal',
        'sites/default',
        'typo3',
        'magento',
        'bitrix',

        // Database tools
        'phpmyadmin',
        'pma',
        'myadmin',
        'adminer',
        'mysql',
        'dbadmin',

        // Dev/config paths
        'vendor/',
        'node_modules',
        'cgi-bin',
        'aws',
        '.aws',

        // Backups
        'backup',
        'backups',
        'dump',
        'export',
    ];

    /**
     * Exact filenames or extensions to block.
     */
    protected array $blockedFiles = [
        '.env',
        '.git',
        '.svn',
        '.htaccess',
        '.htpasswd',
        '.DS_Store',
        'web.config',
        'config.php',
        'configuration.php',
        'settings.php',
        'install.php',
        'setup.php',
        'phpinfo.php',
        'info.php',
        'shell.php',
        'c99.php',
        'r57.php',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json',
        '.sql',
        '.bak',
        '.old',
        '.zip',
        '.tar',
        '.gz',
        '.rar',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = strtolower($request->path());

        // Check path prefixes
        foreach ($this->blockedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix) || str_contains($path, '/' . $prefix)) {
                abort(404);
            }
        }

        // Check filenames and extensions
        foreach ($this->blockedFiles as $file) {
            if (str_ends_with($path, $file) || str_contains($path, '/' . $file)) {
                abort(404);
            }
        }

        return $next($request);
    }
}
