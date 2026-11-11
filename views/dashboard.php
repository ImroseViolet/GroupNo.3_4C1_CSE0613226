<?php if ($u["role"] === "admin"):
    $all = bookings(); ?>
<div class="stats"><?php foreach (
    [
        "Total Users" => one(
            "SELECT COUNT(*) n FROM users WHERE role='customer'",
        )["n"],
        "Total Providers" => one(
            "SELECT COUNT(*) n FROM users WHERE role='provider'",
        )["n"],
        "Total Bookings" => count($all),
        "Pending Verifications" => one(
            "SELECT COUNT(*) n FROM provider_profiles WHERE verification IN ('Pending','Under Review')",
        )["n"],
    ]
    as $label => $n
): ?><article class="card stat"><small><?php echo e($label); ?></small><strong><?php echo e(
    $n,
); ?></strong></article><?php endforeach; ?></div><div class="split"><section class="card stack"><h2>Booking Statistics</h2><p class="muted">Your latest service requests</p><?php foreach (
    rows("SELECT status,COUNT(*) n FROM bookings GROUP BY status")
    as $s
): ?><div class="section-heading"><span><?php echo e($s["status"]); ?></span><?php echo badge(
    (string) $s["n"],
); ?></div><?php endforeach; ?></section><section class="card stack"><h2>Review Providers</h2><p class="muted">Review submitted identity and qualification documents before approving service access.</p><?php echo btn(
    "Review now",
    "admin-verification",
); ?></section></div><div class="section-heading"><h2>Recent Bookings</h2><a href="<?php echo e(
    url("bookings"),
); ?>">View All →</a></div><?php booking_table(array_slice($all, 0, 5)); ?>
<?php
elseif ($u["role"] === "provider"):

    $pp = one("SELECT * FROM provider_profiles WHERE user_id=?", [$u["id"]]);
    $all = bookings();
    ?>
<div class="notice">Verification: <?php echo badge($pp["verification"]); ?> <?php echo e(
     $pp["verification"] === "Approved"
         ? "You can review and accept booking requests."
         : "Complete verification before accepting any service.",
 ); ?></div><div class="stats"><?php foreach (
    [
        "Pending Requests" => count(
            array_filter($all, fn($b) => $b["status"] === "Pending"),
        ),
        "Active Tasks" => count(
            array_filter(
                $all,
                fn($b) => in_array($b["status"], [
                    "Confirmed",
                    "On the Way",
                    "In Progress",
                ]),
            ),
        ),
        "Completed" => count(
            array_filter($all, fn($b) => $b["status"] === "Completed"),
        ),
        "Earnings" => money(
            array_sum(
                array_map(
                    fn($b) => $b["status"] === "Completed"
                        ? $b["rate"] * $b["duration"] - 100
                        : 0,
                    $all,
                ),
            ),
        ),
    ]
    as $label => $n
): ?><article class="card stat"><small><?php echo e($label); ?></small><strong><?php echo e(
    $n,
); ?></strong></article><?php endforeach; ?></div><div class="quick-grid"><a class="quick primary" href="<?php echo e(
    url("requests"),
); ?>"><span class="tile"><?php echo icon(
    "imgIconCalendar1",
    24,
); ?></span><div><strong>Booking Requests</strong><small>Review and accept new requests</small></div></a><a class="quick" href="<?php echo e(
    url("schedule"),
); ?>"><span class="tile"><?php echo icon(
    "imgIconCalendar",
    24,
); ?></span><div><strong>My Schedule</strong><small>Set your availability</small></div></a><a class="quick" href="<?php echo e(
    url("bookings"),
); ?>"><span class="tile"><?php echo icon(
    "imgIconGrid",
    24,
); ?></span><div><strong>My Tasks</strong><small>Update your service progress</small></div></a></div><h2>Upcoming Care</h2><?php booking_table(
    array_slice($all, 0, 6),
); ?>
<?php
else:

    $ps = providers();
    $all = bookings();
    $upcoming = array_values(
        array_filter(
            $all,
            fn($b) => in_array($b["status"], [
                "Confirmed",
                "On the Way",
                "In Progress",
                "Pending",
            ]),
        ),
    );
    ?>
<div class="quick-grid"><a class="quick primary" href="<?php echo e(
    url("services"),
); ?>"><span class="tile"><?php echo icon(
    "imgIconCalendar1",
    24,
); ?></span><div><strong>Book a Service</strong><small>Find and schedule a caregiver</small></div><span class="arrow"><?php echo icon(
    "imgIconArrowRight",
); ?></span></a><a class="quick danger" href="<?php echo e(
    url("emergency"),
); ?>"><span class="tile"><?php echo icon(
    "imgIconAlertCircle1",
    24,
); ?></span><div><strong>Emergency Help</strong><small>Alert your family and contacts</small></div><span class="arrow"><?php echo icon(
    "imgIconArrowRight1",
); ?></span></a><a class="quick" href="<?php echo e(
    url("calls"),
); ?>"><span class="tile blue"><?php echo icon(
    "imgIconVideo1",
    24,
); ?></span><div><strong>Video Call</strong><small>Stay connected with your family</small></div><span class="arrow"><?php echo icon(
    "imgIconArrowRight2",
); ?></span></a></div>
<div class="section-heading"><h2>Service Categories</h2><a href="<?php echo e(
    url("services"),
); ?>">View all →</a></div><?php category_cards(
    rows("SELECT * FROM categories WHERE active=1"),
); ?>
<div class="dashboard-grid"><section class="stack"><h2>Upcoming Booking</h2><?php if (
    $upcoming
):
    $b = $upcoming[0]; ?><article class="card"><div class="row"><?php echo avatar(
    $b["avatar"],
); ?><div><strong><?php echo e(
    $b["provider_name"],
); ?></strong><small style="display:block"><?php echo e(
    $b["category"],
); ?> · Home visit</small></div></div><div class="booking-info"><div class="row"><span class="tile"><?php echo icon(
     "imgIconCalendar2",
     18,
 ); ?></span><?php echo e(
    date("l, d M Y", strtotime($b["service_date"])),
); ?></div><div class="row"><span class="tile"><?php echo icon(
    "imgIconClock",
    18,
); ?></span><?php echo e(substr($b["start_time"], 0, 5)); ?> · <?php echo e(
     $b["duration"],
 ); ?> hours</div><div class="row"><span class="tile"><?php echo icon(
     "imgIconMapPin",
     18,
 ); ?></span><?php echo e(
    $b["address"],
); ?></div><div class="section-heading"><small>Status</small><?php echo badge(
    $b["status"],
); ?></div><?php echo btn(
    "View Details",
    "details",
    ["id" => $b["id"]],
    "full",
); ?></div></article><?php
else:
     ?><div class="card empty"><p>No upcoming booking.</p><?php echo btn(
    "Find a caregiver",
    "providers",
    [],
    "outline",
); ?></div><?php
endif; ?></section><section class="stack"><div class="section-heading"><h2>Recommended Caregivers</h2><a href="<?php echo e(
    url("providers"),
); ?>">View all →</a></div><div class="cards"><?php foreach (
    array_slice($ps, 0, 3)
    as $p
) {
    provider_card($p);
} ?></div></section></div>
<aside class="advisor"><span class="tile blue"><?php echo icon(
    "imgIconPhone",
    22,
); ?></span><div><strong>Not sure which service fits?</strong><p class="muted">Browse service descriptions and connect with approved providers.</p></div><?php echo btn(
    "Explore Services",
    "services",
    [],
    "outline",
); ?></aside>
<?php
endif; ?>
