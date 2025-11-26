<?php
// Menu item renderer for BULOG admin sidebar
$mobile = isset($mobile) ? (bool)$mobile : false;

$menuItems = [
    [
        'label' => 'Data Peserta',
        'href' => 'data_peserta.php',
        'icon' => '<i class="fa-solid fa-users" aria-hidden="true"></i>'
    ],   
];

foreach ($menuItems as $idx => $item) {
    // Determine active menu item robustly using the request URI path
    $uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $current = basename($uri_path);
    $itemBasename = isset($item['href']) ? basename(parse_url($item['href'], PHP_URL_PATH)) : '';
    // Consider some pages as aliases (e.g. edit_profile.php should keep 'Profil Saya' active)
    $isActive = false;
    if (!empty($item['active'])) {
        $isActive = true;
    } elseif ($itemBasename !== '' && $itemBasename === $current) {
        $isActive = true;
    } else {
        // alias mapping: when on edit_profile.php, highlight profile.php
        $aliases = [
            'data_peserta.php' => ['data_peserta.php'],
        ];
        if (isset($aliases[$itemBasename]) && in_array($current, $aliases[$itemBasename], true)) {
            $isActive = true;
        }
    }
    $baseClasses = $isActive
        ? 'nav-item flex items-center px-4 py-3 bg-blue-600 text-white rounded-lg font-medium transition-all'
        : 'nav-item flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg transition-all';
    $mt = $idx === 0 ? '' : ' mt-3';
    $icon = isset($item['icon']) ? $item['icon'] : '';

    echo '<a href="' . htmlspecialchars($item['href']) . '" class="' . $baseClasses . $mt . '">';
    echo $icon;
    echo '<span class="nav-item-text">' . htmlspecialchars($item['label']) . '</span>';
    echo '</a>' . PHP_EOL;
}

?>
