<?php
$pageTitle = 'Activity Logs';
require_once 'includes/header.php';
$rs = $conn->query("SELECT l.*,a.username FROM activity_logs l LEFT JOIN admins a ON a.id=l.admin_id ORDER BY l.id DESC LIMIT 200");
?>
<div class="card-panel">
    <h3>Audit Trail</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody><?php while ($r = $rs->fetch_assoc()): ?><tr>
                        <td><?= e($r['created_at']) ?></td>
                        <td><?= e($r['username']) ?></td>
                        <td><?= e($r['action']) ?></td>
                        <td><?= e($r['module']) ?></td>
                        <td><?= e($r['description']) ?></td>
                        <td><?= e($r['ip_address']) ?></td>
                    </tr><?php endwhile; ?></tbody>
        </table>
    </div>
</div>
<?php require 'includes/footer.php'; ?>