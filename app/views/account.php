<?php if ($page === "profile"): ?>
<div class="split"><section class="card stack"><div class="row"><?php echo avatar(
    $u["avatar"],
    "large",
); ?><div><h2><?php echo e($u["name"]); ?></h2><p class="muted"><?php echo e(
    ucfirst($u["role"]),
); ?></p><?php echo badge("Active"); ?></div></div><p><?php echo e($u["email"]); ?></p><p><?php echo e(
    $u["phone"],
); ?></p><p class="muted"><?php echo e(
    $u["address"],
); ?></p></section><form class="card form-stack" method="post"><?php echo csrf(); ?><input type="hidden" name="action" value="profile"><h2>Personal information</h2><?php
field("Full name", "name", "text", $u["name"]);
field("Phone number", "phone", "tel", $u["phone"]);
field("Address", "address", "text", $u["address"]);
?><button class="btn">Save Profile</button></form></div>
<?php elseif ($page === 'settings'): ?>
<div class="settings-grid">
<section class="card form-stack"><div class="row"><span class="tile"><?php echo icon('imgIconShieldCheck',24); ?></span><div><h2>Account Security</h2><p class="muted">Manage your password and signed-in sessions.</p></div></div>
<form class="form-stack" method="post"><?php echo csrf(); ?><input type="hidden" name="action" value="password"><h3>Change password</h3>
<?php field('Current Password','current_password','password'); field('New Password','password','password'); field('Confirm New Password','confirm_password','password'); ?>
<button class="btn">Update Password</button><small>Changing your password also signs out your other sessions.</small></form>
<hr><div class="stack"><h3>Session controls</h3><div class="actions"><?php action_form('logout', [], 'Log Out', 'outline'); action_form('logout_other_sessions', [], 'Log Out Other Sessions', 'danger', 'Sign out your account on all other browsers and devices?'); ?></div><small>Your current session stays active when you log out other sessions.</small></div></section>
<div class="stack"><section class="card stack"><div class="row"><span class="tile blue"><?php echo icon('imgIconContact',24); ?></span><h2>Help &amp; Support</h2></div><p class="muted">Read short instructions, find contact information, and submit or view service complaints.</p><?php echo btn('Open Help & Support','help',[],'outline'); ?></section>
<section class="card stack"><div class="row"><span class="tile"><?php echo icon('imgIconHeart1',24); ?></span><h2>About CareConnect</h2></div><p class="muted">Learn about CareConnect, our privacy notice, and what the SOS feature does.</p><?php echo btn('About & Privacy','about',[],'outline'); ?></section></div></div>
<?php elseif ($page === 'help'): $cfg=require __DIR__.'/../config.php'; ?>
<div class="split"><section class="card stack"><h2>Quick guide</h2><ol class="help-list">
<?php if($u['role']==='customer'): ?><li>Browse services, then compare verified caregivers after login.</li><li>Book for yourself or a customer who approved your connection.</li><li>Your request stays pending until the caregiver accepts it.</li><li>Open My Bookings to track progress or report a service problem.</li><li>Use Family Linking to share your invitation code and approve access.</li>
<?php elseif($u['role']==='provider'): ?><li>Submit your documents under Verification.</li><li>After approval, review Booking Requests and accept suitable visits.</li><li>Update each task through On the Way, In Progress and Completed.</li><li>Keep availability and contact details current.</li>
<?php else: ?><li>Review provider documents before approving service access.</li><li>Manage customer and caregiver accounts.</li><li>Review bookings, resolve complaints and check the audit log.</li><?php endif; ?></ol>
<div class="actions"><?php echo btn('View Bookings','bookings',[],'outline'); if($u['role']==='admin')echo btn('Manage Complaints','admin-complaints'); else echo btn('View My Complaints','my-complaints',[],'outline'); ?></div>
<?php if($u['role']==='customer'): ?><h3>Submit a complaint</h3><p>Open the relevant booking, scroll to <strong>Report a problem</strong>, describe the issue and select <strong>Submit Complaint</strong>.</p><?php elseif($u['role']==='provider'): ?><p>For a service problem, contact the support details shown alongside this guide.</p><?php endif; ?></section>
<aside class="card stack"><h2>Contact information</h2><?php if(!empty($cfg['support_email'])): ?><p>Email: <a href="mailto:<?php echo e($cfg['support_email']); ?>"><?php echo e($cfg['support_email']); ?></a></p><?php endif; ?>
<?php if(!empty($cfg['support_phone'])): ?><p>Phone: <a href="tel:<?php echo e(preg_replace('/[^+0-9]/','',$cfg['support_phone'])); ?>"><?php echo e($cfg['support_phone']); ?></a></p><?php endif; ?>
<?php if(empty($cfg['support_email'])&&empty($cfg['support_phone'])): ?><p class="muted">Support contact details are not available yet. Customers can submit a booking complaint for administrator review.</p><?php endif; ?><small>Support is not an emergency response service.</small><?php echo btn('About SOS','about',[],'outline'); ?></aside></div>
<?php elseif ($page === 'about'): ?>
<div class="stack about-content"><section class="card stack"><h2>About CareConnect</h2><p>CareConnect connects customers with verified service providers for daily assistance and care-related support. Customers can book for themselves or support someone who has approved their connection.</p><p class="muted">The platform brings service requests, progress updates, approved family access and complaints into one place.</p></section>
<section class="card stack"><h2>Privacy notice</h2><p>Account details, contact information and service records are used to coordinate care. Approved linked customers may access shared care information; assigned caregivers receive information needed for their visits. Administrators review verification documents and service issues.</p><p>You control who receives linked access. Review or revoke connections from Family Linking. Location is attached to SOS only after you grant browser permission.</p><p>This local build stores information in the configured database. Passwords are hashed. Calls are not recorded, and live video calling is not connected.</p></section>
<section class="card stack"><h2>What SOS does</h2><p>SOS records an alert and creates in-app notifications for customers approved to monitor your care. You must confirm before sending. Location is optional.</p><div class="notice error">SOS does not dispatch an ambulance, guarantee help will arrive, or replace emergency services. Automatic SMS, email and push delivery are not connected.</div><p>For urgent help, contact local emergency services or call an emergency contact directly.</p></section></div>
<?php elseif ($page === 'my-complaints'): ?>
<section class="stack"><div class="section-heading"><h2>Your submitted complaints</h2><?php echo btn('Find a Booking','bookings',[],'outline'); ?></div>
<?php $cs=rows('SELECT c.* FROM complaints c WHERE c.user_id=? ORDER BY c.id DESC',[$u['id']]); if(!$cs): ?><div class="card empty"><h2>No complaints submitted</h2><p>Customers can report an issue from the relevant booking.</p></div><?php endif; foreach($cs as $c): ?>
<article class="card stack"><div class="section-heading"><h3>Booking CC-<?php echo e($c['booking_id']); ?></h3><?php echo badge($c['status']); ?></div><p><?php echo e($c['message']); ?></p><small><?php echo e($c['created_at']); ?></small></article><?php endforeach; ?></section>
<?php elseif ($page === 'family'): ?>
<section class="notice"><strong>Your invitation code: <?php echo e($u['invite_code']); ?></strong><p>Share this code only with someone you want to access your care information. They must request access, and you must approve it.</p></section>
<form method="post" class="card form-stack"><?php echo csrf(); ?><input type="hidden" name="action" value="link"><h2>Connect with another customer</h2><div class="field"><label for="code">Their invitation code</label><input id="code" name="code" maxlength="32" required></div><button class="btn">Send Connection Request</button></form>
<h2>People requesting access to your care</h2><div class="cards">
<?php $incoming=rows('SELECT f.*,u.name FROM family_links f JOIN users u ON u.id=f.family_id WHERE f.elder_id=?',[$u['id']]); if(!$incoming)echo '<p class="muted">No access requests yet.</p>'; foreach($incoming as $l): ?>
<article class="card stack"><h3><?php echo e($l['name']); ?></h3><?php echo badge($l['status']); ?><div class="actions"><?php if($l['status']!=='Approved')action_form('link_decision',['id'=>$l['id'],'decision'=>'approve'],'Approve',''); action_form('link_decision',['id'=>$l['id'],'decision'=>'reject'],$l['status']==='Approved'?'Remove Access':'Reject','danger','Update access to your care information?'); ?></div></article><?php endforeach; ?></div>
<h2>Customers whose care you support</h2><div class="cards"><?php $outgoing=rows("SELECT f.*,CASE WHEN f.status='Approved' THEN u.name ELSE 'Customer connection' END name FROM family_links f JOIN users u ON u.id=f.elder_id WHERE f.family_id=?",[$u['id']]); if(!$outgoing)echo '<p class="muted">No outgoing connections yet.</p>'; foreach($outgoing as $l): ?>
<article class="card stack"><h3><?php echo e($l['name']); ?></h3><?php echo badge($l['status']); ?><p class="muted"><?php echo $l['status']==='Approved'?'You may view services and book for this customer.':'Waiting for their approval. Their care information remains private.'; ?></p></article><?php endforeach; ?></div>
<?php elseif ($page === "contacts"):
    $contacts = rows("SELECT * FROM contacts WHERE user_id=?", [$u["id"]]); ?>
