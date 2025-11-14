<?php
// Sidebar partial for regular users. Similar layout to admin sidebar but uses user menu.
?>
<!-- Sidebar (Hidden on mobile, visible on desktop) -->
<aside id="sidebar" class="hidden md:flex w-64 flex-col bg-gray-900 text-white">
    <div class="flex items-center justify-center h-20 border-b border-gray-700">
        <svg class="h-8 w-auto text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
        </svg>
        <span class="ml-3 text-2xl font-semibold">AirNav</span>
    </div>
    <nav class="flex-1 overflow-y-auto px-4 py-6" tabindex="0">
        <?php
        if (function_exists('render_partial')) {
            render_partial('menu_user', ['mobile' => false]);
        } else {
            $mobile = false;
            include __DIR__ . '/menu_user.php';
        }
        ?>
    </nav>
    <div class="p-4 border-t border-gray-700">
        <a href="/logout.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
            Keluar
        </a>
    </div>
</aside>

<!-- Sidebar Mobile (Initially hidden) -->
<div id="mobile-menu" class="fixed inset-0 z-30 flex md:hidden transform -translate-x-full transition-transform duration-300 ease-in-out transition-opacity duration-200 opacity-0" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="relative w-64 max-w-xs flex flex-col bg-gray-900 text-white" role="document" tabindex="-1">
        <div class="flex items-center justify-center h-20 border-b border-gray-700">
            <svg class="h-8 w-auto text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
            </svg>
            <span class="ml-3 text-2xl font-semibold">AirNav</span>
        </div>
        <nav class="flex-1 overflow-y-auto px-4 py-6">
            <?php
            if (function_exists('render_partial')) {
                render_partial('menu_user', ['mobile' => true]);
            } else {
                $mobile = true;
                include __DIR__ . '/menu_user.php';
            }
            ?>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <a href="/logout.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
                Keluar
            </a>
        </div>
    </div>
</div>
