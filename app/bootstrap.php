<?php
declare(strict_types=1);
date_default_timezone_set("Asia/Dhaka");
ini_set("session.use_strict_mode", "1");
session_set_cookie_params([
    "httponly" => true,
    "samesite" => "Lax",
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
]);
session_start();
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: same-origin");
header("Cache-Control: no-store");
function db(): PDO
{
    static $db;
    if (!$db) {
        $c = require __DIR__ . "/../config.php";
        $db = new PDO(
            "mysql:host={$c["host"]};port={$c["port"]};dbname={$c["database"]};charset=utf8mb4",
            $c["username"],
            $c["password"],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }
    return $db;
}
function q(string $sql, array $params = []): PDOStatement
{
    $s = db()->prepare($sql);
    $s->execute($params);
    return $s;
}
function one(string $sql, array $params = []): ?array
{
    return q($sql, $params)->fetch() ?: null;
}
function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}
function e($v): string
{
    return htmlspecialchars((string) ($v ?? ""), ENT_QUOTES, "UTF-8");
}
function url(string $page, array $args = []): string
{
    return "index.php?" . http_build_query(["page" => $page] + $args);
}
function go(string $page, array $args = []): void
{
    header("Location: " . url($page, $args));
    exit();
}
function user(): ?array
{
    static $u;
    if (!$u && isset($_SESSION['uid'])) {
        $u = one('SELECT * FROM users WHERE id=? AND active=1', [$_SESSION['uid']]);
        if ($u && (int)($_SESSION['session_version'] ?? 0) !== (int)($u['session_version'] ?? 0)) {
            unset($_SESSION['uid'], $_SESSION['session_version'], $_SESSION['csrf']);
            $u = null;
        }
    }
    return $u;
}
function must(array $roles = []): array
{
    $u = user();
    if (!$u) {
        go("login");
    }
    if ($roles && !in_array($u["role"], $roles, true)) {
        throw new RuntimeException("You do not have access to this action.");
    }
    return $u;
}
function csrf(): string
{
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf" value="' .
        e($_SESSION["csrf"]) .
        '">';
}
function check_csrf(): void
{
    if (
        !hash_equals(
            $_SESSION["csrf"] ?? "",
            (string) ($_POST["csrf"] ?? ""),
        ) ||
        empty($_SESSION["csrf"])
    ) {
        throw new RuntimeException(
            "Your session expired. Refresh and try again.",
        );
    }
}
function input(string $key, int $max = 2000): string
{
    $v = trim((string) ($_POST[$key] ?? ""));
    if (mb_strlen($v) > $max) {
        throw new RuntimeException("Input is too long.");
    }
    return $v;
}
function required(string $key, int $max = 2000): string
{
    $v = input($key, $max);
    if ($v === "") {
        throw new RuntimeException("Please complete all required fields.");
    }
    return $v;
}
function notify(int $id, string $message): void
{
    q("INSERT INTO notifications(user_id,message) VALUES(?,?)", [
        $id,
        $message,
    ]);
}
function audit(string $message): void
{
    q("INSERT INTO audit_logs(user_id,message) VALUES(?,?)", [
        user()["id"],
        $message,
    ]);
}
function flash(string $message): void
{
    $_SESSION["flash"] = $message;
}
function money($value): string
{
    return "BDT " . number_format((float) $value);
}
function badge(string $text): string
{
    return '<span class="badge ' .
        (in_array($text, ["Rejected", "Cancelled", "Disputed", "Suspended"])
            ? "danger"
            : "") .
        '">' .
        e($text) .
        "</span>";
}
function can_elder(int $id): bool
{
    $u = must();
    if ($u['role'] !== 'customer') return false;
    return (int)$u['id'] === $id || (bool)one("SELECT id FROM family_links WHERE family_id=? AND elder_id=? AND status='Approved'", [$u['id'], $id]);
}
function elders(): array
{
    $u = must(['customer']);
    $linked = rows("SELECT u.* FROM users u JOIN family_links f ON f.elder_id=u.id WHERE f.family_id=? AND f.status='Approved' AND u.active=1", [$u['id']]);
    return array_merge([$u], $linked);
}
function booking(int $id): array
{
    $u = must();
    $b = one(
        "SELECT b.*,p.name provider_name,p.avatar,p.phone provider_phone,c.name category,e.name elder_name,u.name requester_name FROM bookings b JOIN users p ON p.id=b.provider_id JOIN categories c ON c.id=b.category_id JOIN users e ON e.id=b.elder_id JOIN users u ON u.id=b.requester_id WHERE b.id=?",
        [$id],
    );
    if (!$b) {
        throw new RuntimeException("Booking not found.");
    }
    if (
        $u["role"] !== "admin" &&
        !(
            $u["role"] === "provider" &&
            (int) $u["id"] === (int) $b["provider_id"]
        ) &&
        !can_elder((int) $b["elder_id"])
    ) {
        throw new RuntimeException("You do not have access to this booking.");
    }
    return $b;
}
function providers(): array
{
    must(["customer", "provider", "admin"]);
    return rows(
        "SELECT u.*,p.*,c.name category,(SELECT ROUND(AVG(r.rating),1) FROM reviews r JOIN bookings b ON b.id=r.booking_id WHERE b.provider_id=u.id) avg_rating FROM users u JOIN provider_profiles p ON p.user_id=u.id JOIN categories c ON c.id=p.category_id WHERE u.active=1 AND p.verification='Approved' AND c.active=1 ORDER BY u.id",
    );
}
function bookings(): array
{
    $u = must();
    $sql =
        "SELECT b.*,p.name provider_name,p.avatar,c.name category,e.name elder_name FROM bookings b JOIN users p ON p.id=b.provider_id JOIN categories c ON c.id=b.category_id JOIN users e ON e.id=b.elder_id";
    if ($u["role"] === "admin") {
        return rows($sql . " ORDER BY b.id DESC");
    }
    if ($u["role"] === "provider") {
        return rows($sql . " WHERE b.provider_id=? ORDER BY b.id DESC", [
            $u["id"],
        ]);
    }
    return rows($sql . " WHERE b.elder_id=? OR EXISTS (SELECT 1 FROM family_links f WHERE f.elder_id=b.elder_id AND f.family_id=? AND f.status='Approved') ORDER BY b.id DESC", [$u['id'], $u['id']]);
}
