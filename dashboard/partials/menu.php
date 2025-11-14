<?php
/*
 * Simple menu renderer.
 * Usage: call via render_partial('menu', ['mobile' => true/false]) or include directly with $mobile set.
 */
$mobile = isset($mobile) ? (bool)$mobile : false;

$menuItems = [
    [
        'label' => 'Dashboard',
        'href' => 'index.php',
        'icon' => '<i class="fa-solid fa-chart-pie mr-3" aria-hidden="true"></i>'
    ],
    [
        'label' => 'Data Peserta',
        'href' => 'data_peserta.php',
        'icon' => '<i class="fa-solid fa-users mr-3" aria-hidden="true"></i>'
    ],
    [
        'label' => 'Manajemen Invoice',
        'href' => 'manajemen_invoice.php',
        'icon' => '<i class="fa-solid fa-file-invoice mr-3" aria-hidden="true"></i>'
    ],
    [
        'label' => 'Manajemen Peserta',
        'href' => 'manajemen_peserta.php',
        'icon' => '<i class="fa-solid fa-user-cog mr-3" aria-hidden="true"></i>'
    ],
];

foreach ($menuItems as $idx => $item) {
    // determine active state: explicit active flag or match current script name to href
    $current = basename($_SERVER['SCRIPT_NAME']);
    $isActive = !empty($item['active']) || (isset($item['href']) && basename($item['href']) === $current);
    $baseClasses = $isActive
        ? 'flex items-center px-4 py-3 bg-blue-600 text-white rounded-lg font-medium'
        : 'flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg';
    // add top margin for non-first items on desktop/mobile
    $mt = $idx === 0 ? '' : ' mt-3';

    // icon: prefer provided icon, fallback to a tiny generic one
    $icon = isset($item['icon']) ? $item['icon'] : '<svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>';

    echo '<a href="' . htmlspecialchars($item['href']) . '" class="' . $baseClasses . $mt . '">';
    echo $icon;
    echo htmlspecialchars($item['label']);
    echo '</a>' . PHP_EOL;
}

?>
