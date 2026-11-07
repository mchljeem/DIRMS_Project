<?php
/** Safe, repeatable DIRMS upgrade for previously installed Bantay Sakuna databases. */
declare(strict_types=1);
function upgradeDirms(PDO $pdo): array {
    $changes=[];
    $exists=$pdo->query("SHOW COLUMNS FROM users LIKE 'username'")->fetch();
    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(32) NULL UNIQUE AFTER email");
        $changes[]='Added usernames to user accounts';
    }
    // Give existing accounts an immediately usable username without changing their email or password.
    // Names include numeric IDs so they cannot collide with regular usernames.
    $old=$pdo->query("SELECT id,role FROM users WHERE username IS NULL OR username='' ORDER BY id")->fetchAll();
    $assign=$pdo->prepare('UPDATE users SET username=? WHERE id=?');
    foreach ($old as $item) {
        $base='legacy_'.$item['id'];
        $assign->execute([$base,(int)$item['id']]);
    }
    if ($old) $changes[]='Assigned usernames to '.count($old).' existing accounts';
    if (!$pdo->query("SHOW COLUMNS FROM evacuation_centers LIKE 'updated_at'")->fetch()) {
        $pdo->exec("ALTER TABLE evacuation_centers ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
        $changes[]='Added center occupancy update times';
    }
    // An early example directory included an unverified provincial number. Disable ONLY that exact
    // legacy demo entry; never silently overwrite administrators' other contact entries.
    $oldLine=$pdo->prepare("UPDATE hotlines SET is_active=0 WHERE organization=? AND phone=? AND is_active=1");
    $oldLine->execute(['Laguna Provincial Emergency Hotline','0919 417 3698']);
    if ($oldLine->rowCount()) $changes[]='Disabled old unverified sample provincial phone';
    $stmt=$pdo->prepare("INSERT INTO site_settings(setting_key,setting_value) VALUES('site_name','DIRMS') ON DUPLICATE KEY UPDATE setting_value=IF(setting_value IN ('Bantay Sakuna','', 'BANTAY SAKUNA'), 'DIRMS', setting_value)");
    $stmt->execute();
    return $changes;
}
