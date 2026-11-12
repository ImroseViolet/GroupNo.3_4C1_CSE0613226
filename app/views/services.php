<?php
if ($page === "services" || $page === "providers") {
    $cats = rows("SELECT * FROM categories WHERE active=1");
    if ($u) echo '<form class="card search-form" method="get"><input type="hidden" name="page" value="providers"><div class="field"><label for="search">Search caregivers</label><input id="search" name="search" placeholder="Search caregivers or keywords" value="' .
        e($_GET["search"] ?? "") .
        '"></div><div class="field"><label for="category">Service Category</label><select id="category" name="category"><option value="">All categories</option>';
    if ($u) {
    foreach ($cats as $c) {
        echo '<option value="' .
            $c["id"] .
            '" ' .
            ((string) $c["id"] === (string) ($_GET["category"] ?? "")
                ? "selected"
                : "") .
            ">" .
            e($c["name"]) .
            "</option>";
    }
    echo '</select></div><div class="field"><label for="location">Location</label><input id="location" name="location" placeholder="Area, Dhaka" value="' .
        e($_GET["location"] ?? "") .
        '"></div><button class="btn large">Search</button></form>';
    }
    if ($page === "services") {
        category_cards($cats, false);
    } else {
        $ps = array_filter(providers(), function ($p) {
            return (empty($_GET["category"]) ||
                (int) $p["category_id"] === (int) $_GET["category"]) &&
                (empty($_GET["search"]) ||
                    stripos(
                        $p["name"] . " " . $p["category"] . " " . $p["bio"],
                        (string) $_GET["search"],
                    ) !== false) &&
                (empty($_GET["location"]) ||
                    stripos($p["address"], (string) $_GET["location"]) !==
                        false);
        });
        echo '<div class="section-heading"><h2>' .
            count($ps) .
            ' verified caregivers</h2></div><div class="cards">';
        foreach ($ps as $p) {
            provider_card($p);
        }
        echo "</div>";
        if (!$ps) {
            echo '<div class="card empty"><h2>No suitable provider available</h2><p>Try another category or location.</p>' .
                btn("Clear filters", "providers", [], "outline") .
                "</div>";
        }
    }
} elseif ($page === "provider" || $page === "book") {
    $p = null;
    foreach (providers() as $item) {
        if ((int) $item["user_id"] === (int) ($_GET["id"] ?? 0)) {
            $p = $item;
        }
    }
    if (!$p) {
        throw new RuntimeException("Verified provider not found.");
    }
    if ($page === "provider"): ?>
 <div class="split"><div class="stack"><section class="card stack"><div class="row"><?php echo avatar(
     $p["avatar"],
     "large",
 ); ?><div><h1><?php echo e($p["name"]); ?></h1><p class="muted"><?php echo e(
    $p["qualification"],
); ?> · <?php echo e(
     $p["experience"],
 ); ?> yrs experience</p><span class="badge">✓ Verified</span></div></div><p><?php echo e(
     $p["bio"] ?:
     "Compassionate care and practical assistance for elders and their families.",
 ); ?></p></section><section class="card stack"><h2>Services & experience</h2><p><?php echo e(
    $p["category"],
); ?></p><p class="muted">Location: <?php echo e(
    $p["address"],
); ?></p><h2>Availability</h2><p><?php echo e(
    $p["availability"] ?: "Please request your preferred date and time.",
); ?></p></section><section class="card stack"><h2>Reviews</h2><?php
$rs = rows(
    "SELECT r.*,u.name FROM reviews r JOIN users u ON u.id=r.user_id JOIN bookings b ON b.id=r.booking_id WHERE b.provider_id=?",
    [$p["user_id"]],
);
if (!$rs) {
    echo '<p class="muted">No reviews yet.</p>';
}
foreach ($rs as $r) {
    echo "<div><strong>" .
        e($r["name"]) .
        '</strong><span class="stars"> ' .
        str_repeat("★", (int) $r["rating"]) .
        '</span><p class="muted">' .
        e($r["review"]) .
        "</p></div>";
}
?></section></div><aside class="card stack"><h2>Book this caregiver</h2><p><strong style="font-size:28px;color:var(--deep)"><?php echo money(
    $p["rate"],
); ?></strong> / hour</p><p class="muted">Send a request with your preferred schedule. The caregiver must accept before the booking is confirmed.</p><?php echo btn(
    "Book Service",
    $u ? "book" : "login",
    $u ? ["id" => $p["user_id"]] : [],
    "large full",
); ?><small>No online payment is charged.</small></aside></div>
 <?php else:$es = elders();
        if (
            !$es
        ): ?><div class="card empty"><h2>Connect with another customer</h2><p>A customer must approve your connection before you can book for them.</p><?php echo btn(
    "Family Linking",
    "family",
); ?></div><?php else: ?>
 <form method="post" class="split"><?php echo csrf(); ?><input type="hidden" name="action" value="book"><input type="hidden" name="provider_id" value="<?php echo $p[
    "user_id"
]; ?>"><div class="stack"><div class="card row"><?php echo avatar(
    $p["avatar"],
); ?><div><h2><?php echo e($p["name"]); ?></h2><p class="muted"><?php echo e(
    $p["category"],
); ?> · <?php echo money(
     $p["rate"],
 ); ?> / hour</p></div></div><section class="card form-stack"><h2>Service details</h2><div class="form-grid"><div class="field"><label for="elder_id">Who is this service for?</label><select id="elder_id" name="elder_id" required><?php foreach (
     $es
     as $elder
 ): ?><option value="<?php echo $elder["id"]; ?>"><?php echo e(
    $elder["name"],
); ?></option><?php endforeach; ?></select></div><div class="field"><label for="date">Date</label><input type="date" name="date" id="date" min="<?php echo date(
    "Y-m-d",
); ?>" value="<?php echo date(
    "Y-m-d",
    strtotime("+1 day"),
); ?>" required></div><div class="field"><label for="time">Start time</label><input type="time" name="time" id="time" value="10:00" required></div><div class="field"><label for="duration">Duration (hours)</label><input type="number" name="duration" id="duration" min="1" max="12" value="2" required></div><div class="field full"><label for="address">Service Location</label><input name="address" id="address" value="<?php echo e(
    $es[0]["address"],
); ?>" maxlength="255" required></div><div class="field full"><label for="notes">Care requirements & additional notes</label><textarea name="notes" id="notes" maxlength="2000" placeholder="Describe the assistance needed and any relevant instructions."></textarea></div></div></section></div><aside class="card form-stack"><h2>Price summary</h2><div><div class="summary-line"><span>Service fee / hour</span><strong><?php echo money(
    $p["rate"],
); ?></strong></div><div class="summary-line"><span>Platform fee</span><span>BDT 100</span></div><div class="summary-line total"><span>Total</span><span id="booking-total" data-rate="<?php echo e(
    $p["rate"],
); ?>"><?php echo money(
    $p["rate"] * 2 + 100,
); ?></span></div></div><p class="muted">Your booking stays pending until the caregiver accepts. Payment is tracked offline.</p><button class="btn large full">Send Booking Request</button></aside></form>
 <?php endif;endif;
} elseif ($page === "confirmation") {
    $b = booking(
        (int) ($_GET["id"] ?? 0),
    ); ?><section class="card success-center form-stack"><span class="tile"><?php echo icon(
    "imgIconShieldCheck",
    32,
); ?></span><h1>Booking request sent!</h1><p class="muted">We have notified <?php echo e(
    $b["provider_name"],
); ?> in the application. Your booking is confirmed only after acceptance.</p><?php echo badge(
     $b["status"],
 ); ?><dl class="detail-list" style="text-align:left"><div><dt>Booking ID</dt><dd>CC-<?php echo $b[
    "id"
]; ?></dd></div><div><dt>Caregiver</dt><dd><?php echo e(
    $b["provider_name"],
); ?></dd></div><div><dt>Date & time</dt><dd><?php echo e(
    $b["service_date"] . " " . substr($b["start_time"], 0, 5),
); ?></dd></div><div><dt>Total amount</dt><dd><?php echo money(
    $b["total"],
); ?></dd></div></dl><div class="actions"><?php echo btn("View Booking", "details", ["id" => $b["id"]]); echo btn("Back to Dashboard", "dashboard", [], "outline"); ?></div></section><?php
} elseif ($page === "bookings" || $page === "requests") {
    $all = bookings();
    $filter =
        (string) ($_GET["status"] ?? ($page === "requests" ? "Pending" : ""));
    echo '<div class="tabs">';
    foreach (
        [
            "" => "All",
            "Pending" => "Pending",
            "Confirmed" => "Confirmed",
            "In Progress" => "In Progress",
            "Completed" => "Completed",
            "Cancelled" => "Cancelled",
        ]
        as $key => $text
    ) {
        echo '<a class="tab ' .
            ($filter === $key ? "active" : "") .
            '" href="' .
            e(url($page, ["status" => $key])) .
            '">' .
            e($text) .
            "</a>";
    }
    echo "</div>";
    booking_table(
        array_values(
            array_filter($all, fn($b) => !$filter || $b["status"] === $filter),
        ),
    );
} elseif ($page === "details") {
    $b = booking((int) ($_GET["id"] ?? 0)); ?>
<div class="section-heading"><a href="<?php echo e(
    url("bookings"),
); ?>">← Back to <?php echo $u["role"] === "provider"
    ? "My Tasks"
    : "My Bookings"; ?></a><?php echo badge(
    $b["status"],
); ?></div><div class="split"><div class="stack"><section class="card stack"><div class="row"><?php echo avatar(
    $b["avatar"],
    "large",
); ?><div><h2><?php echo e($b["provider_name"]); ?></h2><p class="muted"><?php echo e(
    $b["category"],
); ?> · CC-<?php echo $b[
     "id"
 ]; ?></p></div></div><h2>Booking information</h2><dl class="detail-list"><?php foreach (
    [
        "Customer" => $b["elder_name"],
        "Requested by" => $b["requester_name"],
        "Date" => $b["service_date"],
        "Start time" => substr($b["start_time"], 0, 5),
        "Duration" => $b["duration"] . " hours",
        "Location" => $b["address"],
    ]
    as $key => $value
) {
    echo "<div><dt>" . e($key) . "</dt><dd>" . e($value) . "</dd></div>";
} ?></dl><h3>Care requirements</h3><p class="muted"><?php echo nl2br(
    e($b["notes"] ?: "No additional notes."),
); ?></p><?php if (
    $b["completion_notes"]
): ?><h3>Completion notes</h3><p><?php echo nl2br(
    e($b["completion_notes"]),
); ?></p><?php endif; ?></section><section class="card stack"><h2>Payment summary</h2><div><div class="summary-line"><span>Service fee (<?php echo e(
    $b["duration"],
); ?> hours)</span><span><?php echo money(
     $b["rate"] * $b["duration"],
 ); ?></span></div><div class="summary-line"><span>Platform fee</span><span>BDT 100</span></div><div class="summary-line total"><span>Total amount</span><span><?php echo money(
    $b["total"],
); ?></span></div></div><?php echo badge(
    $b["payment_status"],
); ?><small>Offline payment tracking only. No payment gateway is connected.</small><?php if (
    $u["role"] === "admin" &&
    $b["status"] === "Completed" &&
    $b["payment_status"] === "Unpaid"
) {
    action_form(
        "payment",
        ["id" => $b["id"]],
        "Record offline payment",
        "outline",
        "Confirm that this offline payment has actually been received.",
    );
} ?></section>
<?php if (in_array($u["role"], ["customer"])):
    if (
        $b["status"] === "Completed" &&
        !one("SELECT id FROM reviews WHERE booking_id=?", [$b["id"]])
    ): ?>
