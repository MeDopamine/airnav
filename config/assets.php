<?php
// Centralized asset config for third-party libraries (CDN fallback to local)
// Edit these paths to point to local copies if you vendor Flowbite into /dashboard/assets/vendor/

$ASSETS = [
    'flowbite_css_local' => '/dashboard/assets/vendor/flowbite/flowbite.min.css',
    'flowbite_js_local'  => '/dashboard/assets/vendor/flowbite/datepicker.min.js',
    'flowbite_css_cdn'   => 'https://cdn.jsdelivr.net/npm/flowbite@1.7.0/dist/flowbite.min.css',
    'flowbite_js_cdn'    => 'https://cdn.jsdelivr.net/npm/flowbite@1.7.0/dist/datepicker.min.js',
    // asset version for cache-busting; bump this to force clients to reload local/CDN assets
    'version'            => '1.7.0',
];

if (!function_exists('get_asset_url')) {
    /**
     * Return a URL for an asset preferring a local file if present on disk, otherwise fallback to CDN URL.
     * @param string $localPath Absolute-path relative to document root (e.g. /dashboard/assets/...)
     * @param string $cdnUrl Full CDN URL
     * @return string
     */
    function get_asset_url($localPath, $cdnUrl)
    {
        // Map local path to filesystem using DOCUMENT_ROOT
        if (!empty($localPath) && isset($_SERVER['DOCUMENT_ROOT'])) {
            $fs = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . str_replace('/', DIRECTORY_SEPARATOR, $localPath);
            if (file_exists($fs)) {
                // append version query for cache-busting
                $ver = '';
                if (!empty($GLOBALS['ASSETS']['version'])) $ver = $GLOBALS['ASSETS']['version'];
                if ($ver) {
                    return $localPath . (strpos($localPath, '?') === false ? '?v=' . rawurlencode($ver) : '&v=' . rawurlencode($ver));
                }
                return $localPath;
            }
        }
        // fallback to CDN, append version if configured
        $ver = '';
        if (!empty($GLOBALS['ASSETS']['version'])) $ver = $GLOBALS['ASSETS']['version'];
        if ($ver) {
            return $cdnUrl . (strpos($cdnUrl, '?') === false ? '?v=' . rawurlencode($ver) : '&v=' . rawurlencode($ver));
        }
        return $cdnUrl;
    }
}

?>