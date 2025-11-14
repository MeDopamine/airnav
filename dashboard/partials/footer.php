<?php
// Footer partial: scripts and closing tags
?>
<script src="../../assets/js/sweetalert2@11.js"></script>
<script src="../../assets/js/main.js"></script>
<!-- Flowbite datepicker (global) -->
<?php
if (function_exists('get_asset_url')) {
	$flow_js = get_asset_url($ASSETS['flowbite_js_local'] ?? '/dashboard/assets/vendor/flowbite/datepicker.min.js', $ASSETS['flowbite_js_cdn'] ?? 'https://cdn.jsdelivr.net/npm/flowbite@1.7.0/dist/datepicker.min.js');
} else {
	$flow_js = 'https://cdn.jsdelivr.net/npm/flowbite@1.7.0/dist/datepicker.min.js';
}
?>
<script src="<?php echo htmlspecialchars($flow_js); ?>"></script>
<!-- Our shared datepicker initializer (depends on Flowbite if available) -->
<script src="../assets/js/datepicker-init.js"></script>
</body>
</html>
