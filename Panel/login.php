<?php
session_start();

require_once '../config/db-config.php';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: admin.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (empty($_SESSION['uuid'])) {
    $_SESSION['uuid'] = bin2hex(random_bytes(16)); 
}

function generateCaptcha() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $captcha = '';
    for ($i = 0; $i < 6; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    $_SESSION['captcha'] = $captcha;
    $_SESSION['captcha_expiry'] = time() + 300;
}

if (!isset($_SESSION['captcha']) || time() > $_SESSION['captcha_expiry']) {
    generateCaptcha();
}


if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; 
}

if (isset($_POST['language'])) {
    $_SESSION['lang'] = $_POST['language'];
}

$texts = [
    'en' => [
        'login' => 'Login to Admin Panel',
        'username' => 'Username',
        'password' => 'Password',
        'captcha' => 'Enter the captcha',
        'submit' => 'Login',
        'error' => 'Invalid username or password',
        'csrf_error' => 'Session expired. Please try again',
        'csrf_invalid' => 'Invalid CSRF token',
        'uuid_invalid' => 'Invalid UUID token',
        'captcha_invalid' => 'Invalid captcha code',
    ],
    'fa' => [
        'login' => 'ورود به پنل مدیریت',
        'username' => ':نام کاربری',
        'password' => ':رمز عبور',
        'captcha' => ':کد امنیتی را وارد کنید',
        'submit' => 'ورود',
        'error' => 'نام کاربری یا رمز عبور اشتباه است',
        'csrf_error' => 'جلسه منقضی شده است. لطفاً مجددا تلاش کنید',
        'csrf_invalid' => 'توکن CSRF نامعتبر است',
        'uuid_invalid' => 'توکن UUID نامعتبر است',
        'captcha_invalid' => 'کد امنیتی وارد شده صحیح نیست',
    ]
];

$error = '';

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $captchaInput = $_POST['captcha'];
    $csrf = $_POST['csrf_token'];
    $uuid = $_POST['uuid']; 

    if (!isset($_SESSION['csrf_token'])) {
        $error = 'جلسه منقضی شده است. لطفاً مجدداً تلاش کنید';
    } elseif ($csrf !== $_SESSION['csrf_token']) {
        $error = 'توکن CSRF نامعتبر است';
    } elseif ($uuid !== $_SESSION['uuid']) {
        $error = 'توکن UUID نامعتبر است';
    } elseif ($captchaInput !== $_SESSION['captcha']) {
        $error = 'کد امنیتی وارد شده صحیح نیست';
    } else {
        $query = "SELECT * FROM adn2 WHERE username = :username";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if (count($result) > 0) {
            $row = $result[0];
            if (password_verify($password, $row['password'])) {
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['name'] = $row['name'];
                $_SESSION['admin_level'] = $row['admin_level'];
                $_SESSION['status'] = $row['status'];
                $_SESSION['created_at'] = $row['created_at'];
                $_SESSION['last_login'] = date('Y-m-d H:i:s');
                $_SESSION['password_changed_at'] = $row['password_changed_at'];
                $_SESSION['last_activity'] = time();

                $query = "UPDATE adn2 SET last_login = NOW() WHERE username = :username";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->execute();

                header('Location: admin.php');
                exit();
            } else {
                $error = 'نام کاربری یا رمز عبور اشتباه است';
            }
        } else {
            $error = 'نام کاربری یا رمز عبور اشتباه است';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] === 'fa' ? 'fa' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $texts[$_SESSION['lang']]['login']; ?></title>
    <link rel="stylesheet" href="../css/admin_styles.css">
    <style>
        #captchaContainer {
            width: 220px;
            height: 60px;
            border: 1px solid #00ffcc; 
            border-radius: 10px; 
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(30, 30, 30, 0.9); 
            box-shadow: 0 0 15px rgba(0, 255, 204, 0.7); 
            margin: 20px auto; 
            position: relative; 
            overflow: hidden; 
        }

        #captchaCanvas {
            width: 180px;
            height: 50px; 
            background-color: rgba(0, 0, 0, 0.6); 
            border: 1px solid #00ffcc; 
            border-radius: 8px; 
            backdrop-filter: blur(5px); 
            position: relative; 
            z-index: 1; 
        }

        #captchaContainer::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border-radius: 15px; 
            background: linear-gradient(45deg, rgba(0, 255, 204, 0.2), rgba(255, 0, 150, 0.2)); 
            z-index: 0; 
            filter: blur(20px); 
        }

    </style>