<form class="card form-stack" method="post"><?php echo csrf(); ?><input type="hidden" name="action" value="review"><input type="hidden" name="id" value="<?php echo $b[
    "id"
]; ?>"><h2>Rate this service</h2><div class="field"><label for="rating">Rating</label><select name="rating" id="rating" required><?php for (
    $i = 5;
    $i >= 1;
    $i--
) {
    echo '<option value="' . $i . '">' . $i . " stars</option>";
} ?></select></div><div class="field"><label for="review">Review</label><textarea id="review" name="review" maxlength="2000"></textarea></div><button class="btn">Submit Review</button></form><?php endif; ?><form class="card form-stack" method="post"><?php echo csrf(); ?><input type="hidden" name="action" value="complaint"><input type="hidden" name="id" value="<?php echo $b[
    "id"
]; ?>"><h2>Report a problem</h2><div class="field"><label for="message">Complaint details</label><textarea id="message" name="message" required maxlength="2000"></textarea></div><button class="btn outline">Submit Complaint</button><?php foreach (
    rows(
        "SELECT status,message FROM complaints WHERE booking_id=? AND user_id=?",
        [$b["id"], $u["id"]],
    )
    as $c
) {
    echo "<p>" . badge($c["status"]) . " " . e($c["message"]) . "</p>";
} ?></form><?php
endif; ?></div>
<aside class="stack"><section class="card stack"><h2>Actions</h2><a class="btn outline" href="tel:<?php echo e(
    preg_replace("/[^+0-9]/", "", $b["provider_phone"]),
); ?>">Call Caregiver</a><?php if ($u["role"] === "provider") {
    $next =
        [
            "Pending" => "Confirmed",
            "Confirmed" => "On the Way",
            "On the Way" => "In Progress",
            "In Progress" => "Completed",
        ][$b["status"]] ?? null;
    if (
        $next === "Completed"
    ): ?><form method="post" class="form-stack"><?php echo csrf(); ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?php echo $b[
    "id"
]; ?>"><input type="hidden" name="status" value="Completed"><div class="field"><label for="completion_notes">Service completion notes</label><textarea id="completion_notes" name="completion_notes" required maxlength="2000"></textarea></div><button class="btn">Submit Completion</button></form><?php elseif (
        $next
    ):
        action_form(
            "status",
            ["id" => $b["id"], "status" => $next],
            $next === "Confirmed"
                ? "Accept Request"
                : ($next === "In Progress"
                    ? "Start Service"
                    : "Mark On the Way"),
            "",
        );
    endif;
    if ($b["status"] === "Pending") {
        action_form(
            "status",
            ["id" => $b["id"], "status" => "Rejected"],
            "Decline Request",
            "danger",
        );
    }
} elseif (
    in_array($u["role"], ["customer"]) &&
    in_array($b["status"], ["Pending", "Confirmed"])
) {
    action_form(
        "status",
        ["id" => $b["id"], "status" => "Cancelled"],
        "Cancel Booking",
        "danger",
        "Cancel this booking?",
    );
} ?></section><section class="card stack"><h2>Booking status</h2><ol class="timeline"><?php foreach (
    rows("SELECT * FROM status_history WHERE booking_id=? ORDER BY id", [
        $b["id"],
    ])
    as $h
): ?><li><strong><?php echo e($h["status"]); ?></strong><small><?php echo e(
    $h["created_at"],
); ?></small></li><?php endforeach; ?></ol></section></aside></div>
<?php
} ?>
