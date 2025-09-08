<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_helper.php';
require_once 'includes/security_helper.php';

// Require login
requireLogin();
secureSession();

// Check if user is admin (you can modify this based on your user role system)
$user = getLoggedInUser();
if ($user['username'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$log_file = 'logs/security.log';
$logs = [];

if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $log_lines = array_filter(explode("\n", $log_content));
    $logs = array_reverse(array_slice($log_lines, -100)); // Last 100 entries
}

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Security Monitoring</h5>
                <div>
                    <span class="badge bg-info"><?= count($logs) ?> Events</span>
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No security events logged</h5>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Timestamp</th>
                                <th>IP Address</th>
                                <th>Event</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <?php
                            $parts = explode(' - ', $log, 4);
                            if (count($parts) >= 4) {
                                $timestamp = $parts[0];
                                $ip = str_replace('IP: ', '', $parts[1]);
                                $event = str_replace('Event: ', '', $parts[2]);
                                $details = str_replace('Details: ', '', $parts[3]);
                                
                                $badge_class = 'secondary';
                                if (strpos($event, 'ATTACK') !== false || strpos($event, 'FAILED') !== false) {
                                    $badge_class = 'danger';
                                } elseif (strpos($event, 'SUCCESS') !== false) {
                                    $badge_class = 'success';
                                } elseif (strpos($event, 'ERROR') !== false) {
                                    $badge_class = 'warning';
                                }
                            ?>
                            <tr>
                                <td><small><?= htmlspecialchars($timestamp) ?></small></td>
                                <td><code><?= htmlspecialchars($ip) ?></code></td>
                                <td><span class="badge bg-<?= $badge_class ?>"><?= htmlspecialchars($event) ?></span></td>
                                <td><small><?= htmlspecialchars($details) ?></small></td>
                            </tr>
                            <?php } endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>