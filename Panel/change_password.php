<?php
require_once '../config/db-config.php';

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

if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $csrf_token) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; 
}

if (isset($_POST['language'])) {
    $_SESSION['lang'] = $_POST['language'];
}

$texts = [
    'en' => [
        'change_password' => 'Change Password',
        'old_password' => 'Old Password',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm New Password',
        'submit' => 'Change Password',
        'success' => 'Password changed successfully.',
        'error_old_password' => 'Old password is incorrect.',
        'error_password_mismatch' => 'New password and confirmation do not match.',
        'error_user_not_found' => 'User  not found.',
    ],
    'fa' => [
        'change_password' => 'تغییر رمز عبور',
        'old_password' => 'رمز عبور قدیمی',
        'new_password' => 'رمز عبور جدید',
        'confirm_password' => 'تکرار رمز عبور جدید',
        'submit' => 'تغییر رمز عبور',
        'success' => 'رمز عبور با موفقیت تغییر کرد.',
        'error_old_password' => 'رمز عبور قدیمی صحیح نیست.',
        'error_password_mismatch' => 'رمز عبور جدید و تکرار آن یکسان نیستند.',
        'error_user_not_found' => 'کاربر یافت نشد',
    ]
];



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $csrf_token_post = $_POST['csrf_token'];

    if ($csrf_token !== $csrf_token_post) {
        $error = 'توکن CSRF نامعتبر است.';
    } elseif ($new_password !== $confirm_password) {

        $error = 'رمز عبور جدید و تکرار آن یکسان نیستند.';

    } else {

        $conn = new PDO($dsn, $db_username, $db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $query = "SELECT * FROM adn2 WHERE username = '$username'";
        $stmt = $conn->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($old_password, $row['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE adn2 SET password = '$hashed_password', password_changed_at = NOW() WHERE username = '$username'";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $success = 'رمز عبور با موفقیت تغییر کرد.';
            } else {
                $error = 'رمز عبور قدیمی صحیح نیست.';
            }

        } else {
            $error = 'کاربر یافت نشد.';
        }
        $conn = null;
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $_SESSION['lang'] === 'fa' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $texts[$_SESSION['lang']]['change_password']; ?></title>
    <link rel="stylesheet" href="../css/admin_styles.css">
    <style>
    .adminin {
        max-width: 400px;
        margin: 40px auto;
        padding: 20px;
        background-color: #fff;
        border: 1px solid #ddd;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    label {
        display: block;
        margin-bottom: 10px;
    }
    .error {
        color: red;
        margin-bottom: 20px;
    }
    .success {
        color: green;
        margin-bottom: 20px;
    }
</style>
</head>
<body>
<div class="container">
        <h2><?php echo $texts[$_SESSION['lang']]['change_password']; ?> - <?php echo $username; ?></h2>
        <hr>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php elseif (isset($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <form action="" method="post">
            <label for="old_password"><?php echo $texts[$_SESSION['lang']]['old_password']; ?>:</label>
            <input type="password" id="old_password" name="old_password" required><br><br>
            <label for="new_password"><?php echo $texts[$_SESSION['lang']]['new_password']; ?>:</label>
            <input type="password" id="new_password" name="new_password" required><br><br>
            <label for="confirm_password"><?php echo $texts[$_SESSION['lang']]['confirm_password']; ?>:</label>
            <input type="password" id="confirm_password" name="confirm_password" required><br><br>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input class="buttonsub" type="submit" value="<?php echo $texts[$_SESSION['lang']]['submit']; ?>">
        </form>
    </div>
</body>
</html>