</head>
<body>
    <main>
    <h1><?php echo $texts[$_SESSION['lang']]['login']; ?></h1>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <label for="username"><?php echo $texts[$_SESSION['lang']]['username']; ?></label>
            <input type="text" id="username" name="username" required><br><br>
            <label for="password"><?php echo $texts[$_SESSION['lang']]['password']; ?></label>
            <input type="password" id="password" name="password" required><br><br>
            <label for="captcha"><?php echo $texts[$_SESSION['lang']]['captcha']; ?></label>
            <div id="captchaContainer">
                <canvas id="captchaCanvas" width="200" height="50"></canvas>
            </div>
            <input type="text" id="captchaInput" name="captcha" required><br><br>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="uuid" value="<?php echo $_SESSION['uuid']; ?>">
            <button type="submit"><?php echo $texts[$_SESSION['lang']]['submit']; ?></button>
        </form>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <select name="language" onchange="this.form.submit()">
                <option value="en" <?php echo $_SESSION['lang'] === 'en' ? 'selected' : ''; ?>>English</option>
                <option value="fa" <?php echo $_SESSION['lang'] === 'fa' ? 'selected' : ''; ?>>فارسی</option>
            </select>
        </form>
    </main>
    <script>
    const canvas = document.getElementById('captchaCanvas');
    const ctx = canvas.getContext('2d');
    let currentCaptcha = '<?php echo $_SESSION['captcha']; ?>';

    function drawCaptcha(captcha) {
        ctx.fillStyle = getRandomPastelColor();
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        addBackgroundPattern();
        addNoise();
        
        for (let i = 0; i < captcha.length; i++) {
            ctx.save();
            const yOffset = Math.sin(i) * 10; 
            ctx.translate(20 + i * 30, 35 + yOffset);
            ctx.rotate(Math.random() * (Math.PI / 6) - Math.PI / 12);
            ctx.fillStyle = getRandomTextColor(); 
            ctx.font = getRandomFontSize() + 'px Arial'; 
            ctx.fillText(captcha[i], 0, 0);
            ctx.strokeText(captcha[i], 0, 0);
            ctx.restore();
        }

        addLineNoise(); 
    }

    function getRandomFontSize() {
        return Math.floor(Math.random() * 10) + 20; 
    }

    function addNoise() {
        for (let i = 0; i < 70; i++) {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.1)'; 
            ctx.fillRect(Math.random() * canvas.width, Math.random() * canvas.height, 2, 2);
        }
    }

    function addLineNoise() {
        for (let i = 0; i < 10; i++) { 
            ctx.strokeStyle = getRandomLineColor(); 
            ctx.lineWidth = Math.random() * 3 + 1; 

            ctx.beginPath();
            ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height);
            const endX = Math.random() * canvas.width;
            const endY = Math.random() * canvas.height;

            const controlX = (endX + Math.random() * 50) / 2;
            const controlY = (endY + Math.random() * 50) / 2;

            ctx.quadraticCurveTo(controlX, controlY, endX, endY); 
            ctx.stroke();
        }
    }

    function addBackgroundPattern() {
        ctx.fillStyle = 'rgba(255, 255, 255, 0.1)';
        for (let i = 0; i < 100; i++) {
            ctx.fillRect(Math.random() * canvas.width, Math.random() * canvas.height, 2, 2);
        }
    }

    function getRandomTextColor() {
        const colors = [
            '#FFFFFF', '#FFD700', '#FF6347', '#00FA9A', '#1E90FF'
        ];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    function getRandomLineColor() {
        return 'rgba(0, 0, 0, ' + (Math.random() * 0.3 + 0.1) + ')'; 
    }

    function getRandomPastelColor() {
        const colors = [
            'rgba(173, 216, 230, 0.8)', 
            'rgba(158, 216, 211, 0.8)', 
            'rgba(211, 211, 211, 0.8)', 
            'rgba(176, 224, 230, 0.8)', 
            'rgba(240, 248, 255, 0.8)', 
            'rgba(108, 162, 206, 0.8)'
        ];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    drawCaptcha(currentCaptcha); 
</script>

</body>
</html>
