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
if (empty($_SESSION['uuid'])) {
    header('Location: 403.html');
    exit();
}

$uuid_from_request = $_POST['uuid'] ?? null;
if ($uuid_from_request && $uuid_from_request !== $_SESSION['uuid']) {
    $errorPage = '../errors/403.html';
    header("Location: $errorPage");
    exit();
}
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; 
}

if (isset($_POST['language'])) {
    $_SESSION['lang'] = $_POST['language'];
}

$texts = [
    'en' => [
        'welcome' => 'Welcome',
        'username' => 'Username',
        'name' => 'Name',
        'admin_level' => 'Admin Level',
        'status' => 'Status',
        'created_at' => 'Created At',
        'last_login' => 'Last Login',
        'password_changed_at' => 'Password Changed At',
        'change_password' => 'Change Password',
        'logout' => 'Logout',
        'manager' => 'Manager',
        'accounts' => 'Accounts',
        'create_account' => 'Create Account',
        'manage_accounts' => 'Manage Accounts',
        'cloud_space' => 'Cloud Space',
        'manage_cloud_space' => 'Manage Cloud Space',
    ],
    'fa' => [
        'welcome' => 'خوش آمدید',
        'username' => 'نام کاربری',
        'name' => 'نام',
        'admin_level' => 'سطح دسترسی',
        'status' => 'وضعیت',
        'created_at' => 'تاریخ ایجاد حساب',
        'last_login' => 'اخرین ورود',
        'password_changed_at' => 'تاریخ تغییر رمز عبور',
        'change_password' => 'تغییر رمز عبور',
        'logout' => 'خروج',
        'manager' => 'مدیریت',
        'accounts' => 'حساب ها',
        'create_account' => 'ساخت حساب',
        'manage_accounts' => 'مدیریت حساب ها',
        'cloud_space' => 'فضای ابری',
        'manage_cloud_space' => 'مدریت فضای ابری',
    ]
];

include '../config/addb_config.php'; 

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_close($conn);

?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] === 'fa' ? 'fa' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $texts[$_SESSION['lang']]['manager']; ?></title>
    <style>
        @font-face {
        font-family: 'Vazir';
        src: url('../css/fonts/Vazir.woff2');
        }
body {
    font-family: 'Vazir';
    background-color: #0d0d0d;
    background-image: linear-gradient(135deg, rgba(20, 20, 20, 0.9), rgba(5, 5, 5, 0.9)));
    background-size: cover;
    color: #e0e0e0;
    direction: rtl;
    text-align: center;
    overflow-x: hidden;
}

h2 {
    color: #00ffcc;
    text-shadow: 1px 1px 5px rgba(0, 255, 204, 0.5);
    margin: 20px 0;
}

