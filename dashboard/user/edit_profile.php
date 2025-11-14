<?php
include_once __DIR__ . '/../../auth.php';
require_login();

$user = current_user();
// only allow users to access their profile; admins can also view via dashboard
if ($user['role'] !== 'user' && $user['role'] !== 'admin') {
    echo 'Akses ditolak.';
    exit;
}

require_once __DIR__ . '/../../db/db.php';
// load dashboard partials
include_once __DIR__ . '/../partials/_init.php';

$registrasi = null;
$s1 = mysqli_prepare($conn, 'SELECT * FROM registrasi_peserta WHERE email = ? ORDER BY id DESC LIMIT 1');
mysqli_stmt_bind_param($s1, 's', $user['email']);
mysqli_stmt_execute($s1);
$r1 = mysqli_stmt_get_result($s1);
$registrasi = mysqli_fetch_assoc($r1);

if (!$registrasi) {
    $s2 = mysqli_prepare($conn, 'SELECT * FROM registrasi_peserta WHERE nama = ? ORDER BY id DESC LIMIT 1');
    mysqli_stmt_bind_param($s2, 's', $user['name']);
    mysqli_stmt_execute($s2);
    $r2 = mysqli_stmt_get_result($s2);
    $registrasi = mysqli_fetch_assoc($r2);
}

// helper to normalize date to Y-m-d or return false
function parse_date_normalize($input) {
    $input = trim((string)$input);
    if ($input === '') return false;
    $d = DateTime::createFromFormat('Y-m-d', $input);
    if ($d && $d->format('Y-m-d') === $input) return $d->format('Y-m-d');
    $formats = ['d-m-Y','d/m/Y','Y/m/d','Y.m.d','d.m.Y','m/d/Y','m-d-Y'];
    foreach ($formats as $f) {
        $d = DateTime::createFromFormat($f, $input);
        if ($d) return $d->format('Y-m-d');
    }
    $ts = strtotime($input);
    if ($ts !== false) return date('Y-m-d', $ts);
    return false;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['nama'] ?? '');
    $email_input = array_key_exists('email', $_POST) ? trim($_POST['email']) : null;
    $no_hp = trim($_POST['no_hp'] ?? '');
    // allow editing jenis kelamin (optional)
    $kelamin_in = trim($_POST['kelamin'] ?? '');
    // NIK is displayed but not editable/submitted from this form
    $tgl_lahir_in = trim($_POST['tgl_lahir'] ?? '');

    if ($name === '') $errors[] = 'Nama wajib diisi';

    // normalize tgl_lahir
    $tgl_lahir_db = null;
    if ($tgl_lahir_in !== '') {
        $norm = parse_date_normalize($tgl_lahir_in);
        if ($norm === false) {
            $errors[] = 'Tanggal lahir tidak valid';
        } else {
            $tgl_lahir_db = $norm;
        }
    }

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            // find registrasi row
            $s = mysqli_prepare($conn, 'SELECT id FROM registrasi_peserta WHERE email = ? ORDER BY id DESC LIMIT 1');
            mysqli_stmt_bind_param($s, 's', $user['email']);
            mysqli_stmt_execute($s);
            $r = mysqli_stmt_get_result($s);
            $reg = mysqli_fetch_assoc($r);

            // determine email to use (allow user@localhost as a local address similar to registration rules)
            if ($email_input !== null && $email_input !== '') {
                $email = $email_input;
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // allow local addresses like user@localhost
                    if (!preg_match('/^[^@\s]+@localhost$/i', $email)) {
                        mysqli_rollback($conn);
                        $errors[] = 'Email tidak valid';
                    }
                }
            } else {
                $email = $reg['email'] ?? $user['email'];
            }

            // validate phone number: only digits allowed
            if ($no_hp !== '' && !preg_match('/^[0-9]+$/', $no_hp)) {
                mysqli_rollback($conn);
                $errors[] = 'Nomor HP hanya boleh berisi angka';
            }

            if (empty($errors)) {
                if ($reg) {
                    $id = $reg['id'];
                    // do not update nik from this form; keep existing nik in DB
                    $u = mysqli_prepare($conn, 'UPDATE registrasi_peserta SET nama = ?, email = ?, no_hp = ?, tgl_lahir = ?, kelamin = ? WHERE id = ?');
                    $tgl_param = $tgl_lahir_db !== null ? $tgl_lahir_db : '';
                    // determine kelamin to store: prefer submitted valid value, otherwise keep existing
                    $kelamin_db = in_array($kelamin_in, ['L','P']) ? $kelamin_in : ($reg['kelamin'] ?? '');
                    mysqli_stmt_bind_param($u, 'sssssi', $name, $email, $no_hp, $tgl_param, $kelamin_db, $id);
                    mysqli_stmt_execute($u);
                } else {
                    // insert without nik column (we don't collect nik here)
                    $ins = mysqli_prepare($conn, 'INSERT INTO registrasi_peserta (nama, email, no_hp, tgl_lahir, kelamin, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                    $tgl_param = $tgl_lahir_db !== null ? $tgl_lahir_db : '';
                    $kelamin_db = in_array($kelamin_in, ['L','P']) ? $kelamin_in : '';
                    mysqli_stmt_bind_param($ins, 'sssss', $name, $email, $no_hp, $tgl_param, $kelamin_db);
                    mysqli_stmt_execute($ins);
                    $id = mysqli_insert_id($conn);
                }

                if (!empty($user['id'])) {
                    $up = mysqli_prepare($conn, 'UPDATE users SET name = ?, email = ? WHERE id = ?');
                    mysqli_stmt_bind_param($up, 'ssi', $name, $email, $user['id']);
                    mysqli_stmt_execute($up);
                }

                // update session so header displays the new name/email immediately
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                }

                mysqli_commit($conn);
                // redirect back to profile with success
                header('Location: profile.php?updated=1');
                exit;
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = 'Terjadi kesalahan server';
        }
    }
}

