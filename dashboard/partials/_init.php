<?php
// Partials loader helper
// Provides a light helper render_partial(name, vars)

if (!function_exists('render_partial')) {
    function render_partial(string $name, array $vars = [])
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . $name . '.php';
        if (!file_exists($path)) {
            trigger_error("Partial not found: $path", E_USER_WARNING);
            return;
        }
        // extract variables for the partial, but don't overwrite existing variables
        if (!empty($vars)) {
            extract($vars, EXTR_SKIP);
        }
        include $path;
    }
}

// Load global asset config if available
$assets_config_path = __DIR__ . '/../../config/assets.php';
if (file_exists($assets_config_path)) {
    include_once $assets_config_path;
}

?>
