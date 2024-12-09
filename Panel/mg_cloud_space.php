<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

$_SESSION['last_activity'] = time();


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if (!isset($_SESSION['admin_level']) || !in_array($_SESSION['admin_level'], [1, 2])) {
    header('HTTP/1.1 403 Forbidden');
    exit('دسترسی مجاز نیست! دسترسی شما به این بخش مجاز نیست');
}


if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; 
}

if (isset($_POST['language'])) {
    $_SESSION['lang'] = $_POST['language'];
}

$texts = [
    'en' => [
        'title' => 'Cloud Space',
        'subtitle' => 'Cyber Turbo Manager',
        'used_space' => 'Used Space: ',
        'search_placeholder' => 'Search...',
        'upload_file' => 'Upload File',
        'create_folder' => 'Create Folder',
        'files_and_folders' => 'Files and Folders',
        'name' => 'Name',
        'type' => 'Type',
        'size' => 'Size (Bytes)',
        'created_time' => 'Created Time',
        'actions' => 'Actions',
        'delete' => 'Delete',
        'rename' => 'Rename',
        'confirm_delete' => 'Are you sure?',
        'file_saved' => 'File saved successfully!',
        'name_changed' => 'File name changed successfully!',
    ],
    'fa' => [
        'title' => 'فضای ابری',
        'subtitle' => 'مدیر سایبری توربو',
        'used_space' => 'فضای مصرف شده: ',
        'search_placeholder' => 'جستجو...',
        'upload_file' => 'آپلود فایل',
        'create_folder' => 'ایجاد پوشه',
        'files_and_folders' => 'فایل‌ها و پوشه‌ها',
        'name' => 'نام',
        'type' => 'نوع',
        'size' => 'حجم (بایت)',
        'created_time' => 'زمان ایجاد',
        'actions' => 'عملیات',
        'delete' => 'حذف',
        'rename' => 'تغییر نام',
        'confirm_delete' => 'آیا مطمئن هستید؟',
        'file_saved' => 'فایل با موفقیت ذخیره شد!',
        'name_changed' => 'نام فایل با موفقیت تغییر یافت!',
    ]
];




$uploads_dir = '../uploads';
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir);
}

function getTotalSize($dir) {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
        $size += $file->getSize();
    }
    return $size;
}
$total_size = getTotalSize($uploads_dir);
$total_size_kb = round($total_size / 1024, 2); 

function showAlert($message) {
    echo "<script>alert('$message');</script>";
}

if (isset($_FILES['file'])) {
    $target_dir = isset($_GET['dir']) ? $_GET['dir'] : $uploads_dir;
    $target_file = $target_dir . '/' . basename($_FILES['file']['name']);
    if (file_exists($target_file)) {
        showAlert("فایل با همین نام وجود دارد!");
    } else {
        move_uploaded_file($_FILES['file']['tmp_name'], $target_file);
        header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($target_dir));
        exit;
    }
}

if (isset($_POST['folder_name'])) {
    $target_dir = isset($_GET['dir']) ? $_GET['dir'] : $uploads_dir;
    $folder_name = $_POST['folder_name'];
    $target_folder = $target_dir . '/' . $folder_name;
    if (is_dir($target_folder)) {
        showAlert("پوشه با همین نام وجود دارد!");
    } else {
        mkdir($target_folder);
        header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($target_dir));
        exit;
    }
}

if (isset($_POST['file_path'])) {
    $file_path = $_POST['file_path'];
    $content = $_POST['content'];
    file_put_contents($file_path, $content);
    header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode(dirname($file_path)));
    exit;
}

