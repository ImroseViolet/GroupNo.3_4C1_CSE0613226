<?php if ($page === "home"): ?>
<section class="hero"><div><span class="badge">Verified providers · Connected families</span><h1>Trusted Care for Your Loved Ones</h1><p>Find verified, compassionate elderly-care professionals near you. Book personal care and companionship with confidence — and peace of mind.</p><div class="actions"><?php echo btn("Book a Service", $u ? "services" : "roles", [], "outline large"); ?></div><p style="font-size:14px;margin-top:22px">✓ Verified caregivers &nbsp; ✓ Family monitoring &nbsp; ✓ Private & secure</p></div><div class="hero-art"><img src="<?php echo e(
    asset("imgIllustration", "8-69"),
); ?>" alt="CareConnect care illustration"></div></section>
<section class="stack"><div class="section-heading"><h2>Popular Services</h2><a href="<?php echo e(
    url("services"),
); ?>">View all →</a></div><?php category_cards(
    rows("SELECT * FROM categories WHERE active=1"),
    true,
); ?></section><section class="cards role-cards"><?php foreach (
    [
        "Tell us your needs" => "Choose care type, location and schedule.",
        "Match with caregivers" =>
            "Compare verified providers and send a request.",
        "Stay connected" => "Track the service and keep your family informed.",
    ]
    as $h => $p
): ?><article class="card"><h2><?php echo e(
    $h,
); ?></h2><p class="muted" style="margin-top:10px"><?php echo e(
    $p,
); ?></p></article><?php endforeach; ?></section><div class="notice">CareConnect coordinates services and sends family alerts. It is not a medical or ambulance service.</div>
<?php elseif ($page === "roles"): ?>
<div class="success-center"><?php heading(
    "How would you like to use CareConnect?",
    "Choose your role so we can tailor your experience.",
); ?></div><div class="cards role-cards"><?php foreach (
    [
        "customer" => ["Customer", "Book services for yourself or, with approval, a linked customer."],
        "provider" => ["Caregiver / Service Provider", "Get verified, receive requests and manage your care visits."],
    ]
    as $role => [$name, $desc]
): ?><article class="card stack"><span class="tile"><?php echo icon(
    $role === "provider" ? "imgIconBriefcase" : "imgIconUsers",
    26,
); ?></span><h2><?php echo e($name); ?></h2><p class="muted"><?php echo e($desc); ?></p><?php echo btn(
    "Continue as " . $name,
    "register",
    ["role" => $role],
); ?></article><?php endforeach; ?></div>
<?php elseif ($page === "login"): ?>
<div class="auth-layout"><section class="card auth-card form-stack"><div><span class="badge">Customer Login · Caregiver & Admin Access</span><h1 style="margin-top:16px">Welcome back</h1><p class="muted">Log in to manage care bookings and stay connected.</p></div><form method="post" class="form-stack"><?php echo csrf(); ?><input type="hidden" name="action" value="login"><?php field(
    "Email or Phone",
    "identity",
    "text",
    "",
    true,
); ?><div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div><div class="section-heading"><small>Private & secure</small><a href="<?php echo e(
    url("forgot"),
); ?>">Forgot Password?</a></div><button class="btn large full">Login</button></form><p class="muted">New to CareConnect? <a href="<?php echo e(
    url("roles"),
); ?>">Create Account</a></p></section><aside class="auth-aside"><span class="tile"><?php echo icon(
    "imgIconShieldCheck",
    28,
); ?></span><h2>Care you can trust,<br>every day</h2><p class="muted">Connect with verified providers and keep your family informed throughout every service.</p><ul><li>Verified caregivers</li><li>Customer approval and privacy</li><li>Simple booking and service tracking</li></ul></aside></div>
<?php elseif ($page === "register"):

    $role = (string) ($_GET["role"] ?? "customer");
    if (!in_array($role, ["customer", "provider"])) {
        $role = "customer";
    }
    ?>
<div class="auth-layout"><aside class="auth-aside"><span class="badge">Join CareConnect</span><h2><?php echo $role ===
"provider"
    ? "Become a verified caregiver"
    : "Trusted care starts here"; ?></h2><p>Create an account as <?php echo e(
    $role === "provider" ? "a Service Provider" : "a Customer",
); ?>.</p><p class="muted"><?php echo $role === "provider"
    ? "After registration, upload your documents for administrator review. Approval is required before accepting requests."
    : "As a customer, you can book for yourself. You need another customer’s approval before viewing or booking their care."; ?></p><a href="<?php echo e(
    url("roles"),
); ?>">Change role →</a></aside><section class="card auth-card form-stack"><h1>Create your account</h1><form method="post" class="form-stack"><?php echo csrf(); ?><input type="hidden" name="action" value="register"><input type="hidden" name="role" value="<?php echo e(
    $role,
); ?>"><div class="form-grid"><?php
field("Full Name", "name");
field("Email", "email", "email");
field("Phone", "phone", "tel");
field("Address", "address");
if (
    $role === "provider"
): ?><div class="field"><label for="category">Service Category</label><select id="category" name="category" required><?php foreach (
    rows("SELECT * FROM categories WHERE active=1")
    as $c
): ?><option value="<?php echo $c["id"]; ?>"><?php echo e(
    $c["name"],
); ?></option><?php endforeach; ?></select></div><?php
field("Years of experience", "experience", "number", 0);
field("Qualification", "qualification");
endif;
field("Password", "password", "password");
field("Confirm Password", "confirm_password", "password");
?></div><label class="checkline"><input type="checkbox" required> I agree to use my information for service coordination and verification.</label><button class="btn large full">Create Account</button></form><p>Already have an account? <a href="<?php echo e(
    url("login"),
); ?>">Log in</a></p></section></div>
<?php
else:
     ?>
<div class="card success-center stack"><h1>Forgot your password?</h1><p>Automated email/SMS reset is not connected in this local lab build.</p><p class="muted">Ask your project administrator to verify your identity and reset your password through the local maintenance process. No reset link has been sent.</p><?php echo btn(
    "Back to Login",
    "login",
    [],
    "outline",
); ?></div>
<?php
endif; ?>