// render page
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Profil</title>
    <!-- Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/tailwind.output.css">
    <link rel="icon" href="https://placehold.co/32x32/0033A0/FFFFFF?text=E" type="image/png">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <?php render_partial('sidebar_user'); ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php render_partial('header'); ?>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-8">
                <div class="max-w-3xl mx-auto">
                    <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 border border-gray-100">
                        <h2 class="text-2xl font-semibold mb-2 text-gray-900">Edit Profil</h2>
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
                                <input name="nama" type="text" value="<?php echo htmlspecialchars($_POST['nama'] ?? ($registrasi['nama'] ?? $user['name'] ?? '')) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">NIK</label>
                                <!-- Disabled so NIK is visible but not submitted/edited -->
                                <input type="text" disabled value="<?php echo htmlspecialchars($registrasi['nik'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                                <div class="mt-1 flex items-center gap-6">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="kelamin" value="L" <?php echo (isset($_POST['kelamin']) && $_POST['kelamin'] === 'L') || (!isset($_POST['kelamin']) && isset($registrasi['kelamin']) && $registrasi['kelamin'] === 'L') ? 'checked' : ''; ?> class="form-radio">
                                        <span class="ml-2">L</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="kelamin" value="P" <?php echo (isset($_POST['kelamin']) && $_POST['kelamin'] === 'P') || (!isset($_POST['kelamin']) && isset($registrasi['kelamin']) && $registrasi['kelamin'] === 'P') ? 'checked' : ''; ?> class="form-radio">
                                        <span class="ml-2">P</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input name="email" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ($registrasi['email'] ?? $user['email'] ?? '')) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Lahir</label>
                                <div class="relative">
                                    <div class="px-3 absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <i class="fa-solid fa-calendar-week w-4 h-4 text-gray-500"></i>
                                    </div>
                                    <input id="tgl_lahir" name="tgl_lahir" type="text" datepicker datepicker-format="dd M yyyy" datepicker-autohide value="<?php
                                    $pv = $_POST['tgl_lahir'] ?? ($registrasi['tgl_lahir'] ?? '');
                                    if ($pv) {
                                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $pv)) {
                                            $dt = DateTime::createFromFormat('Y-m-d', $pv);
                                            echo $dt ? htmlspecialchars($dt->format('d M Y')) : htmlspecialchars($pv);
                                        } else {
                                            echo htmlspecialchars($pv);
                                        }
                                    }
                                    ?>" class="mt-1 block w-full px-8 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="DD-MM-YYYY" autocomplete="off">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nomor HP</label>
                                <input id="no_hp" name="no_hp" inputmode="numeric" pattern="[0-9]*" oninput="this.value=this.value.replace(/\D/g,'')" type="text" value="<?php echo htmlspecialchars($_POST['no_hp'] ?? ($registrasi['no_hp'] ?? '')) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <p id="nohp-help" class="mt-1 text-sm text-red-600 hidden">Nomor HP harus berisi 6–16 digit angka.</p>
                            </div>
                            <div>
                                <button type="submit" class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md mb-2">Simpan</button>
                                <button type="button" onclick="window.location.href='profile.php'" class="w-full py-2 px-4 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-md">Batal</button>
                            </div>
                        </form>

                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php render_partial('footer'); ?>
    <script>
        // Ensure the datepicker init uses dd-mm-yyyy format (DatepickerInit is loaded globally from footer)
        (function(){
            try {
                var el = document.getElementById('tgl_lahir');
                if (el && window.DatepickerInit) DatepickerInit.initElement(el, { format: 'dd-mm-yyyy' });
            } catch(e){}
        })();
    </script>
    <script>
        // Inline client-side validation for edit profile form
        (function(){
            var form = document.querySelector('form');
            var clientErr = document.getElementById('client-error');
            var nohp = document.getElementById('no_hp');
            var nohpHelp = document.getElementById('nohp-help');

            function showClientErrors(list) {
                if (!clientErr) return;
                if (!list || list.length === 0) {
                    clientErr.classList.add('hidden');
                    clientErr.innerHTML = '';
                    return;
                }
                clientErr.classList.remove('hidden');
                clientErr.innerHTML = '<ul class="text-sm">' + list.map(function(it){ return '<li>'+it+'</li>'; }).join('') + '</ul>';
                clientErr.scrollIntoView({behavior:'smooth', block:'center'});
            }

            if (nohp) {
                nohp.addEventListener('input', function(){
                    var v = (this.value||'').replace(/\D/g,'');
                    // reflect cleaned value (oninput already strips non-digits)
                    this.value = v;
                    if (v !== '' && (v.length < 6 || v.length > 16)) {
                        nohpHelp.classList.remove('hidden');
                    } else {
                        nohpHelp.classList.add('hidden');
                    }
                });
            }

            if (!form) return;
            form.addEventListener('submit', function(e){
                var errs = [];
                var name = form.nama && form.nama.value && form.nama.value.trim();
                var email = form.email && form.email.value && form.email.value.trim();
                var nohpVal = form.no_hp && form.no_hp.value && form.no_hp.value.trim();

                if (!name) errs.push('Nama wajib diisi');

                if (email) {
                    var reStandard = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
                    var reLocal = /^[^@\s]+@localhost$/i;
                    if (!reStandard.test(email) && !reLocal.test(email)) errs.push('Email tidak valid');
                }

                if (nohpVal) {
                    if (!/^[0-9]{8,14}$/.test(nohpVal)) errs.push('Nomor HP tidak valid (hanya angka, 8-14 digit).');
                }

                if (errs.length) {
                    e.preventDefault();
                    showClientErrors(errs);
                }
            });
        })();
    </script>
</body>
</html>