if (isset($_POST['delete_path'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        showAlert("توکن نامعتبر است.");
    } else {
        $delete_path = $_POST['delete_path'];
        if (is_file($delete_path)) {
            unlink($delete_path);
        } elseif (is_dir($delete_path)) {
            rmdir($delete_path);
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode(dirname($delete_path)));
        exit;
    }
}

if (isset($_POST['rename_path']) && isset($_POST['new_name'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        showAlert("توکن نامعتبر است.");
    } else {
        $rename_path = $_POST['rename_path'];
        $new_name = $_POST['new_name'];
        $new_path = dirname($rename_path) . '/' . $new_name;
        if (!file_exists($new_path)) {
            rename($rename_path, $new_path);
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode(dirname($rename_path)));
        exit;
    }
}

$search_query = '';
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
}

$directory = isset($_GET['dir']) ? $_GET['dir'] : $uploads_dir;
$files = array_diff(scandir($directory), array('..', '.'));

?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>TURBO cloud space</title>
    <style>
        @font-face {
        font-family: 'Vazir';
        src: url('../css/fonts/Vazir.woff2');
        }
body {
    font-family: 'Vazir';
    background-color: #121212;
    color: #e0e0e0;
    margin: 0;
    padding: 20px;
    transition: background-color 0.3s;
}


input, button {
    font-family: 'Vazir';
    padding: 10px;
    margin: 5px;
    border: none;
    border-radius: 5px;
    transition: background-color 0.3s, transform 0.3s;
}

input[type="text"], input[type="file"] {
    width: calc(100% - 22px);
    background-color: #1e1e1e;
    color: #ffffff;
    border: 1px solid #333;
}

input[type="submit"], button {
    background-color: #007bff;
    color: #ffffff;
    cursor: pointer;
}

input[type="file"],
input[type="text"],
input[type="submit"] {
    padding: 5px; 
    font-size: 14px; 
}
input[type="submit"]:hover, button:hover {
    background-color: #0056b3;
    transform: translateY(-2px);
}

.folder, .file {
    margin: 10px 0;
    padding: 15px;
    border-radius: 5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background-color 0.3s;
}

.folder:hover, .file:hover {
    background-color: #333333;
}

.modal {
    display: none; 
    position: fixed; 
    z-index: 1; 
    left: 0; 
    top: 0; 
    width: 100%; 
    height: 100%; 
    overflow: auto; 
    background-color: rgba(0, 0, 0, 0.9); 
    padding-top: 60px; 
    animation: fadeIn 0.5s;
}

.modal-content {
    background-color: #1e1e1e;
    margin: 5% auto; 
    padding: 20px;
    width: 80%; 
    border-radius: 5px;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
    animation: slideIn 0.5s;
}

.close {
    color: #e0e0e0;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover {
    color: #ffffff;
}

.media {
    max-width: 100%;
    max-height: 500px;
    border-radius: 5px;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.form-item {
    display: flex;
    align-items: center; 
}



.form-container {
    display: flex;
    justify-content: space-between; 
    padding: 10px;
    border-radius: 5px;
    top: 120px; 
    left: 0; 
    right: 0; 
    z-index: 1000; 
}

.search-container {
    display: flex;
    align-items: center; 
}

.upload-container {
    display: flex;
    flex-direction: column; 
    gap: 10px; 
}

h1, h4, p {
    margin: 0;
}



table {
    width: 100%;
    border-collapse: collapse;
}


.table-container {
    max-height: 400px; 
    overflow-y: auto;
    border: 1px solid #00ffcc; 
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 255, 204, 0.5);
    background-color: #1c1c1c; 
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #333; 
}

th {
    background-color: #007bff; 
    color: white;
    position: sticky;
    top: 0;
    z-index: 1;
}

tr:hover {
  background-color: rgba(36, 183, 209, 0.3); 
}

td {
    color: #e0e0e0; 
}

.icon {
    width: 24px;
    height: 24px;
    vertical-align: middle;
    margin-right: 8px;
}

.folder-link {
    text-decoration: none;
    color: #007BFF;
}

.file-button, .delete-button, .rename-button {
    padding: 6px 12px;
    margin-left: 5px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.file-button {
    background-color: #28a745;
    color: white;
}

.file-button:hover {
    background-color: #218838;
}

.delete-button:hover {
  background-color: #c92131;
}
.rename-button:hover {
    background-color: #e0a800;
}
h4 {
    color: #00ffcc; 
    text-align: center;
    text-shadow: 0 0 20px rgba(0, 255, 204, 0.7); 
}

h1 {
  color: #007bff; 
  text-align: center;
  text-shadow: 0 0 20px #00aeff;
}

h2 {
  color: #008cff; 
  text-align: center;
  text-shadow: 0 0 20px #0059ff;
}

h5 {
  color: #008cff; 
  text-align: left;
  text-shadow: 0 0 20px #0059ff;
}

.usedspace {
    color: #00ffcc; 
    text-align: left;
    text-shadow: 0 0 20px rgba(0, 255, 204, 0.7); 
}

textarea {
  width: 100%; 
  height: 100%; 
  background-color: #a5cac3;
  color: #000000;
  border: 1px solid rgba(0, 162, 255, 0.7);
  cursor: pointer;
  box-shadow: 0 0 30px rgba(49, 49, 49, 0.7);

}


    </style>
</head>
<body>
<h1><?php echo $texts[$_SESSION['lang']]['title']; ?></h1>
<h4>Cyber Turbo Manager</h4>
<h5>Cyber Turbo Manager - CTM</h5>

<p class="usedspace"><?php echo $texts[$_SESSION['lang']]['used_space'] . $total_size_kb . ' kb'; ?></p>

<div class="form-container">
    <div class="search-container">
        <form action="" method="get">
            <input type="text" name="search" placeholder="<?php echo $texts[$_SESSION['lang']]['search_placeholder']; ?>" value="<?php echo htmlspecialchars($search_query); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="submit" value="<?php echo $texts[$_SESSION['lang']]['search_placeholder']; ?>">
        </form>
    </div>

    <div class="upload-container">
        <form action="" method="post" enctype="multipart/form-data" class="form-item">
            <input type="file" name="file" required>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="submit" value="<?php echo $texts[$_SESSION['lang']]['upload_file']; ?>">
            </form>

        <form action="" method="post" class="form-item">
            <input type="text" name="folder_name" placeholder="<?php echo $texts[$_SESSION['lang']]['name']; ?>" required>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="submit" value="<?php echo $texts[$_SESSION['lang']]['create_folder']; ?>">
            </form>
    </div>
</div>


    <hr color="#008cff">
    <h2><?php echo $texts[$_SESSION['lang']]['files_and_folders']; ?></h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th><?php echo $texts[$_SESSION['lang']]['name']; ?></th>
                <th><?php echo $texts[$_SESSION['lang']]['type']; ?></th>
                <th><?php echo $texts[$_SESSION['lang']]['size']; ?></th>
                <th><?php echo $texts[$_SESSION['lang']]['created_time']; ?></th>
                <th><?php echo $texts[$_SESSION['lang']]['actions']; ?></th>
            </tr>
        </thead>
        <tbody id="fileList">
            <?php
            foreach ($files as $file) {
                if ($search_query && stripos($file, $search_query) === false) {
                    continue; 
                }
                $file_path = $directory . '/' . $file;
                $file_type = is_dir($file_path) ? 'folder' : 'file';
                $file_size = is_dir($file_path) ? '-' : filesize($file_path);
                $file_time = date("Y-m-d H:i:s", filemtime($file_path));
                
                echo "<tr>";
                echo "<td>";
                if (is_dir($file_path)) {
                    echo "<a href='?dir=" . htmlspecialchars($file_path) . "&csrf_token=" . $csrf_token . "' class='folder-link'>";
                    echo "<img src='imegs/folder-icon.png' class='icon' alt='Folder Icon'>";
                    echo "$file</a>";
                } else {
                    echo "<img src='imegs/file-icon.png' class='icon' alt='File Icon' onclick=\"openFile('$file_path')\" style='cursor: pointer;'>";
                    echo "<button class='file-button' onclick=\"openFile('$file_path')\">$file</button>";
                }
                echo "</td>";
                echo "<td>$file_type</td>";
                echo "<td>$file_size</td>";
                echo "<td>$file_time</td>";
                echo "<td>";
                echo "<form method='post' style='display:inline;'>"; 
                echo "<input type='hidden' name='delete_path' value='$file_path'>"; 
                echo "<input type='hidden' name='csrf_token' value='" . $csrf_token . "'>"; 
                echo "<input type='submit' value='" . $texts[$_SESSION['lang']]['delete'] . "' class='delete-button' onclick=\"return confirm('" . $texts[$_SESSION['lang']]['confirm_delete'] . "')\">"; 
                echo "<button type='button' class='rename-button' onclick=\"openRenameModal('$file_path')\">" . $texts[$_SESSION['lang']]['rename'] . "</button>"; 
                echo "<a href='$file_path' target='_blank' style='margin-left: 5px;' title='Open in web page'><img src='imegs/open-icon.png' class='icon' alt='Open in web page'></a>"; 
                echo "</form>"; 
                echo "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>



    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">محتویات فایل</h2>
            <hr color="#008cff">
            <center>
            <div id="fileContent" style="white-space: pre-wrap; display: none;"></div>
            <textarea id="editContent" style="width: 100%; height: 300px; display: none;"></textarea>
            </center>
            <button id="saveButton" style="display:none;" onclick="saveFile()">ذخیره</button>

        </div>
    </div>

    <div id="renameModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRenameModal()">&times;</span>
            <h2>تغییر نام</h2>
            <input type="text" id="newFileName" placeholder="نام جدید">
            <button onclick="renameFile()">تأیید</button>
            <input type="hidden" id="renameFilePath">
            <button onclick="closeRenameModal()">انصراف</button>
        </div>
    </div>

    <script>
        let currentFilePath = '';

        function openFile(filePath) {
    currentFilePath = filePath; 
    const xhr = new XMLHttpRequest();
    xhr.open('GET', filePath, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            const extension = filePath.split('.').pop().toLowerCase();
            const content = xhr.responseText;

            document.getElementById('fileContent').style.display = 'none';
            document.getElementById('editContent').style.display = 'none';
            document.getElementById('saveButton').style.display = 'none';

            if (['txt', 'csv', 'json'].includes(extension)) {
                document.getElementById('modalTitle').innerText = 'محتویات فایل: ' + filePath;
                document.getElementById('editContent').value = content;
                document.getElementById('editContent').style.display = 'block';
                document.getElementById('saveButton').style.display = 'block';
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                document.getElementById('modalTitle').innerText = 'تصویر: ' + filePath;
                document.getElementById('fileContent').innerHTML = '<img src="' + filePath + '" class="media" />';
                document.getElementById('fileContent').style.display = 'block';
            } else if (['mp4', 'webm', 'ogg'].includes(extension)) {
                document.getElementById('modalTitle').innerText = 'ویدیو: ' + filePath;
                document.getElementById('fileContent').innerHTML = '<video class="media" controls><source src="' + filePath + '" type="video/' + extension + '"> مرورگر شما از این فرمت پشتیبانی نمی‌کند.</video>';
                document.getElementById('fileContent').style.display = 'block';
            } else if (['mp3', 'wav', 'ogg'].includes(extension)) {
                document.getElementById('modalTitle').innerText = 'صوت: ' + filePath;
                document.getElementById('fileContent').innerHTML = '<audio class="media" controls><source src="' + filePath + '" type="audio/' + extension + '"> مرورگر شما از این فرمت پشتیبانی نمی‌کند.</audio>';
                document.getElementById('fileContent').style.display = 'block';
            } else {
                document.getElementById('modalTitle').innerText = 'این نوع فایل قابل نمایش نیست';
                document.getElementById('fileContent').innerText = 'این نوع فایل قابل نمایش نیست';
            }
            document.getElementById('myModal').style.display = 'block';
        }
    };
    xhr.send();
}


        function closeModal() {
            document.getElementById('myModal').style.display = 'none';
        }

        function saveFile() {
            const content = document.getElementById('editContent').value;
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('file_path=' + encodeURIComponent(currentFilePath) + '&content=' + encodeURIComponent(content) + '&csrf_token=' + encodeURIComponent('<?php echo $csrf_token; ?>'));
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert("فایل با موفقیت ذخیره شد!");
                    closeModal();
                    location.reload();
                }
            };
        }

        function openRenameModal(filePath) {
            document.getElementById('renameFilePath').value = filePath;
            document.getElementById('newFileName').value = filePath.split('/').pop();
            document.getElementById('renameModal').style.display = 'block';
        }

        function closeRenameModal() {
            document.getElementById('renameModal').style.display = 'none';
        }

        function renameFile() {
            const newName = document.getElementById('newFileName').value;
            const filePath = document.getElementById('renameFilePath').value;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('rename_path=' + encodeURIComponent(filePath) + '&new_name=' + encodeURIComponent(newName) + '&csrf_token=' + encodeURIComponent('<?php echo $csrf_token; ?>'));
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert("نام فایل با موفقیت تغییر یافت!");
                    closeRenameModal();
                    location.reload();
                }
            };
        }

        window.onclick = function(event) {
            const modal = document.getElementById('myModal');
            const renameModal = document.getElementById('renameModal');
            if (event.target === modal) {
                closeModal();
            }
            if (event.target === renameModal) {
                closeRenameModal();
            }
        };
    </script>
</body>
</html>
