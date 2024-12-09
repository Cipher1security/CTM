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
        'create_account' => 'Create Account',
        'username' => 'Username',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'name' => 'Name',
        'admin_level' => 'Admin Level',
        'submit' => 'Create',
        'error_empty_fields' => 'Please fill in all fields.',
        'error_password_mismatch' => 'Passwords do not match.',
        'error_username_taken' => 'This username is already taken.',
        'error_weak_password' => 'Weak password. It must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, and one number.',
        'success' => 'Admin registered successfully.',
        'error_registration' => 'Error in admin registration: ',
    ],
    'fa' => [
        'create_account' => 'ساخت حساب',
        'username' => 'یوزرنیم',
        'password' => 'رمز',
        'confirm_password' => 'تکرار رمز',
        'name' => 'نام',
        'admin_level' => 'سطح ادمین',
        'submit' => 'ساخت',
        'error_empty_fields' => 'لطفاً همه فیلدها را پر کنید.',
        'error_password_mismatch' => 'رمزهای عبور مطابقت ندارند.',
        'error_username_taken' => 'این یوزرنیم قبلاً استفاده شده است.',
        'error_weak_password' => 'رمز عبور ضعیف است. باید حداقل 8 کاراکتر داشته باشد و شامل حداقل یک حرف بزرگ، یک حرف کوچک و یک عدد باشد.',
        'success' => 'ادمین با موفقیت ثبت‌نام شد.',
        'error_registration' => 'خطا در ثبت‌نام ادمین: ',
    ]
];


$conn = new PDO($dsn, $db_username, $db_password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $name = $_POST['name'];
    $admin_level = $_POST['admin_level'];

    if (empty($username) || empty($password) || empty($confirm_password) || empty($name) || empty($admin_level)) {
        echo "<div class='alert alert-danger'>لطفاً همه فیلدها را پر کنید.</div>";
    } elseif ($password != $confirm_password) {
        echo "<div class='alert alert-danger'>رمزهای عبور مطابقت ندارند.</div>";
    } else {
        $query_check_username = "SELECT * FROM adn2 WHERE username = '$username'";
        $result_check_username = $conn->query($query_check_username);

        if ($result_check_username->rowCount() > 0) {
            echo "<div class='alert alert-danger'>این یوزرنیم قبلاً استفاده شده است.</div>";
        } else {
            $password_strength = password_strength($password);

            if ($password_strength < 3) {
                echo "<div class='alert alert-danger'>رمز عبور ضعیف است. باید حداقل 8 کاراکتر داشته باشد و شامل حداقل یک حرف بزرگ، یک حرف کوچک و یک عدد باشد.</div>";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $query = "INSERT INTO adn2 (username, password, name, admin_level, created_at, status) VALUES ('$username', '$hashed_password', '$name', '$admin_level', NOW(), 'active')";
                $result = $conn->query($query);

                if ($result) {
                    echo "<div class='alert alert-success'>ادمین با موفقیت ثبت‌نام شد.</div>";
                } else {
                    echo "<div class='alert alert-danger'>خطا در ثبت‌نام ادمین: " . $conn->errorInfo()[2] . "</div>";
                }
            }
        }
    }
}

function password_strength($password) {
    $strength = 0;

    if (strlen($password) >= 8) {
        $strength++;
    }

    if (preg_match('/[A-Z]/', $password)) {
        $strength++;
    }

    if (preg_match('/[a-z]/', $password)) {
        $strength++;
    }

    if (preg_match('/[0-9]/', $password)) {
        $strength++;
    }

    return $strength;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>" dir="<?php echo $_SESSION['lang'] === 'fa' ? 'rtl' : 'ltr'; ?>">
<head>
<title><?php echo $texts[$_SESSION['lang']]['create_account']; ?></title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/admin_styles.css">

</head>
<body>
    <div class="container">
        <h2><?php echo $texts[$_SESSION['lang']]['create_account']; ?></h2>
        <form action="" method="post">
            <label for="username"><?php echo $texts[$_SESSION['lang']]['username']; ?>:</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="password"><?php echo $texts[$_SESSION['lang']]['password']; ?>:</label>
            <input type="password" id="password" name="password" required>
            <br>
            <label for="confirm_password"><?php echo $texts[$_SESSION['lang']]['confirm_password']; ?>:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <br>
            <label for="name"><?php echo $texts[$_SESSION['lang']]['name']; ?>:</label>
            <input type="text" id="name" name="name" required>
            <br>
            <label for="admin_level"><?php echo $texts[$_SESSION['lang']]['admin_level']; ?>:</label>
            <select id="admin_level" name="admin_level" required>
                <option value="1">Admin</option>
                <option value="2">Admin 2</option>
                <option value="3">Admin 3</option>
            </select>
            <br>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="submit" name="register" value="<?php echo $texts[$_SESSION['lang']]['submit']; ?>" class="btn btn-primary">
        </form>
    </div>
</body>
</html>
