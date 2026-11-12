<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#2e8b78"><title><?php echo e(
    $title,
); ?> · CareConnect</title><link rel="stylesheet" href="assets/styles.css"></head><body class="<?php echo $u &&
 $u["role"] === "admin"
     ? "admin"
     : ""; ?>"><a class="skip-link" href="#main">Skip to content</a>
<?php if ($public): ?>
<header class="public-header"><a href="<?php echo e(
    url("home"),
); ?>" class="brand"><span class="brandmark"><?php echo icon(
    "imgIconHeart",
); ?></span>CareConnect</a><nav class="public-nav" aria-label="Main navigation"><a class="optional" href="<?php echo e(
    url("home"),
); ?>">Home</a><a class="optional" href="<?php echo e(
    url("services"),
); ?>">Services</a><a href="<?php echo e(url("login")); ?>">Login</a><?php echo btn(
    "Get Started",
    "roles",
); ?></nav></header><main class="public-main" id="main">
<?php else:
    $nav = [
        ["dashboard", "Dashboard", "imgIconDash"],
        ["services", "Browse Services", "imgIconGrid"],
        ["bookings", "My Bookings", "imgIconCalendar"],
        ["family", "Family Linking", "imgIconUsers"],
        ["emergency", "Emergency Help", "imgIconAlertCircle"],
        ["calls", "Video Calls", "imgIconVideo"],
        ["notifications", "Notifications", "imgIconBell"],
        ["contacts", "Emergency Contacts", "imgIconContact"],
        ["profile", "Profile", "imgIconUser"],
        ["settings", "Settings", "imgIconSettings"],
    ];
    if ($u["role"] === "provider") {
        $nav = [
            ["dashboard", "Dashboard", "imgIconDash"],
            ["requests", "Booking Requests", "imgIconCalendar"],
            ["bookings", "My Tasks", "imgIconGrid"],
            ["schedule", "My Schedule", "imgIconCalendar"],
            ["clients", "My Clients", "imgIconUsers"],
            ["earnings", "Earnings", "imgIconBriefcase"],
            ["verification", "Verification", "imgIconShieldCheck"],
            ["notifications", "Notifications", "imgIconBell"],
            ["profile", "Profile", "imgIconUser"],
            ["settings", "Settings", "imgIconSettings"],
        ];
    }
    if ($u["role"] === "admin") {
        $nav = [
            ["dashboard", "Dashboard", "imgIconDash"],
            ["admin-users", "Users", "imgIconUsers"],
            ["admin-providers", "Providers", "imgIconUser"],
            ["admin-verification", "Verification", "imgIconShieldCheck"],
            ["bookings", "Bookings", "imgIconCalendar"],
            ["admin-complaints", "Complaints", "imgIconContact"],
            ["admin-categories", "Service Categories", "imgIconGrid"],
            ["reports", "Reports", "imgIconBriefcase"],
            ["audit", "Audit Log", "imgIconClock"],
            ["notifications", "Notifications", "imgIconBell"],
            ["settings", "Settings", "imgIconSettings"],
        ];
    }
    ?>
<aside id="sidebar" class="sidebar" aria-label="Sidebar"><button class="menu-close" aria-label="Close menu">×</button><a href="<?php echo e(
    url("dashboard"),
); ?>" class="brand"><span class="brandmark"><?php echo icon(
    "imgIconHeart",
); ?></span>CareConnect</a><?php if (
    in_array($u["role"], ["provider", "admin"])
): ?><small style="padding:0 14px 16px"><?php echo $u["role"] === "admin"
    ? "ADMIN PANEL"
    : "CARE PROVIDER"; ?></small><?php endif; ?><nav class="nav" aria-label="Dashboard navigation"><?php foreach (
    $nav
    as [$route, $label, $ico]
): ?><a class="<?php echo $page === $route ? "active " : ""; echo $route === "emergency" ? "emergency" : ""; ?>" <?php echo $page === $route ? 'aria-current="page"' : ""; ?> href="<?php echo e(
     url($route),
 ); ?>"><?php echo icon($ico); ?><span><?php echo e(
    $label,
); ?></span></a><?php endforeach; ?></nav><div class="sidebar-bottom"><form method="post"><?php echo csrf(); ?><input type="hidden" name="action" value="logout"><button class="logout"><?php echo icon(
    "imgIconLogOut",
); ?> Logout</button></form></div></aside><div id="scrim" class="scrim"></div><main class="main" id="main"><div class="content"><header class="topbar"><button id="menu-toggle" class="mobile-toggle" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false">☰</button><div class="greeting"><?php heading(
     $title,
     $subtitle,
 ); ?></div><div class="account"><a class="bell" href="<?php echo e(
    url("notifications"),
); ?>" aria-label="Notifications"><?php echo icon("imgIconBell"); ?></a><a href="<?php echo e(
    url("profile"),
); ?>" aria-label="My profile"><?php echo avatar(
    $u["avatar"],
    "small",
); ?></a><div class="account-copy"><strong><?php echo e(
    $u["name"],
); ?></strong><small><?php echo e(
    [
        "customer" => "Customer",
        
        "provider" => "Service Provider",
        "admin" => "Administrator",
    ][$u["role"]],
); ?></small></div></div></header>
<?php endif; ?>
<?php if ($error): ?><div class="notice error" role="alert"><?php echo e(
    $error,
); ?></div><?php endif; ?>
<?php if (isset($_SESSION["flash"])): ?><div class="notice" role="status"><?php echo e(
    $_SESSION["flash"],
); ?></div><?php unset($_SESSION["flash"]);endif; ?>
