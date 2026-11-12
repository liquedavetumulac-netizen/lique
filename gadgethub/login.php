<?php
require 'config.php';

if (!empty($_SESSION['user_id'])) {
    redirect('index.php');
}
if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}
$error = ''; 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['login_csrf'], $token)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            unset($_SESSION['login_csrf']);
            redirect('index.php');
        }
        $error = 'Incorrect username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in | GadgetHub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/login.css">
</head>
<body>
<main class="login-shell">
  <section class="login-visual">
    <div class="visual-brand"><span class="brand-mark">G</span><span>gadget<span>hub</span></span></div>
    <div class="visual-copy">
      <span class="eyebrow">SMARTER INVENTORY. BETTER BUSINESS.</span>
      <h1>Your store,<br>under control.</h1>
      <p>Manage products, monitor stock, and keep every sale organized in one place.</p>
    </div>
    <div class="visual-foot">GADGETHUB MANAGEMENT SUITE</div>
  </section>
  <section class="login-panel">
    <div class="login-card">
      <div class="mobile-brand"><span class="brand-mark">G</span><b>gadget<span>hub</span></b></div>
      <span class="eyebrow">WELCOME BACK</span>
      <h2>Sign in to your account</h2>
      <p class="subtext">Enter your administrator credentials to continue.</p>
      <?php if ($error): ?><div class="error-message"><?= e($error) ?></div><?php endif; ?>
      <form method="post" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['login_csrf']) ?>">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" placeholder="Enter your username" autocomplete="username" required autofocus>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
        <button type="submit">Sign in <span>→</span></button>
      </form>
      <div class="secure-note"><span>●</span> Secure administrator access</div>
    </div>
    <footer>© <?= date('Y') ?> GadgetHub. All rights reserved.</footer>
  </section>
</main>
</body>
</html>