<div class="split"><div class="stack"><?php
foreach (
    $contacts
    as $c
): ?><article class="card stack"><div class="row"><span class="tile"><?php echo icon(
    "imgIconContact",
    24,
); ?></span><div><h2><?php echo e($c["name"]); ?></h2><small><?php echo e(
    $c["relationship"],
); ?></small></div></div><a href="tel:<?php echo e(
    preg_replace("/[^+0-9]/", "", $c["phone"]),
); ?>"><?php echo e(
    $c["phone"],
); ?></a><div class="actions"><a class="btn outline" href="tel:<?php echo e(
    preg_replace("/[^+0-9]/", "", $c["phone"]),
); ?>">Call</a><?php action_form(
    "delete_contact",
    ["id" => $c["id"]],
    "Remove",
    "danger",
    "Remove this contact?",
); ?></div></article><?php endforeach;
if (!$contacts) {
    echo '<div class="notice">Add at least one emergency contact.</div>';
}
?></div><form method="post" class="card form-stack"><?php echo csrf(); ?><input type="hidden" name="action" value="contact"><h2>Add emergency contact</h2><?php
field("Full name", "name");
field("Relationship", "relationship");
field("Phone number", "phone", "tel");
?><button class="btn">Save Contact</button></form></div>
<?php
elseif ($page === "notifications"):
    $ns = rows("SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC", [
        $u["id"],
    ]); ?>
