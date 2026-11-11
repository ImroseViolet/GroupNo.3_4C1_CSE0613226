<?php if ($page === "admin-users" || $page === "admin-providers"):

    $isProvider = $page === "admin-providers";
    $items = $isProvider
        ? rows(
            "SELECT u.*,p.verification FROM users u JOIN provider_profiles p ON p.user_id=u.id",
        )
        : rows("SELECT * FROM users WHERE role='customer'");
    ?>
<div class="table-wrap" tabindex="0" role="region" aria-label="Accounts"><table><thead><tr><th>Name</th><th>Role</th><th>Phone</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach (
    $items
    as $item
): ?><tr><td><?php echo e($item["name"]); ?><small><?php echo e(
    $item["email"],
); ?></small></td><td><?php echo e($item["role"]); ?></td><td><?php echo e(
    $item["phone"],
); ?></td><td><?php echo badge(
    $item["active"] ? "Active" : "Suspended",
); ?> <?php echo $isProvider
     ? badge($item["verification"])
     : ""; ?></td><td><?php action_form(
    "user_active",
    ["id" => $item["id"]],
    $item["active"] ? "Suspend" : "Activate",
    "outline",
    "Change this account’s access?",
); ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php
elseif ($page === "admin-verification"): ?><div class="stack"><?php foreach (
    rows(
        "SELECT u.name,p.* FROM provider_profiles p JOIN users u ON u.id=p.user_id ORDER BY p.user_id DESC",
    )
    as $p
): ?><section class="card form-stack"><div class="section-heading"><h2><?php echo e(
    $p["name"],
); ?></h2><?php echo badge($p["verification"]); ?></div><p><?php echo e(
    $p["qualification"],
); ?> · <?php echo e($p["experience"]); ?> years experience</p><?php if (
     $p["document_name"]
 ): ?><a class="btn outline" href="<?php echo e(
    url("document", ["id" => $p["user_id"]]),
); ?>">Download Submitted Document</a><?php else: ?><p class="muted">No document submitted.</p><?php endif; ?><form method="post" class="form-stack"><?php echo csrf(); ?><input type="hidden" name="action" value="verify"><input type="hidden" name="id" value="<?php echo $p[
    "user_id"
]; ?>"><div class="form-grid"><div class="field"><label for="state-<?php echo $p[
    "user_id"
]; ?>">Review decision</label><select id="state-<?php echo $p[
    "user_id"
]; ?>" name="status"><?php foreach (
    ["Under Review", "Approved", "Rejected", "Suspended"]
    as $s
) {
    echo "<option " .
        ($s === $p["verification"] ? "selected" : "") .
        ">" .
        e($s) .
        "</option>";
} ?></select></div><div class="field"><label for="reason-<?php echo $p[
    "user_id"
]; ?>">Reason / review notes</label><input name="reason" id="reason-<?php echo $p[
    "user_id"
]; ?>" maxlength="255" value="<?php echo e(
    $p["rejection_reason"],
); ?>"></div></div><button class="btn">Save Decision</button></form></section><?php endforeach; ?></div>
<?php elseif ($page === "admin-complaints"): ?><div class="stack"><?php
$cs = rows(
    "SELECT c.*,u.name FROM complaints c JOIN users u ON u.id=c.user_id ORDER BY c.id DESC",
);
if (!$cs) {
    echo '<div class="card empty"><h2>No complaints</h2></div>';
}
foreach (
    $cs
    as $c
): ?><article class="card stack"><div class="section-heading"><h2>Booking CC-<?php echo $c[
    "booking_id"
]; ?></h2><?php echo badge($c["status"]); ?></div><p><?php echo e(
    $c["message"],
); ?></p><small>Submitted by <?php echo e($c["name"]); ?> · <?php echo e(
     $c["created_at"],
 ); ?></small><div class="actions"><?php echo btn("View Booking", "details", ["id" => $c["booking_id"]], "outline"); if ($c["status"] === "Open") {
    action_form("resolve", ["id" => $c["id"]], "Mark Resolved", "");
}; ?></div></article><?php endforeach;
?></div>
<?php elseif (
    $page === "admin-categories"
): ?><div class="split"><div class="stack"><?php foreach (
    rows("SELECT * FROM categories")
    as $c
): ?><article class="card"><h2><?php echo e($c["name"]); ?></h2><p class="muted"><?php echo e(
    $c["description"],
); ?></p><p>From <?php echo money(
    $c["rate"],
); ?> / hour</p></article><?php endforeach; ?></div><form method="post" class="card form-stack"><?php echo csrf(); ?><input type="hidden" name="action" value="category"><h2>Add category</h2><?php
field("Name", "name");
field("Description", "description");
field("Starting hourly rate", "rate", "number", 500);
?><button class="btn">Add Category</button></form></div>
<?php elseif (
    $page === "reports"
): ?><div class="section-heading"><h2>Service report</h2><button type="button" class="btn outline" onclick="window.print()">Print / Save PDF</button></div><div class="stats"><?php foreach (
    [
        "Completed Services" => one(
            "SELECT COUNT(*) n FROM bookings WHERE status='Completed'",
        )["n"],
        "Cancelled Services" => one(
            "SELECT COUNT(*) n FROM bookings WHERE status='Cancelled'",
        )["n"],
        "Open Complaints" => one(
            "SELECT COUNT(*) n FROM complaints WHERE status='Open'",
        )["n"],
        "Recorded Offline Payments" => money(
            one(
                "SELECT COALESCE(SUM(total),0) n FROM bookings WHERE payment_status='Paid'",
            )["n"],
        ),
    ]
    as $label => $n
): ?><article class="card stat"><small><?php echo e($label); ?></small><strong><?php echo e(
    $n,
); ?></strong></article><?php endforeach; ?></div><section class="card stack"><h2>Bookings by Service</h2><?php foreach (
    rows(
        "SELECT c.name,COUNT(b.id) n FROM categories c LEFT JOIN bookings b ON b.category_id=c.id GROUP BY c.id,c.name",
    )
    as $c
): ?><div class="section-heading"><span><?php echo e(
    $c["name"],
); ?></span><strong><?php echo e(
    $c["n"],
); ?></strong></div><?php endforeach; ?></section><?php booking_table(
    bookings(),
); ?>
<?php elseif (
    $page === "audit"
): ?><div class="table-wrap" tabindex="0" role="region" aria-label="Audit log"><table><thead><tr><th>Admin</th><th>Action</th><th>Timestamp</th></tr></thead><tbody><?php foreach (
    rows(
        "SELECT a.*,u.name FROM audit_logs a JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 200",
    )
    as $a
): ?><tr><td><?php echo e($a["name"]); ?></td><td><?php echo e($a["message"]); ?></td><td><?php echo e(
    $a["created_at"],
); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
