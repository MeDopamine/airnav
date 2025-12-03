<?php
// Header partial for BULOG: page header including mobile menu button and profile area
?>
<!-- Load Font Awesome for icons used in sidebar/menu -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="<?php echo htmlspecialchars($flow_css); ?>">
<header class="relative flex items-center justify-between h-20 bg-white shadow-md px-4 sm:px-6 lg:px-8">
    <!-- Judul Halaman (tersembunyi di mobile) -->
    <?php
    // Determine a friendly page title matching the sidebar active item.
    $uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $current_base = basename($uri_path);
    $menu_map = [
        // bulog pages
        'dashboard.php' => 'Dashboard Super Admin',
    ];
    $header_title = $menu_map[$current_base] ?? 'Dashboard Super Admin';
    ?>
    <h1 class="sr-only"><?php echo htmlspecialchars($header_title); ?></h1>
    <h1 class="text-2xl font-semibold text-gray-800 hidden md:block">
        <?php echo htmlspecialchars($header_title); ?>
    </h1>

    <!-- Center clock card -->
    <div id="header-clock" class="absolute left-1/2 transform -translate-x-1/2 flex items-center gap-3 bg-white border border-gray-100 rounded-lg px-3 py-1 sm:px-4 sm:py-2 shadow-sm z-10">
        <i class="fa-regular fa-clock text-gray-600 mr-3" aria-hidden="true"></i>
        <div class="text-left">
            <div id="clock-time" class="text-sm font-medium text-gray-800">--:--:--</div>
            <div id="clock-date" class="text-xs text-gray-500">--</div>
        </div>
    </div>

    <!-- Profile Dropdown -->
    <div class="flex items-center">
        <?php
        // show current user if available
        if (function_exists('current_user')) {
            $u = current_user();
            $displayName = $u['name'] ?? ($u['email'] ?? 'User');
        } else {
            $displayName = 'User';
        }
        ?>
        <span class="mr-4 text-gray-700 hidden sm:block">Selamat datang, <?php echo htmlspecialchars($displayName); ?>!</span>
        <div class="relative">
            <a href="../../logout.php" title="Keluar" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                <img class="h-10 w-10 rounded-full object-cover" src="https://placehold.co/100x100/E0E7FF/4338CA?text=<?php echo strtoupper(substr($displayName,0,1)); ?>" alt="Avatar">
            </a>
        </div>
    </div>
    <script>
        (function(){
            function updateClock(){
                try {
                    var now = new Date();
                    // Format time manually to force colon separators (HH:MM:SS)
                    var hh = String(now.getHours()).padStart(2, '0');
                    var mm = String(now.getMinutes()).padStart(2, '0');
                    var ss = String(now.getSeconds()).padStart(2, '0');
                    var time = hh + ':' + mm + ':' + ss;
                    var date = now.toLocaleDateString('id-ID', {weekday: 'long', day: '2-digit', month: 'long', year: 'numeric'});
                    var t = document.getElementById('clock-time');
                    var d = document.getElementById('clock-date');
                    if (t) t.textContent = time;
                    if (d) d.textContent = date;
                } catch (e) {
                    // fail silently if Intl not available
                }
            }
            updateClock();
            if (!window._airnav_clock_interval) {
                window._airnav_clock_interval = setInterval(updateClock, 1000);
            }
        })();
    </script>
</header>
