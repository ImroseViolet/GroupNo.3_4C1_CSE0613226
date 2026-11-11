<?php $p = one(
    "SELECT p.*,c.name category FROM provider_profiles p JOIN categories c ON c.id=p.category_id WHERE p.user_id=?",
    [$u["id"]],
);
if (!$p) {
    throw new RuntimeException("Provider profile not found.");
}
if ($page === "verification"): ?>
<div class="split"><section class="card form-stack"><h2>Verification status</h2><?php echo badge(
    $p["verification"],
); ?><p class="muted"><?php echo e(
    [
        "Pending" =>
            "Your application is pending. Upload your qualification and identity document for review.",
        "Under Review" => "An administrator is reviewing your application.",
        "Approved" =>
            "Your profile is verified. You may accept requests for your approved service category.",
        "Rejected" =>
            "Please review the reason below and resubmit your document.",
        "Suspended" =>
            "Your provider access is suspended. Contact your project administrator.",
    ][$p["verification"]],
); ?></p><?php if ($p["rejection_reason"]) {
    echo '<div class="notice error">' . e($p["rejection_reason"]) . "</div>";
} ?><dl class="detail-list"><div><dt>Category</dt><dd><?php echo e(
    $p["category"],
); ?></dd></div><div><dt>Qualification</dt><dd><?php echo e(
    $p["qualification"],
); ?></dd></div><div><dt>Submitted document</dt><dd><?php echo e(
    $p["document_name"] ?: "Not submitted",
); ?></dd></div></dl></section><form method="post" enctype="multipart/form-data" class="card form-stack"><?php echo csrf(); ?><input type="hidden" name="action" value="upload_document"><h2>Submit documents</h2><p class="muted">Combine your identity and relevant qualifications into one PDF, or submit a clear image. Only the administrator can download it.</p><div class="field"><label for="document">PDF, JPG or PNG · maximum 5 MB</label><input type="file" id="document" name="document" accept="application/pdf,image/jpeg,image/png" required></div><button class="btn">Submit for Review</button><small>Submitting a new document returns your profile to Pending verification.</small></form></div>
<?php elseif ($page === "schedule"): ?>
<div class="split"><form method="post" class="card form-stack"><?php echo csrf(); ?><input type="hidden" name="action" value="availability"><h2>Availability & service profile</h2><?php
field("Available days and times", "availability", "text", $p["availability"]);
field("Hourly Rate (BDT)", "rate", "number", $p["rate"]);
?><div class="field"><label for="bio">About you</label><textarea name="bio" id="bio" maxlength="2000"><?php echo e(
    $p["bio"],
); ?></textarea></div><button class="btn">Save Availability</button><small>Availability is displayed as text. Accepted overlapping bookings are blocked by the server.</small></form><section class="card stack"><h2>Upcoming visits</h2><?php foreach (
    bookings()
    as $b
) {
    if (in_array($b["status"], ["Confirmed", "On the Way", "In Progress"])) {
        echo "<div><strong>" .
            e($b["elder_name"]) .
            '</strong><p class="muted">' .
            e($b["service_date"] . " " . substr($b["start_time"], 0, 5)) .
            "</p>" .
            btn("Task Details", "details", ["id" => $b["id"]], "outline") .
            "</div>";
    }
} ?></section></div>
<?php elseif ($page === "clients"): ?><div class="cards"><?php foreach (
    rows(
        "SELECT DISTINCT u.id,u.name,u.phone,u.address FROM users u JOIN bookings b ON b.elder_id=u.id WHERE b.provider_id=? AND b.status IN ('Confirmed','On the Way','In Progress','Completed')",
        [$u["id"]],
    )
    as $c
): ?><article class="card stack"><h2><?php echo e(
    $c["name"],
); ?></h2><p class="muted"><?php echo e(
    $c["address"],
); ?></p><a class="btn outline" href="tel:<?php echo e(
    preg_replace("/[^+0-9]/", "", $c["phone"]),
); ?>">Call Client</a></article><?php endforeach; ?></div>
<?php else:$bs = array_values(
        array_filter(bookings(), fn($b) => $b["status"] === "Completed"),
    ); ?><div class="stats"><article class="card stat"><small>Completed services</small><strong><?php echo count(
    $bs,
); ?></strong></article><article class="card stat"><small>Recorded earnings</small><strong><?php echo money(
    array_sum(array_map(fn($b) => $b["rate"] * $b["duration"] - 100, $bs)),
); ?></strong></article></div><div class="notice">Earnings follow the Figma example: service fee minus BDT 100 provider platform fee. This is a ledger, not a payout service.</div><?php booking_table(
    $bs,
);endif; ?>
