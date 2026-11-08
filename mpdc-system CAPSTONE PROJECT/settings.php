<?php
$pageTitle = 'Settings';
require_once 'includes/header.php';
$passwordError = '';
$passwordToken = $_SESSION['password_change_token'] ?? bin2hex(random_bytes(32));
$_SESSION['password_change_token'] = $passwordToken;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'change_password') {
        $submittedToken = $_POST['password_change_token'] ?? '';
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $adminId = current_admin_id();

        if (!is_string($submittedToken) || !hash_equals($passwordToken, $submittedToken)) {
            $passwordError = 'Hindi valid ang request. I-refresh ang page at subukan muli.';
        } elseif (strlen($newPassword) < 8) {
            $passwordError = 'Dapat hindi bababa sa 8 character ang bagong password.';
        } elseif ($newPassword !== $confirmPassword) {
            $passwordError = 'Hindi magkapareho ang bagong password at confirmation.';
        } else {
            $lookup = $conn->prepare('SELECT password_hash FROM admins WHERE id = ? AND is_active = 1 LIMIT 1');
            $lookup->bind_param('i', $adminId);
            $lookup->execute();
            $admin = $lookup->get_result()->fetch_assoc();
            $lookup->close();

            if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
                $passwordError = 'Mali ang kasalukuyang password.';
            } else {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $conn->prepare('UPDATE admins SET password_hash = ? WHERE id = ? AND is_active = 1');
                $update->bind_param('si', $passwordHash, $adminId);
                $update->execute();
                $updated = $update->affected_rows === 1;
                $update->close();

                if ($updated) {
                    unset($_SESSION['password_change_token']);
                    flash('success', 'Matagumpay na napalitan ang password mo.');
                    header('Location: settings.php');
                    exit;
                }

                $passwordError = 'Hindi na-update ang password. Subukan muli.';
            }
        }
    } else {
        foreach (['system_name', 'office_name', 'municipality', 'province', 'default_map_zoom', 'area_tolerance_percent'] as $key) {
            $v = trim($_POST[$key] ?? '');
            $s = $conn->prepare("INSERT INTO system_settings(setting_key,setting_value,setting_group,updated_by) VALUES(?,?, 'general',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_by=VALUES(updated_by)");
            $a = current_admin_id();
            $s->bind_param("ssi", $key, $v, $a);
            $s->execute();
        }
        flash('success', 'Settings saved.');
        header('Location: settings.php');
        exit;
    }
}
$vals = [];
foreach (['system_name', 'office_name', 'municipality', 'province', 'default_map_zoom', 'area_tolerance_percent'] as $k) $vals[$k] = setting($conn, $k);
?>
<div class="card-panel">
    <h3>System Settings</h3>
    <form method="post" class="row g-3"><?php foreach ($vals as $k => $v): ?><div class="col-md-6"><label><?= ucwords(str_replace('_', ' ', $k)) ?></label><input class="form-control" name="<?= e($k) ?>" value="<?= e($v) ?>"></div><?php endforeach; ?><div class="col-12"><button class="btn btn-primary">Save Settings</button></div>
    </form>
</div>
<div class="card-panel mt-4">
    <h3>Change Password</h3>
    <p>Gamitin ang kasalukuyang password para mapalitan ang password ng iyong administrator account.</p>
    <button class="btn btn-primary" id="togglePasswordForm" type="button" aria-expanded="<?= $passwordError ? 'true' : 'false' ?>" aria-controls="passwordChangeForm">
        <?= $passwordError ? 'Cancel' : 'Change Password' ?>
    </button>
    <div id="passwordChangeForm" class="mt-3" <?= $passwordError ? '' : 'hidden' ?>>
        <?php if ($passwordError): ?><div class="alert alert-danger"><?= e($passwordError) ?></div><?php endif; ?>
        <form method="post" class="row g-3" autocomplete="off">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="password_change_token" value="<?= e($passwordToken) ?>">
            <div class="col-md-4">
                <label class="form-label" for="current_password">Current Password</label>
                <input class="form-control" id="current_password" type="password" name="current_password" autocomplete="current-password" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="new_password">New Password</label>
                <input class="form-control" id="new_password" type="password" name="new_password" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="confirm_password">Confirm New Password</label>
                <input class="form-control" id="confirm_password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="col-12"><button class="btn btn-primary">Save New Password</button></div>
        </form>
    </div>
</div>
<script>
const passwordToggle = document.getElementById('togglePasswordForm');
const passwordForm = document.getElementById('passwordChangeForm');
passwordToggle.addEventListener('click', function () {
    const isExpanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', String(!isExpanded));
    passwordForm.hidden = isExpanded;
    this.textContent = isExpanded ? 'Change Password' : 'Cancel';
});
</script>
<?php require 'includes/footer.php'; ?>