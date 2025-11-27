<?php
// Header partial for Petrokimia: page header including mobile menu button and profile area
?>
<!-- Load Font Awesome for icons used in sidebar/menu -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="<?php echo htmlspecialchars($flow_css); ?>">
<header class="relative flex items-center justify-between h-20 bg-white shadow-md px-4 sm:px-6 lg:px-8">
    <!-- Tombol Menu Mobile -->
    <button id="open-menu-btn" class="text-gray-500 focus:outline-none focus:text-gray-700 md:hidden" aria-controls="mobile-menu" aria-expanded="false">
        <span class="sr-only">Buka sidebar</span>
        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>

    <!-- Judul Halaman (tersembunyi di mobile) -->
    <?php
    // Determine a friendly page title matching the sidebar active item.
    $uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $current_base = basename($uri_path);
    $menu_map = [
        // petrokimia pages
        'data_peserta.php' => 'Data Peserta',
    ];
    $header_title = $menu_map[$current_base] ?? 'Dashboard Petrokimia';
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

<script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('open-menu-btn');
        var menu = document.getElementById('mobile-menu');
        var overlay = menu ? menu.previousElementSibling : null;
        if (btn && menu) {
            btn.addEventListener('click', function() {
                var expanded = btn.getAttribute('aria-expanded') === 'true';
                btn.setAttribute('aria-expanded', !expanded);
                if (menu.classList.contains('-translate-x-full')) {
                    menu.classList.remove('-translate-x-full');
                    menu.classList.add('translate-x-0');
                    menu.classList.remove('opacity-0');
                    menu.classList.add('opacity-100');
                    if (overlay) {
                        overlay.classList.remove('opacity-0', 'pointer-events-none');
                        overlay.classList.add('opacity-100');
                    }
                } else {
                    menu.classList.add('-translate-x-full');
                    menu.classList.remove('translate-x-0');
                    menu.classList.add('opacity-0');
                    menu.classList.remove('opacity-100');
                    if (overlay) {
                        overlay.classList.add('opacity-0', 'pointer-events-none');
                        overlay.classList.remove('opacity-100');
                    }
                }
            });
        }
    });
</script>
