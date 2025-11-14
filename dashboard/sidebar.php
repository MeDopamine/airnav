<?php
// Sidebar markup (desktop + mobile) extracted from index.php
?>
<!-- Sidebar (Hidden on mobile, visible on desktop) -->
<aside id="sidebar" class="hidden md:flex w-64 flex-col bg-gray-900 text-white">
    <div class="flex items-center justify-center h-20 border-b border-gray-700">
        <svg class="h-8 w-auto text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
        </svg>
        <span class="ml-3 text-2xl font-semibold">AirNav</span>
    </div>
    <nav class="flex-1 overflow-y-auto px-4 py-6">
        <a href="#" class="flex items-center px-4 py-3 bg-blue-600 text-white rounded-lg font-medium">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25A2.25 2.25 0 0113.5 8.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
            Dashboard
        </a>
        <a href="#" class="flex items-center px-4 py-3 mt-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A9.06 9.06 0 016 18.719m12 0a9.049 9.049 0 00-12 0m12 0c.058 0 .113.003.169.005L18 18.72zM12 9a3 3 0 100-6 3 3 0 000 6z" />
            </svg>
            Data Peserta
        </a>
        <a href="#" class="flex items-center px-4 py-3 mt-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.08.823.24 1.205C8.16 6.472 8.5 6.842 8.8 7.314A2.69 2.69 0 019 9.5V12m6.375 0l-3.75-3.75M12.75 12l3.75 3.75M6.375 12l3.75 3.75m-3.75-3.75l3.75-3.75" />
            </svg>
            Manajemen Invoice
        </a>
        <a href="#" class="flex items-center px-4 py-3 mt-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-1.007 1.11-1.226.55-.22 1.157-.22 1.707 0 .55.22 1.02.684 1.11 1.226M9.594 3.94C9.54 4.568 9.5 5.223 9.5 5.91c0 .9.09 1.785.26 2.623m0 0c.264.814.666 1.56 1.157 2.188V13.875M9.76 8.533c.264.814.666 1.56 1.157 2.188m3.364 3.154c.49-.628.892-1.374 1.157-2.188m0 0c.17-.838.26-1.723.26-2.623 0-.687-.04-1.342-.096-1.97M14.406 3.94c.09-.542.56-1.007 1.11-1.226.55-.22 1.157-.22 1.707 0 .55.22 1.02.684 1.11 1.226m-1.707 0c-.056.628-.096 1.283-.096 1.97 0 .9.09 1.785.26 2.623m0 0c.264.814.666 1.56 1.157 2.188V13.875m-3.364 3.154c.49-.628.892-1.374 1.157-2.188m0 0c.17-.838.26-1.723.26-2.623 0-.687-.04-1.342-.096-1.97M12 15.75a3 3 0 100-6 3 3 0 000 6z" />
            </svg>
            Pengaturan
        </a>
    </nav>
    <div class="p-4 border-t border-gray-700">
        <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
            Keluar
        </a>
    </div>
</aside>

<!-- Sidebar Mobile (Initially hidden) -->
<div id="mobile-menu" class="fixed inset-0 z-30 flex md:hidden bg-gray-900 bg-opacity-75 transform -translate-x-full">
    <div class="relative w-64 max-w-xs flex flex-col bg-gray-900 text-white">
        <div class="absolute top-0 right-0 -mr-12 pt-2">
            <button id="close-menu-btn" type="button" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                <span class="sr-only">Tutup sidebar</span>
                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex items-center justify-center h-20 border-b border-gray-700">
            <svg class="h-8 w-auto text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
            </svg>
            <span class="ml-3 text-2xl font-semibold">AirNav</span>
        </div>
        <nav class="flex-1 overflow-y-auto px-4 py-6">
            <a href="#" class="flex items-center px-4 py-3 bg-blue-600 text-white rounded-lg font-medium">
                <!-- Icon Dashboard -->
                Dashboard
            </a>
            <a href="#" class="flex items-center px-4 py-3 mt-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
                <!-- Icon Data Peserta -->
                Data Peserta
            </a>
            <a href="#" class="flex items-center px-4 py-3 mt-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
                <!-- Icon Manajemen Invoice -->
                Manajemen Invoice
            </a>
            <a href="#" class="flex items-center px-4 py-3 mt-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
                <!-- Icon Pengaturan -->
                Pengaturan
            </a>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-lg">
                <!-- Icon Keluar -->
                Keluar
            </a>
        </div>
    </div>
</div>
