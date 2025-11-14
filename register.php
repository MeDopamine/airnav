<?php
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/auth.php';
// load centralized asset configuration (provides get_asset_url and $ASSETS)
if (file_exists(__DIR__ . '/config/assets.php')) {
    include_once __DIR__ . '/config/assets.php';
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $no_hp = trim($_POST['no_hp'] ?? '');
    $nik = trim($_POST['nik'] ?? '');
    $kelamin = trim($_POST['kelamin'] ?? '');
    $tgl_lahir = trim($_POST['tgl_lahir'] ?? '');

    // helper: normalize incoming date string (accept DD-MM-YYYY, MM/DD/YYYY, YYYY-MM-DD, etc.) -> return Y-m-d or empty string
    function parse_date_normalize_local($input) {
        $input = trim((string)$input);
        if ($input === '') return '';
        // ISO
        $d = DateTime::createFromFormat('Y-m-d', $input);
        if ($d && $d->format('Y-m-d') === $input) return $d->format('Y-m-d');
        $formats = ['d-m-Y','d/m/Y','Y/m/d','Y.m.d','d.m.Y','m/d/Y','m-d-Y'];
        foreach ($formats as $f) {
            $d = DateTime::createFromFormat($f, $input);
            if ($d) return $d->format('Y-m-d');
        }
        $ts = strtotime($input);
        if ($ts !== false) return date('Y-m-d', $ts);
        return '';
    }

    if ($name === '') $errors[] = 'Nama harus diisi.';
    // Accept normal emails and allow local addresses like user@localhost
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if (!preg_match('/^[^@\s]+@localhost$/i', $email)) {
            $errors[] = 'Email tidak valid.';
        }
    }
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $password_confirm) $errors[] = 'Konfirmasi password tidak sesuai.';
    // require NIK, phone, and tanggal lahir
    if ($nik === '') {
        $errors[] = 'NIK wajib diisi.';
    } elseif (!preg_match('/^[0-9]{6,20}$/', $nik)) {
        $errors[] = 'NIK tidak valid (6-20 digit).';
    }
    if ($kelamin === '') {
        $errors[] = 'Jenis kelamin wajib dipilih.';
    } else {
        // accept 'L' or 'P' for Laki-laki / Perempuan
        if (!in_array($kelamin, ['L','P'])) $errors[] = 'Pilihan jenis kelamin tidak valid.';
    }
    // require phone and restrict to digits only (6-16 digits)
    if ($no_hp === '') {
        $errors[] = 'Nomor HP wajib diisi.';
    } elseif (!preg_match('/^[0-9]{6,16}$/', $no_hp)) {
        $errors[] = 'Nomor HP tidak valid (hanya angka, 6-16 digit).';
    }
    // accept various human formats on registration; we'll normalize to YYYY-MM-DD for storage
    if ($tgl_lahir === '') {
        $errors[] = 'Tanggal lahir wajib diisi.';
    } else {
        $norm = parse_date_normalize_local($tgl_lahir);
        if ($norm === '') {
            $errors[] = 'Tanggal lahir tidak valid';
        } else {
            // overwrite for DB insert
            $tgl_lahir = $norm; // YYYY-MM-DD
        }
    }

    if (empty($errors)) {
        // check unique email in users table
        $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'Email sudah terdaftar.';
        } else {
            // We'll insert into registrasi_peserta (account/profile info), then create a users row
            // and finally create a data_peserta row for admin approval (status_data kept in data_peserta).
            mysqli_begin_transaction($conn);
            try {
                // Use provided no_hp and nik if available
                $no_hp = $no_hp;
                $nik = $nik;
                $tgl = date('Y-m-d');
                // prepare date for DB (YYYY-MM-DD or empty)
                $tgl_for_db = $tgl_lahir !== '' ? $tgl_lahir : '';
                // insert registrasi_peserta so account/profile data is available there
                // Note: registrasi_peserta does not have `created_at`; do not attempt to insert that column.
                $insReg = mysqli_prepare($conn, 'INSERT INTO registrasi_peserta (nama, kelamin, email, no_hp, nik, tgl_lahir) VALUES (?, ?, ?, ?, ?, ?)');
                if (!$insReg) throw new Exception('Gagal menyiapkan registrasi: ' . mysqli_error($conn));
                mysqli_stmt_bind_param($insReg, 'ssssss', $name, $kelamin, $email, $no_hp, $nik, $tgl_for_db);
                if (!mysqli_stmt_execute($insReg)) throw new Exception('Gagal memasukkan registrasi: ' . mysqli_error($conn));
                $registrasi_id = mysqli_insert_id($conn);

                $hash = password_hash($password, PASSWORD_BCRYPT);
                $role = 'user';
                // Insert into users table (do NOT add or rely on registrasi_id column)
                $ins = mysqli_prepare($conn, 'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
                mysqli_stmt_bind_param($ins, 'ssss', $name, $email, $hash, $role);
                if (!mysqli_stmt_execute($ins)) throw new Exception('Gagal membuat akun: ' . mysqli_error($conn));

                // commit
                mysqli_commit($conn);
                // auto-login using the newly created users row
                $user_id = mysqli_insert_id($conn);
                // NOTE: Do NOT create a data_peserta row here. Account verification is handled by admin
                // using the registrasi_peserta table; once admin verifies, they can create or update
                // an entry in data_peserta with status_data = 1 to mark the account as approved.
                $row = ['id' => $user_id, 'name' => $name, 'email' => $email, 'role' => $role];
                require_once __DIR__ . '/auth.php';
                login_user_from_row($row);
                // redirect users to their profile area, not the main dashboard
                header('Location: /dashboard/user/dashboard.php');
                exit;
            } catch (Exception $ex) {
                mysqli_rollback($conn);
                $errors[] = $ex->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Daftar - AirNav</title>
    <!-- Icon -->
    <link rel="icon" href="https://placehold.co/32x32/0033A0/FFFFFF?text=D" type="image/png">
    <link rel="stylesheet" href="/dashboard/assets/css/tailwind.output.css">
    <!-- Load Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <?php
    if (function_exists('get_asset_url')) {
        $flow_css = get_asset_url($ASSETS['flowbite_css_local'] ?? '/dashboard/assets/vendor/flowbite/flowbite.min.css', $ASSETS['flowbite_css_cdn'] ?? 'https://cdn.jsdelivr.net/npm/flowbite@1.7.0/dist/flowbite.min.css');
    } else {
        $flow_css = 'https://cdn.jsdelivr.net/npm/flowbite@1.7.0/dist/flowbite.min.css';
    }
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($flow_css); ?>">
    <link rel="stylesheet" href="/dashboard/assets/css/swal-custom.css">
    <style>
        /* Confirmation (sa-konfirmasi) styles inspired by Tailwind utilities */
        .sa-konfirmasi-list {
            text-align: left;
            margin-top: 0.5rem;
        }
        .sa-konfirmasi-item {
            margin-bottom: 0.75rem;
        }
        .sa-konfirmasi-label {
            font-size: 0.75rem; /* 12px */
            color: #6b7280; /* gray-500 */
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: center;
        }
        .sa-konfirmasi-value {
            font-size: 1rem; /* 16px */
            color: #111827; /* gray-900 */
            font-weight: 600;
            padding-top: 0.125rem;
            text-align: center;
        }
        .sa-konfirmasi-divider {
            margin: 1rem 0;
            border-top: 1px dashed #d1d5db; /* gray-300 */
        }
        /* Reduce title -> paragraph spacing inside SweetAlert2 */
        .swal2-title { margin-top: -1.5rem !important; }
        .sa-konfirmasi-list p { margin-top: -1.25rem !important; margin-bottom: 0.75rem !important; }
        /* Slightly increase popup padding and roundness to match site */
        .swal2-popup { border-radius:12px; padding:1.25rem !important; }
    /* SweetAlert2 styles moved to /dashboard/assets/css/swal-custom.css for reuse */
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-xl shadow-md p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Buat Akun Baru</h1>
            <p class="text-sm text-gray-500 mt-2">Daftar sebagai pengguna untuk mengupload data.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 p-3 rounded">
                <ul class="text-sm">
                    <?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div id="client-error" class="mb-4 hidden bg-red-50 border border-red-200 text-red-700 p-3 rounded"></div>

        <form method="post" action="" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nama</label>
                <input name="name" type="text" value="<?php echo htmlspecialchars($_POST['name'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                <div class="mt-1 flex items-center gap-6">
                    <label class="inline-flex items-center">
                        <input type="radio" name="kelamin" value="L" <?php echo (isset($_POST['kelamin']) && $_POST['kelamin'] === 'L') ? 'checked' : ''; ?> class="form-radio">
                        <span class="ml-2">Laki-laki</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="kelamin" value="P" <?php echo (isset($_POST['kelamin']) && $_POST['kelamin'] === 'P') ? 'checked' : ''; ?> class="form-radio">
                        <span class="ml-2">Perempuan</span>
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">NIK</label>
                <input name="nik" required type="text" value="<?php echo htmlspecialchars($_POST['nik'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Masukkan NIK">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tanggal Lahir</label>
                <div class="relative">
                    <div class="px-3 absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                        <i class="fa-solid fa-calendar-week w-4 h-4 text-gray-500"></i>
                    </div>
                    <!-- Flowbite-friendly input; fallback to native date input at runtime if Datepicker is unavailable -->
                    <input id="tgl_lahir" name="tgl_lahir" required type="text" datepicker datepicker-autohide datepicker-format="dd M yyyy" value="<?php
                        $pv = $_POST['tgl_lahir'] ?? '';
                        if ($pv) {
                            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $pv)) {
                                $dt = DateTime::createFromFormat('Y-m-d', $pv);
                                echo $dt ? htmlspecialchars($dt->format('d-m-Y')) : htmlspecialchars($pv);
                            } else {
                                echo htmlspecialchars($pv);
                            }
                        }
                    ?>" class="mt-1 block w-full px-8 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Pilih tanggal" autocomplete="off">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Nomor HP</label>
                <input name="no_hp" required inputmode="numeric" pattern="[0-9]*" oninput="this.value=this.value.replace(/\D/g,'')" type="text" value="<?php echo htmlspecialchars($_POST['no_hp'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Contoh: 08123456789" maxlength="16">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input name="email" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input name="password" type="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                <input name="password_confirm" type="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <div>
                <button type="submit" class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md">Daftar</button>
            </div>
        </form>

        <div class="mt-4 text-center text-sm text-gray-600">
            Sudah punya akun? <a href="/login.php" class="text-blue-600 hover:underline">Masuk</a>
        </div>

    <!-- SweetAlert for confirm preview -->
    <script src="/assets/js/sweetalert2@11.js"></script>
    <script>
        (function(){
            const form = document.querySelector('form');
            if (!form) return;

            function showClientErrors(errors) {
                const container = document.getElementById('client-error');
                if (!container) return;
                if (!errors || errors.length === 0) {
                    container.classList.add('hidden');
                    container.innerHTML = '';
                    return;
                }
                container.innerHTML = '<ul class="text-sm">' + errors.map(er => '<li>'+er+'</li>').join('') + '</ul>';
                container.classList.remove('hidden');
                container.scrollIntoView({behavior:'smooth', block:'center'});
            }

            form.addEventListener('submit', async function(e){
                e.preventDefault();
                const name = form.name.value.trim();
                const email = form.email.value.trim();
                const nik = (form.nik && form.nik.value) ? form.nik.value.trim() : '';
                const kelEl = document.querySelector('input[name="kelamin"]:checked');
                const kel = kelEl ? kelEl.value : '';
                const tgl = (form.tgl_lahir && form.tgl_lahir.value) ? form.tgl_lahir.value.trim() : '';
                const nohp = (form.no_hp && form.no_hp.value) ? form.no_hp.value.trim() : '';
                const pass = form.password.value;
                const pass2 = form.password_confirm.value;
                let errors = [];

                if (!name) errors.push('Nama harus diisi');
                if (!email) errors.push('Email harus diisi');
                else {
                    const reStandard = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
                    const reLocal = /^[^@\s]+@localhost$/i;
                    if (!reStandard.test(email) && !reLocal.test(email)) errors.push('Email tidak valid');
                }
                if (pass.length < 6) errors.push('Password minimal 6 karakter');
                if (pass !== pass2) errors.push('Konfirmasi password tidak sesuai');

                if (!nik) {
                    errors.push('NIK wajib diisi');
                } else {
                    const reNik = /^[0-9]{6,20}$/;
                    if (!reNik.test(nik)) errors.push('NIK tidak valid (6-20 digit)');
                }

                if (!kel) {
                    errors.push('Jenis kelamin wajib dipilih');
                }

                if (!tgl) {
                    errors.push('Tanggal lahir wajib diisi');
                }

                if (!nohp) {
                    errors.push('Nomor HP wajib diisi');
                } else {
                    const reHp = /^[0-9]{6,16}$/;
                    if (!reHp.test(nohp)) errors.push('Nomor HP tidak valid (hanya angka, 6-16 digit).');
                }

                if (errors.length) {
                    showClientErrors(errors);
                    return;
                }

                // check availability (email & nik) via server API
                try {
                    const resp = await fetch('/api/check_availability.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: email, nik: nik })
                    });
                    if (resp && resp.ok) {
                        const j = await resp.json();
                        if (j.email_taken || j.nik_taken) {
                            let msgParts = [];
                            if (j.email_taken) msgParts.push('Email sudah terdaftar.');
                            if (j.nik_taken) msgParts.push('NIK sudah terdaftar.');
                            const msg = msgParts.join('\n');
                            // show SweetAlert error and highlight fields
                            Swal.fire({
                                icon: 'error',
                                title: 'Sudah Terdaftar',
                                text: msg,
                                confirmButtonText: 'Oke',
                                customClass: { confirmButton: 'swal-ok-btn' }
                            });
                            if (j.email_taken) {
                                const el = form.email; el.focus();
                            } else if (j.nik_taken) {
                                const el = form.nik; el.focus();
                            }
                            return;
                        }
                    }
                } catch (ex) {
                    console.error('Availability check failed', ex);
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan',
                        text: 'Tidak dapat memeriksa ketersediaan sekarang. Coba lagi.',
                        confirmButtonText: 'Oke',
                        customClass: { confirmButton: 'swal-ok-btn' }
                    });
                    return;
                }

                // build preview HTML using the sa-konfirmasi layout (matches example)
                const previewHtml = `
                    <div class="sa-konfirmasi-list">
                        <p class="text-sm text-gray-700 text-center mb-2">
                            Anda akan menyimpan data berikut. Mohon periksa kembali data Anda.
                        </p>

                        <div class="sa-konfirmasi-divider"></div>

                        <div class="sa-konfirmasi-item">
                            <div class="sa-konfirmasi-label">Nama</div>
                            <div class="sa-konfirmasi-value">${escapeHtml(name)}</div>
                        </div>

                        <div class="sa-konfirmasi-item">
                            <div class="sa-konfirmasi-label">NIK</div>
                            <div class="sa-konfirmasi-value">${escapeHtml(nik)}</div>
                        </div>

                        <div class="sa-konfirmasi-item">
                            <div class="sa-konfirmasi-label">Email</div>
                            <div class="sa-konfirmasi-value">${escapeHtml(email)}</div>
                        </div>

                        <div class="sa-konfirmasi-item">
                            <div class="sa-konfirmasi-label">Tanggal Lahir</div>
                            <div class="sa-konfirmasi-value">${escapeHtml(tgl)}</div>
                        </div>

                        <div class="sa-konfirmasi-item">
                            <div class="sa-konfirmasi-label">Jenis Kelamin</div>
                            <div class="sa-konfirmasi-value">${kel === 'L' ? 'Laki-laki' : (kel === 'P' ? 'Perempuan' : '')}</div>
                        </div>

                        <div class="sa-konfirmasi-item">
                            <div class="sa-konfirmasi-label">Nomor HP</div>
                            <div class="sa-konfirmasi-value">${escapeHtml(nohp)}</div>
                        </div>

                        <div class="sa-konfirmasi-divider" style="margin-bottom:2px;"></div>
                    </div>
                `;

                Swal.fire({
                    title: 'Konfirmasi Data Diri',
                    icon: 'question',
                    html: previewHtml,
                    showCancelButton: true,
                    confirmButtonText: 'Konfirmasi & Daftar',
                    cancelButtonText: 'Kembali',
                    allowOutsideClick: false,
                    width: 640,
                    showCloseButton: false,
                    customClass: {
                        confirmButton: 'swal-confirm-btn',
                        cancelButton: 'swal-cancel-btn'
                    },
                    didOpen: () => {
                        // apply Tailwind-like classes to buttons since SweetAlert2 doesn't auto-apply them
                        const btn = document.querySelector('.swal-confirm-btn');
                        const cbtn = document.querySelector('.swal-cancel-btn');
                        if (btn) btn.className = 'bg-blue-600 text-white font-medium py-2 px-4 rounded-lg swal2-confirm';
                        if (cbtn) cbtn.className = 'bg-gray-500 text-white font-medium py-2 px-4 rounded-lg ml-2 swal2-cancel';
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // finally submit the form
                        form.submit();
                    }
                });
            });

            // small helper to escape HTML
            function escapeHtml(s) {
                return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }
        })();
    </script>

    <!-- Flowbite datepicker script (initializes below if available) -->
    <?php
    if (function_exists('get_asset_url')) {
        $flow_js = get_asset_url($ASSETS['flowbite_js_local'] ?? '/dashboard/assets/vendor/flowbite/datepicker.min.js', $ASSETS['flowbite_js_cdn'] ?? 'https://cdn.jsdelivr.net/npm/flowbite@1.7.0/dist/datepicker.min.js');
    } else {
        $flow_js = 'https://cdn.jsdelivr.net/npm/flowbite@1.7.0/dist/datepicker.min.js';
    }
    ?>
    <script src="<?php echo htmlspecialchars($flow_js); ?>"></script>
    <script src="/dashboard/assets/js/datepicker-init.js"></script>
    <script>
        (function(){
            try {
                var el = document.getElementById('tgl_lahir');
                if (!el) return;
                try {
                    if (window.DatepickerInit && typeof DatepickerInit.initElement === 'function') {
                        DatepickerInit.initElement(el, { format: 'dd-mm-yyyy' });
                    } else if (typeof Datepicker !== 'undefined') {
                        new Datepicker(el, { autohide: true, format: 'dd-mm-yyyy' });
                    } else {
                        el.type = 'date';
                    }
                } catch (innerEx) {
                    try { el.type = 'date'; } catch(_){ }
                    console.error('Datepicker init failed, falling back to native date input', innerEx);
                }
            } catch (ex) {
                console.error('Gagal menginisialisasi datepicker:', ex);
            }
        })();
    </script>
    </div>
</body>
</html>
