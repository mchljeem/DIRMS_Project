<?php
/** Local-only, idempotent upgrade for an existing Bantay Sakuna database. */
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/migration.php';
require_once __DIR__.'/app/community-seed.php';
header('X-Robots-Tag: noindex, nofollow');
if (!in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1','::1'], true)) {
    http_response_code(403);
    exit('Run this page on the local XAMPP computer.');
}
$message=''; $success=false; $adminExists=false;
try {
    $pdo=db();
    $adminExists=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()>0;
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        if (!hash_equals(csrfToken(),(string)($_POST['csrf']??''))) throw new RuntimeException('Session token expired.');
        if ($adminExists) {
            // Validate against the old schema before migrating: username column may not yet exist.
            $authenticated=false;
            if (!empty($_SESSION['user_id'])) {
                $q=$pdo->prepare("SELECT role,is_active FROM users WHERE id=? LIMIT 1");
                $q->execute([(int)$_SESSION['user_id']]);
                $u=$q->fetch();
                $authenticated=$u && $u['role']==='admin' && (int)$u['is_active']===1;
            }
            if (!$authenticated) {
                $email=strtolower(trim((string)($_POST['email']??'')));
                $password=(string)($_POST['password']??'');
                $q=$pdo->prepare("SELECT id,password_hash,is_active FROM users WHERE email=? AND role='admin' LIMIT 1");
                $q->execute([$email]); $u=$q->fetch();
                $authenticated=$u && (int)$u['is_active']===1 && password_verify($password,(string)$u['password_hash']);
                if ($authenticated) {session_regenerate_id(true);$_SESSION['user_id']=(int)$u['id'];}
            }
            if (!$authenticated) throw new RuntimeException('Valid existing administrator credentials are required for this upgrade.');
        }
        $changes=upgradeDirms($pdo);
        $added=seedCommunityDirectory($pdo);
        $message=(implode('; ',$changes)?:'Database schema already up to date.')
           .' Added '.$added['hotlines'].' hotline(s) and '.$added['tips'].' preparedness tip(s). Original reports, passwords and accounts were preserved.';
        $success=true;
    }
} catch(Throwable $e) {$message=$e->getMessage();}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>DIRMS Database Upgrade</title><link rel="stylesheet" href="assets/css/style.css?v=6"></head>
<body class="setup-page"><main class="setup-card">
<h1>DIRMS Database Upgrade</h1>
<p>One-time database upgrade: adds usernames, center update times and revised contact information. Existing user accounts, hashed passwords, alerts and reports are preserved.</p>
<?php if ($message): ?><p class="notice <?= $success?'':'danger' ?>"><?= htmlspecialchars($message,ENT_QUOTES) ?></p><?php endif; ?>
<?php if ($success): ?>
<a class="btn btn-blue" href="<?= $adminExists ? 'admin.php' : 'setup.php' ?>"><?= $adminExists?'Open Admin Dashboard':'Create First Administrator' ?> →</a>
<?php else: ?>
<form method="post" class="form-stack">
<input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken(),ENT_QUOTES) ?>">
<?php if($adminExists): ?>
<p>The database already has an admin account. To protect existing records, enter that admin's email and password to authorize changes (or run while already logged in as an admin).</p>
<label>Administrator Email<input type="email" name="email" autocomplete="username" placeholder="Existing admin email"></label>
<label>Administrator Password<span class="password-field"><input type="password" id="upgradePassword" name="password" autocomplete="current-password"><button type="button" class="password-eye" data-toggle-password="upgradePassword" aria-label="Show password">👁</button></span></label>
<?php else: ?>
<p>No administrator account exists yet. You may migrate the database, then visit <code>setup.php</code> to create the first administrator.</p>
<?php endif; ?>
<button type="submit" class="btn btn-blue">Upgrade Database Safely</button>
</form>
<?php endif; ?>
<p class="fineprint">Make a MySQL backup before upgrading. Only available on localhost. Remove this upgrade file once completed.</p>
</main><script src="assets/js/password-eye.js" defer></script></body></html>