.adminin {
    max-width: 500px;
    margin: 40px auto;
    padding: 20px;
    border: 1px solid #444;
    border-radius: 10px;
    box-shadow: 0 0 25px rgba(0, 255, 204, 0.5);
    background-color: rgba(20, 20, 20, 0.9);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.adminin:hover {
    box-shadow: 0 0 40px rgba(0, 255, 204, 0.7);
}



a.button {
    display: inline-block;
    padding: 10px 20px;
    margin: 5px;
    font-size: 16px;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    transition: background-color 0.3s, transform 0.3s, box-shadow 0.3s;
    background: linear-gradient(45deg, #00e1ff, #007bff);
    box-shadow: 0 0 10px rgba(0, 238, 255, 0.5);
}

a.button:hover {
    box-shadow: 0 0 20px rgb(0, 217, 255);
    transform: translateY(-3px);
}





.img-box {
    border-radius: 50%;
    margin: 10px;
    object-fit: cover;
    transition: transform 0.3s;
}

.img-box:hover {
    transform: scale(1.1);
    box-shadow: 0 0 15px rgba(0, 255, 204, 0.5);
}

.Manage_box {
    margin: 20px;
    padding: 20px;
    border: 1px solid #444;
    border-radius: 10px;
    box-shadow: 0 0 25px rgba(0, 255, 204, 0.5);
    background-color: rgba(30, 30, 30, 0.9);
    backdrop-filter: blur(5px);
}
.Mg_box {
  margin: 20px;
  padding: 20px;
  border: 1px solid #444;
  border-radius: 10px;
  box-shadow: 0 0 25px rgba(0, 153, 255, 0.5);
  background-color: rgba(30, 30, 30, 0.9);
  backdrop-filter: blur(5px);
}

.Mg_box h3, .Mg_box h4 {
    color: #00ffcc;
}


.box:hover {
    transform: scale(1.05);
    box-shadow: 0 0 30px rgba(0, 255, 204, 0.7);
}

.rb {
    border: 0;
    height: 3px; 
    background: linear-gradient(to right, #00ffcc, #007bff); 
    margin: 20px 0;
    animation: color-change 2s infinite alternate;
}

@keyframes color-change {
    0% { background-color: #00ffcc; }
    100% { background-color: #007bff; }
}


a{
  color: #008cff; 

}
h1 {
  color: #00c3ff;
  text-align: center;
  text-shadow: 0 0 10px rgba(0, 110, 255, 0.7);
}
select {
  padding: 10px;
  border: 1px solid #555;
  border-radius: 4px;
  width: 100%;
  box-sizing: border-box;
  background-color: #222;
  color: #fff;
  transition: border-color 0.3s;
}
select:focus {
  border-color: #00ffcc;
  outline: none;
}
</style>
</head>
<body>
    <div class="adminin">
        <div class="adp1">
        </div>
        <h3><?php echo $texts[$_SESSION['lang']]['welcome']; ?> <?php echo $_SESSION['username']; ?></h3>  
        <hr>
        <p class="inp"><?php echo $texts[$_SESSION['lang']]['username']; ?>:  <?php echo $_SESSION['username']; ?> </p>
     
        <p class="inp"><?php echo $texts[$_SESSION['lang']]['name']; ?>:  <?php echo $_SESSION['name']; ?></p>
    
        <p class="inp"><?php echo $texts[$_SESSION['lang']]['admin_level']; ?>:  <?php echo $_SESSION['admin_level']; ?></p>
         
        <p class="inp"><?php echo $texts[$_SESSION['lang']]['status']; ?>:  <?php echo $_SESSION['status']; ?></p>
          
          <p class="inp"><?php echo $texts[$_SESSION['lang']]['created_at']; ?>:  <?php echo $_SESSION['created_at']; ?></p>
      
          <p class="inp"><?php echo $texts[$_SESSION['lang']]['last_login']; ?>:  <?php echo $_SESSION['last_login']; ?></p>
        
          <p class="inp"><?php echo $texts[$_SESSION['lang']]['password_changed_at']; ?>:  <?php echo $_SESSION['password_changed_at']; ?></p>
          
          <a class="button" href="change_password.php?csrf_token=<?php echo $csrf_token; ?>"><?php echo $texts[$_SESSION['lang']]['change_password']; ?></a>
          <br>
          <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" style="text-align: center; margin-top: 20px;">
        <select name="language" onchange="this.form.submit()">
            <option value="fa" <?php echo $_SESSION['lang'] === 'fa' ? 'selected' : ''; ?>>فارسی</option>
            <option value="en" <?php echo $_SESSION['lang'] === 'en' ? 'selected' : ''; ?>>English</option>
        </select>
    </form>

            <hr color="#00ffcc">
            <a href="logout.php" title="خروج">
            <img src="imegs/logout-icon.png" alt="statsu" width="40" height="40">
            </a>
    </div>
        <hr class="rb">


    <?php

    include '../config/addb_config.php'; 
    if (!$conn) {

        die("Connection failed: " . mysqli_connect_error());

    }
    $query = "SELECT COUNT(*) as admin_count FROM adn2";
    $result = mysqli_query($conn, $query);
    $admin_count = mysqli_fetch_assoc($result)['admin_count'];
    mysqli_close($conn);
    ?>
 <div class="Manage_box">
        <h1><?php echo $texts[$_SESSION['lang']]['manager']; ?></h1>
        <?php if ($_SESSION['admin_level'] == 1): ?>
        <div class="Mg_box">
            <img src="imegs/ad2.png" alt="accounts" width="100" height="100">
            <h3><?php echo $texts[$_SESSION['lang']]['accounts']; ?></h3>
            <h4><?php echo $texts[$_SESSION['lang']]['create_account']; ?></h4>
            <p><?php echo $admin_count; ?></p>
            <a class="button" href="create_admin.php?csrf_token=<?php echo $csrf_token; ?>"><?php echo $texts[$_SESSION['lang']]['create_account']; ?></a>
            <br>
            <a class="button" href="Manage_users.php?csrf_token=<?php echo $csrf_token; ?>"><?php echo $texts[$_SESSION['lang']]['manage_accounts']; ?></a>
            <br><hr>
        </div>
        <?php endif; ?>

        <?php if (in_array($_SESSION['admin_level'], [1, 2])): ?>
        <div class="Mg_box">
            <img src="imegs/up4.png" alt="cloud space" width="100" height="100">
            <h3><?php echo $texts[$_SESSION['lang']]['cloud_space']; ?></h3>
            <p>Cyber Turbo Manager</p>
            <a class="button" href="mg_cloud_space.php?csrf_token=<?php echo $csrf_token; ?>"><?php echo $texts[$_SESSION['lang']]['manage_cloud_space']; ?></a>
        </div>
        <?php endif; ?>
    </div>
    
</body>
</html>
