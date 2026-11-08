<?php
session_start();
require_once 'db.php';

if (!empty($_SESSION['admin_id'])) {
  header("Location: dashboard.php");
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $stmt = $conn->prepare("SELECT id, username, password_hash, full_name FROM admins WHERE username=? AND is_active=1 LIMIT 1");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $admin = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($admin && password_verify($password, $admin['password_hash'])) {
    $_SESSION['admin_id'] = (int)$admin['id'];
    $_SESSION['admin_name'] = $admin['full_name'];
    $u = $conn->prepare("UPDATE admins SET last_login_at=NOW() WHERE id=?");
    $u->bind_param("i", $admin['id']);
    $u->execute();
    $u->close();
    header("Location: dashboard.php");
    exit;
  }
  $error = "Invalid username or password.";
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>MPDC System Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-page">
  <div class="login-card">
    <div class="login-logo">MPDC</div>
    <h2>MPDC Property System</h2>
    <p>Land Property Records and Geotagging System</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <label>Username</label><input class="form-control mb-3" name="username" required autofocus>
      <label>Password</label><input class="form-control mb-4" type="password" name="password" required>
      <button class="btn btn-primary w-100">Sign In</button>
    </form>
  </div>
</body>

</html>