<?php
// User-specific menu renderer for sidebar
$mobile = isset($mobile) ? (bool)$mobile : false;

$menuItems = [
    [
        'label' => 'Dashboard',
        'href' => 'dashboard.php',
        'icon' => '<i class="fa-solid fa-house mr-3" aria-hidden="true"></i>'
    ],
    [
        'label' => 'Riwayat Invoice',
        'href' => 'riwayat_invoice.php',
        'icon' => '<i class="fa-solid fa-upload mr-3" aria-hidden="true"></i>'
    ],
    [
        'label' => 'Profil Saya',
        'href' => 'profile.php',
        'icon' => '<i class="fa-solid fa-user mr-3" aria-hidden="true"></i>'
    ],    
    [
        'label' => 'Bantuan',
        'href' => '#',
        'icon' => '<i class="fa-solid fa-circle-question mr-3" aria-hidden="true"></i>'
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
            'profile.php' => ['edit_profile.php'],
        ];
        if (isset($aliases[$itemBasename]) && in_array($current, $aliases[$itemBasename], true)) {
            $isActive = true;
        }
    }
    $baseClasses = $isActive
        ? 'flex items-center px-4 py-3 bg-blue-600 text-white rounded-lg font-medium'
        : 'flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg';
    $mt = $idx === 0 ? '' : ' mt-3';
    $icon = isset($item['icon']) ? $item['icon'] : '';

    echo '<a href="' . htmlspecialchars($item['href']) . '" class="' . $baseClasses . $mt . '">';
    echo $icon;
    echo htmlspecialchars($item['label']);
    echo '</a>' . PHP_EOL;
}

?>
