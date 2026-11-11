<?php
function handle_action(): void
{
    check_csrf();
    $a = input("action", 50);
    if ($a === "login") {
        $identity = required("identity", 190);
        $key = hash(
            "sha256",
            strtolower($identity) . "|" . ($_SERVER["REMOTE_ADDR"] ?? ""),
        );
        $attempt = one("SELECT * FROM login_attempts WHERE identity_hash=?", [
            $key,
        ]);
        if (
            $attempt &&
            $attempt["attempts"] >= 5 &&
            strtotime($attempt["last_attempt"]) > time() - 900
        ) {
            throw new RuntimeException(
                "Too many attempts. Try again in 15 minutes.",
            );
        }
        $u = one("SELECT * FROM users WHERE email=? OR phone=?", [
            $identity,
            $identity,
        ]);
        if (
            !$u ||
            !$u["active"] ||
            !password_verify(required("password", 200), $u["password"])
        ) {
            q(
                "INSERT INTO login_attempts(identity_hash,attempts,last_attempt) VALUES(?,1,NOW()) ON DUPLICATE KEY UPDATE attempts=IF(last_attempt<DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,attempts+1),last_attempt=NOW()",
                [$key],
            );
            throw new RuntimeException("Email/phone or password is incorrect.");
        }
        q("DELETE FROM login_attempts WHERE identity_hash=?", [$key]);
        session_regenerate_id(true);
        $_SESSION["uid"] = $u["id"];
        $_SESSION["session_version"] = (int)($u["session_version"] ?? 0);
        unset($_SESSION["csrf"]);
        go("dashboard");
    }
    if ($a === "register") {
        $role = required("role");
        if (!in_array($role, ["customer", "provider"], true)) {
            throw new RuntimeException("Select a valid role.");
        }
        $name = required("name", 120);
        $email = required("email", 190);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Enter a valid email.");
        }
        $phone = required("phone", 40);
        $password = required("password", 200);
        if (
            strlen($password) < 8 ||
            $password !== input("confirm_password", 200)
        ) {
            throw new RuntimeException(
                "Passwords must match and contain at least 8 characters.",
            );
        }
        if (
            one("SELECT id FROM users WHERE email=? OR phone=?", [
                $email,
                $phone,
            ])
        ) {
            throw new RuntimeException(
                "This email or phone is already registered.",
            );
        }
        $cat =
            $role === "provider"
                ? one("SELECT * FROM categories WHERE id=? AND active=1", [
                    (int) input("category"),
                ])
                : null;
        if ($role === "provider" && !$cat) {
            throw new RuntimeException("Choose a service category.");
        }
        db()->beginTransaction();
        try {
            q(
                "INSERT INTO users(name,email,phone,password,role,address,invite_code) VALUES(?,?,?,?,?,?,?)",
                [
                    $name,
                    $email,
                    $phone,
                    password_hash($password, PASSWORD_DEFAULT),
                    $role,
                    input("address", 255),
                    bin2hex(random_bytes(6)),
                ],
            );
            $id = (int) db()->lastInsertId();
            if ($role === "provider") {
                q(
                    "INSERT INTO provider_profiles(user_id,category_id,qualification,experience,rate,bio) VALUES(?,?,?,?,?,?)",
                    [
                        $id,
                        $cat["id"],
                        input("qualification", 255),
                        max(0, min(60, (int) input("experience"))),
                        $cat["rate"],
                        "",
                    ],
                );
            }
            db()->commit();
        } catch (Throwable $t) {
            db()->rollBack();
            throw $t;
        }
        session_regenerate_id(true);
        $_SESSION["uid"] = $id;
        $_SESSION["session_version"] = 0;
        go($role === "provider" ? "verification" : "dashboard");
    }
    $u = must();
    $uid = (int) $u["id"];
    if ($a === "logout") {
        $_SESSION = [];
        session_destroy();
        go("login");
    }
    if ($a === 'logout_other_sessions') {
        q('UPDATE users SET session_version=session_version+1 WHERE id=?', [$uid]);
        $_SESSION['session_version'] = (int)($u['session_version'] ?? 0) + 1;
        session_regenerate_id(true);
        flash('Other sessions have been signed out. This session remains active.');
        go('settings');
    }
    if ($a === "profile") {
        q("UPDATE users SET name=?,phone=?,address=? WHERE id=?", [
            required("name", 120),
            required("phone", 40),
            input("address", 255),
            $uid,
        ]);
        flash("Profile updated.");
        go("profile");
    }
    if ($a === "password") {
        if (
            !password_verify(required("current_password", 200), $u["password"])
        ) {
            throw new RuntimeException("Current password is incorrect.");
        }
        $p = required("password", 200);
        if (strlen($p) < 8 || $p !== input("confirm_password", 200)) {
            throw new RuntimeException(
                "Use matching passwords of at least 8 characters.",
            );
        }
        q("UPDATE users SET password=?,session_version=session_version+1 WHERE id=?", [
            password_hash($p, PASSWORD_DEFAULT),
            $uid,
        ]);
        session_regenerate_id(true);
        $_SESSION["session_version"] = (int)($u["session_version"] ?? 0) + 1;
        flash("Password updated. Other sessions have been signed out.");
        go("settings");
    }
    if ($a === "link") {
        must(["customer"]);
        $elder = one(
            "SELECT id FROM users WHERE invite_code=? AND role='customer' AND active=1",
            [required("code", 32)],
        );
        if ($elder && (int)$elder["id"] === $uid) throw new RuntimeException("Use another customer’s invitation code, not your own.");
        if (!$elder) {
            throw new RuntimeException("Invitation code not found.");
        }
        q(
            "INSERT INTO family_links(family_id,elder_id,status) VALUES(?,?,'Pending') ON DUPLICATE KEY UPDATE status=IF(status='Approved','Approved','Pending')",
            [$uid, $elder["id"]],
        );
        notify(
            (int) $elder["id"],
            $u["name"] .
                " requested family access. Open Family Linking to review.",
        );
        flash("Request sent. The other customer must approve access.");
        go("family");
    }
    if ($a === "link_decision") {
        must(["customer"]);
        $id = (int) input("id");
        $status = input("decision") === "approve" ? "Approved" : "Rejected";
        q("UPDATE family_links SET status=? WHERE id=? AND elder_id=?", [
            $status,
            $id,
            $uid,
        ]);
        flash("Family access updated.");
        go("family");
    }
    if ($a === "contact") {
        must(["customer"]);
        q(
            "INSERT INTO contacts(user_id,name,relationship,phone) VALUES(?,?,?,?)",
            [
                $uid,
                required("name", 120),
                required("relationship", 80),
                required("phone", 40),
            ],
        );
        flash("Contact added.");
        go("contacts");
    }
    if ($a === "delete_contact") {
        q("DELETE FROM contacts WHERE id=? AND user_id=?", [
            (int) input("id"),
            $uid,
        ]);
        go("contacts");
    }
    if ($a === "book") {
        must(["customer"]);
        $elder = (int) input("elder_id");
        if (!can_elder($elder)) {
            throw new RuntimeException("Choose yourself or an approved linked customer.");
        }
        $p = one(
            "SELECT p.*,u.id FROM provider_profiles p JOIN users u ON u.id=p.user_id JOIN categories c ON c.id=p.category_id WHERE p.user_id=? AND p.verification='Approved' AND u.active=1 AND c.active=1",
            [(int) input("provider_id")],
        );
        if (!$p) {
            throw new RuntimeException("This provider is not available.");
        }
        $date = required("date", 10);
        $time = required("time", 5);
        $duration = (int) input("duration");
        $dt = DateTimeImmutable::createFromFormat(
            "!Y-m-d H:i",
            $date . " " . $time,
        );
        if (
            !$dt ||
            $dt->format("Y-m-d H:i") !== $date . " " . $time ||
            $dt->getTimestamp() < time() ||
            $duration < 1 ||
            $duration > 12 ||
            (int) substr($time, 0, 2) + $duration > 24
        ) {
            throw new RuntimeException(
                "Choose a valid future date, time and duration (1–12 hours, within the same day).",
            );
        }
        $total = $p["rate"] * $duration + 100;
        q(
            "INSERT INTO bookings(requester_id,elder_id,provider_id,category_id,service_date,start_time,duration,address,notes,rate,total) VALUES(?,?,?,?,?,?,?,?,?,?,?)",
            [
                $uid,
                $elder,
                $p["user_id"],
                $p["category_id"],
                $date,
                $time,
                $duration,
                required("address", 255),
                input("notes"),
                $p["rate"],
                $total,
            ],
        );
        $id = (int) db()->lastInsertId();
        q("INSERT INTO status_history(booking_id,status) VALUES(?,'Pending')", [
            $id,
        ]);
        notify(
            (int) $p["user_id"],
            "New booking request CC-" .
                str_pad((string) $id, 5, "0", STR_PAD_LEFT),
        );
        go("confirmation", ["id" => $id]);
    }
    if ($a === "status") {
        $b = booking((int) input("id"));
        $next = required("status", 30);
        $transitions = [
            "Pending" => ["Confirmed", "Rejected"],
            "Confirmed" => ["On the Way"],
            "On the Way" => ["In Progress"],
            "In Progress" => ["Completed"],
        ];
        $provider =
            $u["role"] === "provider" && (int) $b["provider_id"] === $uid;
        if ($provider) {
            $p = one(
                "SELECT verification FROM provider_profiles WHERE user_id=?",
                [$uid],
            );
            if ($p["verification"] !== "Approved") {
                throw new RuntimeException("Provider approval is required.");
            }
            if (!in_array($next, $transitions[$b["status"]] ?? [], true)) {
                throw new RuntimeException("Invalid service status change.");
            }
        } elseif (
            !(
                $next === "Cancelled" &&
                in_array($u["role"], ["customer"]) &&
                in_array($b["status"], ["Pending", "Confirmed"])
            )
        ) {
            throw new RuntimeException("This status change is not allowed.");
        }
        db()->beginTransaction();
        try {
            if ($next === "Confirmed") {
                $profile = one(
                    "SELECT user_id FROM provider_profiles WHERE user_id=? FOR UPDATE",
                    [$uid],
                );
                $overlap = one(
                    "SELECT id FROM bookings WHERE provider_id=? AND service_date=? AND id<>? AND status IN ('Confirmed','On the Way','In Progress') AND start_time<ADDTIME(?,SEC_TO_TIME(?*3600)) AND ADDTIME(start_time,SEC_TO_TIME(duration*3600))>?",
                    [
                        $uid,
                        $b["service_date"],
                        $b["id"],
                        $b["start_time"],
                        $b["duration"],
                        $b["start_time"],
                    ],
                );
                if ($overlap) {
                    throw new RuntimeException(
                        "You already have an accepted booking at this time.",
                    );
                }
            }
            $s = q(
                "UPDATE bookings SET status=?,completion_notes=? WHERE id=? AND status=?",
                [
                    $next,
                    $next === "Completed"
                        ? required("completion_notes")
                        : $b["completion_notes"] ?? "",
                    $b["id"],
                    $b["status"],
                ],
            );
            if (!$s->rowCount()) {
                throw new RuntimeException(
                    "Booking changed. Refresh and try again.",
                );
            }
            q("INSERT INTO status_history(booking_id,status) VALUES(?,?)", [
                $b["id"],
                $next,
            ]);
            notify(
                (int) $b["requester_id"],
                "Booking CC-" . $b["id"] . " is now " . $next,
            );
            db()->commit();
        } catch (Throwable $t) {
            db()->rollBack();
            throw $t;
        }
        flash("Status updated.");
        go("details", ["id" => $b["id"]]);
    }
    if ($a === "review") {
        must(["customer"]);
        $b = booking((int) input("id"));
        $rating = (int) input("rating");
        if ($b["status"] !== "Completed" || $rating < 1 || $rating > 5) {
            throw new RuntimeException("Rate a completed service from 1 to 5.");
        }
        if (one("SELECT id FROM reviews WHERE booking_id=?", [$b["id"]])) {
            throw new RuntimeException("This service has already been rated.");
        }
        q(
            "INSERT INTO reviews(booking_id,user_id,rating,review) VALUES(?,?,?,?)",
            [$b["id"], $uid, $rating, input("review")],
        );
        flash("Thank you for your review.");
        go("details", ["id" => $b["id"]]);
    }
    if ($a === "complaint") {
        must(["customer"]);
        $b = booking((int) input("id"));
        q("INSERT INTO complaints(booking_id,user_id,message) VALUES(?,?,?)", [
            $b["id"],
            $uid,
            required("message"),
        ]);
        flash("Complaint submitted to the administrator.");
        go("details", ["id" => $b["id"]]);
    }
    if ($a === "sos") {
        must(["customer"]);
        if (input("confirm") !== "yes") {
            throw new RuntimeException("Confirm before sending an alert.");
        }
        $lat = input("latitude");
        $lng = input("longitude");
        if (
            $lat !== "" &&
            (!is_numeric($lat) ||
                !is_numeric($lng) ||
                abs((float) $lat) > 90 ||
                abs((float) $lng) > 180)
        ) {
            throw new RuntimeException("Invalid location.");
        }
        q("INSERT INTO sos_alerts(elder_id,latitude,longitude) VALUES(?,?,?)", [
            $uid,
            $lat !== "" ? $lat : null,
            $lat !== "" ? $lng : null,
        ]);
        foreach (
            rows(
                "SELECT family_id FROM family_links WHERE elder_id=? AND status='Approved'",
                [$uid],
            )
            as $f
        ) {
            notify(
                (int) $f["family_id"],
                "SOS alert from " . $u["name"] . ". Please contact them now.",
            );
        }
        flash(
            "SOS recorded. Linked family members have an in-app alert. Call your emergency contacts directly below.",
        );
        go("emergency");
    }
    if ($a === "read_notifications") {
        q("UPDATE notifications SET is_read=1 WHERE user_id=?", [$uid]);
        go("notifications");
    }
    if ($a === "availability") {
        must(["provider"]);
        $rate = (float) required("rate");
        if ($rate < 1 || $rate > 100000) {
            throw new RuntimeException("Enter a valid hourly rate.");
        }
        q(
            "UPDATE provider_profiles SET availability=?,rate=?,bio=? WHERE user_id=?",
            [required("availability", 255), $rate, input("bio"), $uid],
        );
        flash("Availability and profile saved.");
        go("schedule");
    }
    if ($a === "upload_document") {
        must(["provider"]);
        $file = $_FILES["document"] ?? [];
        if (
            ($file["error"] ?? 1) !== UPLOAD_ERR_OK ||
            ($file["size"] ?? 0) > 5 * 1024 * 1024
        ) {
            throw new RuntimeException("Upload a PDF, JPG or PNG up to 5 MB.");
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file["tmp_name"]);
        if (
            !in_array(
                $mime,
                ["application/pdf", "image/jpeg", "image/png"],
                true,
            )
        ) {
            throw new RuntimeException("Unsupported document format.");
        }
        q(
            "UPDATE provider_profiles SET document_name=?,document_data=?,document_mime=?,verification='Pending' WHERE user_id=?",
            [
                basename($file["name"]),
                file_get_contents($file["tmp_name"]),
                $mime,
                $uid,
            ],
        );
        flash("Document submitted for administrator review.");
        go("verification");
    }
    must(["admin"]);
    if ($a === "verify") {
        $id = (int) input("id");
        $state = required("status");
        if (
            !in_array(
                $state,
                ["Under Review", "Approved", "Rejected", "Suspended"],
                true,
            )
        ) {
            throw new RuntimeException("Invalid verification state.");
        }
        $p = one("SELECT * FROM provider_profiles WHERE user_id=?", [$id]);
        if (!$p) {
            throw new RuntimeException("Provider not found.");
        }
        if ($state === "Approved" && !$p["document_data"]) {
            throw new RuntimeException(
                "Review a submitted qualification/identity document before approval.",
            );
        }
        q(
            "UPDATE provider_profiles SET verification=?,rejection_reason=? WHERE user_id=?",
            [$state, input("reason", 255), $id],
        );
        audit("Provider " . $id . " verification: " . $state);
        notify(
            $id,
            "Your provider application is " .
                $state .
                ". " .
                input("reason", 255),
        );
        go("admin-verification");
    }
    if ($a === "user_active") {
        $id = (int) input("id");
        if ($id === $uid) {
            throw new RuntimeException("You cannot suspend your own account.");
        }
        q("UPDATE users SET active=1-active WHERE id=? AND role<>'admin'", [
            $id,
        ]);
        audit("Changed account activity for user " . $id);
        go("admin-users");
    }
    if ($a === "payment") {
        $b = booking((int) input("id"));
        if ($b["status"] !== "Completed") {
            throw new RuntimeException("Record payment after completion.");
        }
        q("UPDATE bookings SET payment_status='Paid' WHERE id=?", [$b["id"]]);
        audit("Recorded offline payment for booking " . $b["id"]);
        flash("Offline payment recorded. No money was charged.");
        go("details", ["id" => $b["id"]]);
    }
    if ($a === "resolve") {
        q("UPDATE complaints SET status='Resolved' WHERE id=?", [
            (int) input("id"),
        ]);
        audit("Resolved complaint " . input("id"));
        go("admin-complaints");
    }
    if ($a === "category") {
        q(
            "INSERT INTO categories(name,description,icon,rate) VALUES(?,?,?,?)",
            [
                required("name", 100),
                required("description", 255),
                "imgIconHeart1",
                max(1, (float) input("rate")),
            ],
        );
        audit("Created category " . input("name"));
        go("admin-categories");
    }
    throw new RuntimeException("Unknown action.");
}
