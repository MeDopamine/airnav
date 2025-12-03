<?php
// Sidebar partial (desktop + mobile). Uses menu.php for nav items.

?>
<!-- Sidebar (Hidden on mobile, visible on desktop) -->
<aside id="sidebar" class="hidden md:flex w-64 flex-col bg-gray-900 text-white">
    <div class="flex items-center justify-center h-20 border-b border-gray-700">
        <i class="fa-solid fa-square-poll-horizontal text-2xl"></i>
        <span class="ml-3 text-2xl font-semibold">AirNav</span>
    </div>
    <nav class="flex-1 overflow-y-auto px-4 py-6" tabindex="0">
        <?php
        // render menu (desktop) via helper if available
        if (function_exists('render_partial')) {
            render_partial('menu', ['mobile' => false]);
        } else {
            $mobile = false;
            include __DIR__ . '/menu.php';
        }
        ?>
    </nav>
    <div class="p-4 border-t border-gray-700">
        <?php if (is_superadmin()): ?>
            <a href="/dashboard/superadmin/dashboard.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
                <i class="fa-solid fa-right-from-bracket text-lg mr-2"></i>
                <h style="font-size: 14px;">Dashboard Superadmin</h>
            </a>
        <?php endif; ?>
        <?php if (is_admin() || is_admintl()): ?>
            <a href="/logout.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
                <i class="fa-solid fa-right-from-bracket text-lg mr-2"></i>
                Keluar
            </a>
        <?php endif; ?>
    </div>
</aside>

<!-- Sidebar Mobile (Initially hidden) -->
<div id="mobile-menu" class="fixed inset-0 z-30 flex md:hidden transform -translate-x-full transition-transform duration-300 ease-in-out opacity-0" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="relative w-64 max-w-xs flex flex-col bg-gray-900 text-white" role="document" tabindex="-1">
        <div class="flex items-center justify-center h-20 border-b border-gray-700">
            <i class="fa-solid fa-square-poll-horizontal text-2xl"></i>
            <span class="ml-3 text-2xl font-semibold">AirNav</span>
        </div>
        <nav class="flex-1 overflow-y-auto px-4 py-6">
            <?php
            // render menu (mobile) via helper if available
            if (function_exists('render_partial')) {
                render_partial('menu', ['mobile' => true]);
            } else {
                $mobile = true;
                include __DIR__ . '/menu.php';
            }
            ?>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <?php if (is_superadmin()): ?>
                <a href="/dashboard/superadmin/dashboard.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
                    Dashboard Superadmin
                </a>
            <?php endif; ?>
            <?php if (is_admin() || is_admintl()): ?>
                <a href="/logout.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
                    Keluar
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
