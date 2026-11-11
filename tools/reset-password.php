<?php
if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit();
}
require __DIR__ . "/../app/bootstrap.php";
$email = $argv[1] ?? "";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php tools/reset-password.php user@example.com\n");
    exit(1);
}
$u = one("SELECT id,name FROM users WHERE email=?", [$email]);
if (!$u) {
    fwrite(STDERR, "Account not found.\n");
    exit(1);
}
fwrite(
    STDOUT,
    "Verify the account owner's identity before continuing.\nEnter new password (visible in this terminal): ",
);
$password = trim(fgets(STDIN));
if (strlen($password) < 10) {
    fwrite(STDERR, "Use at least 10 characters.\n");
    exit(1);
}
q("UPDATE users SET password=?,session_version=session_version+1 WHERE id=?", [
    password_hash($password, PASSWORD_DEFAULT),
    $u["id"],
]);
fwrite(STDOUT, "Password updated.\n");
