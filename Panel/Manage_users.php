<?php
session_start();

define('SESSION_TIMEOUT', 1200);

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

if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $csrf_token) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['admin_level']) || !in_array($_SESSION['admin_level'], [1])) {
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
        'title' => 'User  Management',
        'search_id' => 'Search by ID',
        'search_username' => 'Search by Username',
        'sort_by' => 'Sort By',
        'order' => 'Order',
        'asc' => 'Ascending',
        'desc' => 'Descending',
        'search' => 'Search',
        'delete' => 'Delete',
        'confirm_delete' => 'Are you sure you want to delete this user?',
        'user_count' => 'Number of Accounts',
        'id' => 'ID',
        'username' => 'Username',
        'name' => 'Name',
        'created_at' => 'Created At',
        'last_login' => 'Last Login',
        'admin_level' => 'Admin Level',
        'password_changed_at' => 'Password Changed At',
        'operations' => 'Operations',
    ],
    'fa' => [
        'title' => 'مدیریت کاربران',
        'search_id' => 'جستجو با ID',
        'search_username' => 'جستجو با نام کاربری',
        'sort_by' => 'مرتب‌سازی بر اساس',
        'order' => 'ترتیب',
        'asc' => 'صعودی',
        'desc' => 'نزولی',
        'search' => 'جستجو',
        'delete' => 'حذف',
        'confirm_delete' => 'آیا مطمئن هستید که می‌خواهید این کاربر را حذف کنید؟',
        'user_count' => 'تعداد حساب ها',
        'id' => 'ID',
        'username' => 'نام کاربری',
        'name' => 'نام',
        'created_at' => 'تاریخ ایجاد',
        'last_login' => 'آخرین ورود',
        'admin_level' => 'سطح حساب',
        'password_changed_at' => 'زمان تغییر رمز عبور',
        'operations' => 'عملیات',
    ]
];




require_once '../config/db-config.php';

$admin_id = $_SESSION['username'] ?? null;
$admin_level = $_SESSION['admin_level'] ?? 0; 

function sanitizeInput($data) {
    return htmlspecialchars(trim($data));
}

$query = "SELECT COUNT(*) as admin_count FROM adn2 WHERE admin_level IN (2, 3)";
$stmt = $conn->prepare($query);
$stmt->execute();
$admin_count = $stmt->fetchColumn();

$search_id = '';
$search_username = '';
$sort_by = 'created_at';
$order = 'ASC';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search'])) {
        $search_id = sanitizeInput($_POST['search_id'] ?? '');
        $search_username = sanitizeInput($_POST['search_username'] ?? '');
        $sort_by = sanitizeInput($_POST['sort_by'] ?? 'created_at');
        $order = sanitizeInput($_POST['order'] ?? 'ASC');
    } elseif (isset($_POST['delete'])) {
        if ($admin_level == 1 && isset($_POST['user_id'])) {
            $user_id = intval($_POST['user_id']);
            $delete_query = "DELETE FROM adn2 WHERE id = ? AND admin_level IN (2, 3)";
            $stmt = $conn->prepare($delete_query);
            $stmt->execute([$user_id]);
        }
    }
}

$query = "SELECT id, username, created_at, last_login, admin_level, name, password_changed_at FROM adn2 WHERE admin_level IN (2, 3)";
$params = [];
if ($search_id) {
    $query .= " AND id = ?";
    $params[] = intval($search_id);
}
if ($search_username) {
    $query .= " AND username LIKE ?";
    $params[] = "%" . $search_username . "%";
}

$query .= " ORDER BY $sort_by $order";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$conn = null; 
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کاربران</title>
    <link rel="stylesheet" href="../css/admin_styles.css">

    <style>
        @font-face {
            font-family: 'Vazir';
            src: url('../css/fonts/Vazir.woff2');
        }
        body {
            font-family: 'Vazir';
        }
        input, button, select {
            font-family: 'Vazir';
        }

    </style>
</head>
<body>
    <div class="container">
    <h1><?php echo $texts[$_SESSION['lang']]['title']; ?></h1>
    <p><?php echo htmlspecialchars($admin_id); ?></p>

    <form method="POST" action="">
            <input type="text" name="search_id" placeholder="<?php echo $texts[$_SESSION['lang']]['search_id']; ?>" value="<?php echo htmlspecialchars($search_id); ?>">
            <br>
            <input type="text" name="search_username" placeholder="<?php echo $texts[$_SESSION['lang']]['search_username']; ?>" value="<?php echo htmlspecialchars($search_username); ?>">
            <br>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <select name="sort_by">
                <option value="username" <?php echo ($sort_by == 'username') ? 'selected' : ''; ?>><?php echo $texts[$_SESSION['lang']]['username']; ?></option>
                <option value="created_at" <?php echo ($sort_by == 'created_at') ? 'selected' : ''; ?>><?php echo $texts[$_SESSION['lang']]['created_at']; ?></option>
            </select>
            <br>
            <select name="order">
                <option value="ASC" <?php echo ($order == 'ASC') ? 'selected' : ''; ?>><?php echo $texts[$_SESSION['lang']]['asc']; ?></option>
                <option value="DESC" <?php echo ($order == 'DESC') ? 'selected' : ''; ?>><?php echo $texts[$_SESSION['lang']]['desc']; ?></option>
            </select>
            <br>
            <button type="submit" name="search"><?php echo $texts[$_SESSION['lang']]['search']; ?></button>
        </form>
        
        <p><?php echo $admin_count; ?> :<?php echo $texts[$_SESSION['lang']]['user_count']; ?></p>
        <hr>
        <table>
            <thead>
                <tr>
                <th><?php echo $texts[$_SESSION['lang']]['id']; ?></th>
                    <th><?php echo $texts[$_SESSION['lang']]['username']; ?></th>
                    <th><?php echo $texts[$_SESSION['lang']]['name']; ?></th>
                    <th><?php echo $texts[$_SESSION['lang']]['created_at']; ?></th>
                    <th><?php echo $texts[$_SESSION['lang']]['last_login']; ?></th>
                    <th><?php echo $texts[$_SESSION['lang']]['admin_level']; ?></th>
                    <th><?php echo $texts[$_SESSION['lang']]['password_changed_at']; ?></th>

                    <?php if ($admin_level == 1): ?>
                        <th><?php echo $texts[$_SESSION['lang']]['operations']; ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>

                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($user['last_login']); ?></td>
                        <td><?php echo htmlspecialchars($user['admin_level']); ?></td>
                        <td><?php echo htmlspecialchars($user['password_changed_at']); ?></td>

                        <?php if ($admin_level == 1): ?>
                            <td>
                            <form method="POST" action="">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <button type="submit" name="delete" onclick="return confirm('<?php echo $texts[$_SESSION['lang']]['confirm_delete']; ?>')"><?php echo $texts[$_SESSION['lang']]['delete']; ?></button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