<div class="section-heading"><h2>Your updates</h2><?php action_form(
    "read_notifications",
    [],
    "Mark all read",
); ?></div><div class="stack"><?php
foreach (
    $ns
    as $n
): ?><article class="card"><div class="section-heading"><p><?php echo e(
    $n["message"],
); ?></p><?php if (!$n["is_read"]) {
    echo badge("New");
} ?></div><small><?php echo e($n["created_at"]); ?></small></article><?php endforeach;
if (!$ns) {
    echo '<div class="card empty"><h2>You’re all caught up</h2><p>Booking and family updates will appear here.</p></div>';
}
?></div>
<?php
elseif ($page === "emergency"): ?>
<div class="notice error">SOS alerts linked family inside CareConnect. This is not an ambulance, medical service or guarantee that help will arrive. External SMS and push notifications are not connected.</div><div class="split"><section class="card form-stack"><h2>Need Emergency Help?</h2><p class="muted">Contact someone you trust or send an alert to your linked family.</p><?php if (
    $u["role"] === "customer"
): ?><form method="post" class="form-stack" data-confirm="Send an SOS alert to your linked family now?"><?php echo csrf(); ?><input type="hidden" name="action" value="sos"><input type="hidden" name="latitude"><input type="hidden" name="longitude"><button type="button" class="btn outline" id="share-location">Attach My Location</button><small id="location-result" aria-live="polite">Location is shared only with your permission.</small><label class="checkline"><input type="checkbox" name="confirm" value="yes" required> I confirm that I want to send an SOS alert.</label><button class="btn danger large">Send SOS Alert</button></form><?php else: ?><p class="muted">Customers can send SOS alerts from their own accounts. You can review linked elder alerts in Notifications.</p><?php echo btn(
    "View Notifications",
    "notifications",
    [],
    "outline",
); endif; ?></section><aside class="card stack"><h2>Emergency contacts</h2><?php
$cs = rows("SELECT * FROM contacts WHERE user_id=?", [$u["id"]]);
if (!$cs) {
    echo '<p class="muted">No contacts added. Add a contact before you need help.</p>';
}
foreach ($cs as $c): ?><div><h3><?php echo e($c["name"]); ?></h3><p class="muted"><?php echo e(
    $c["relationship"],
); ?></p><a class="btn outline" href="tel:<?php echo e(
    preg_replace("/[^+0-9]/", "", $c["phone"]),
); ?>">Call <?php echo e($c["phone"]); ?></a></div><?php endforeach;
btn("Manage Contacts", "contacts", [], "outline")
?></aside></div>
<?php elseif ($page === "calls"): ?>
<div class="split"><section class="call-placeholder"><?php echo avatar(
    "design-imgPhotoRahim.svg",
); ?><h2 style="margin-top:20px">Stay connected with family</h2><p>Live video calling is not configured in this build. No camera or microphone is active.</p><p>A WebRTC signaling server and STUN/TURN configuration are needed to enable real calls between devices.</p></section><aside class="card stack"><h2>Linked family contacts</h2><?php
$people =
    $u["role"] === "customer"
        ? rows(
            "SELECT u.* FROM users u JOIN family_links f ON f.family_id=u.id WHERE f.elder_id=? AND f.status='Approved'",
            [$u["id"]],
        )
        : elders();
foreach ($people as $p): ?><div><h3><?php echo e(
    $p["name"],
); ?></h3><a class="btn outline" href="tel:<?php echo e(
    preg_replace("/[^+0-9]/", "", $p["phone"]),
); ?>">Phone Call</a></div><?php endforeach;
if (!$people) {
    echo '<p class="muted">No approved family connections yet.</p>';
}
btn("Manage Connections", "family", [], "outline")
?></aside></div>
<?php endif; ?>
