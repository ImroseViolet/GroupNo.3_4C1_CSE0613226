<?php
function asset(string $name, string $prefix = "design"): string
{
    return "assets/figma/" . $prefix . "-" . $name . ".svg";
}
function icon(string $name, int $size = 20): string
{
    return '<span class="icon" style="width:' .
        $size .
        "px;height:" .
        $size .
        'px"><img src="' .
        e(asset($name)) .
        '" width="' .
        $size .
        '" height="' .
        $size .
        '" alt=""></span>';
}
function avatar(string $file, string $class = ""): string
{
    return '<img class="avatar ' .
        e($class) .
        '" src="assets/figma/' .
        e($file) .
        '" alt="">';
}
function btn(
    string $label,
    string $page,
    array $args = [],
    string $class = "",
): string {
    return '<a class="btn ' .
        e($class) .
        '" href="' .
        e(url($page, $args)) .
        '">' .
        e($label) .
        "</a>";
}
function field(
    string $label,
    string $name,
    string $type = "text",
    $value = "",
    bool $required = true,
): void {
    echo '<div class="field"><label for="' .
        e($name) .
        '">' .
        e($label) .
        '</label><input id="' .
        e($name) .
        '" name="' .
        e($name) .
        '" type="' .
        e($type) .
        '" value="' .
        e($value) .
        '" ' .
        ($required ? "required" : "") .
        " " .
        ($type === "password"
            ? 'minlength="8" autocomplete="new-password"'
            : "") .
        ' maxlength="' .
        ($name === "name" ? "120" : ($name === "phone" ? "40" : "255")) .
        '"></div>';
}
function action_form(
    string $action,
    array $hidden,
    string $label,
    string $class = "outline",
    ?string $confirm = null,
): void {
    echo '<form method="post" ' .
        ($confirm ? 'data-confirm="' . e($confirm) . '"' : "") .
        ">" .
        csrf() .
        '<input type="hidden" name="action" value="' .
        e($action) .
        '">';
    foreach ($hidden as $k => $v) {
        echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    echo '<button class="btn ' .
        e($class) .
        '">' .
        e($label) .
        "</button></form>";
}
function provider_card(array $p): void
{
    echo '<article class="card provider-card"><div class="row">' .
        avatar($p["avatar"]) .
        "<div><strong>" .
        e($p["name"]) .
        '</strong><small style="display:block">' .
        e($p["qualification"]) .
        '</small></div></div><span class="badge">' .
        icon("imgIconShieldCheck", 14) .
        " Verified</span><p>" .
        icon("imgIconBriefcase", 14) .
        " " .
        e($p["category"]) .
        '</p><p><span class="stars">★</span> ' .
        e($p["avg_rating"] ?: "New") .
        '</p><p class="muted">' .
        icon("imgIconClock1", 14) .
        " " .
        e($p["experience"]) .
        ' yrs experience</p><p class="muted">' .
        icon("imgIconMapPin1", 14) .
        " " .
        e($p["address"]) .
        '</p><p><span class="rate">' .
        money($p["rate"]) .
        '</span><small> / hour</small></p><span class="badge">● ' .
        e($p["availability"] ?: "Contact for availability") .
        "</span>" .
        btn("View Profile", "provider", ["id" => $p["user_id"]], "outline") .
        "</article>";
}
function category_cards(array $categories, bool $compact = true): void
{
    echo '<div class="' . ($compact ? "categories" : "services-grid") . '">';
    foreach ($categories as $c) {
        if ($compact) {
            echo '<a class="category" href="' .
                e(url("providers", ["category" => $c["id"]])) .
                '"><span class="tile">' .
                icon($c["icon"], 24) .
                "</span><strong>" .
                e($c["name"]) .
                "</strong><small>From " .
                money($c["rate"]) .
                " / hour</small></a>";
        } else {
            echo '<article class="card service-card"><div class="service-head"><span class="tile">' .
                icon($c["icon"], 26) .
                '</span></div><div class="service-body"><h2>' .
                e($c["name"]) .
                "</h2><p>" .
                e($c["description"]) .
                '</p><div class="section-heading"><strong>' .
                money($c["rate"]) .
                '<small> / hour</small></strong><a href="' .
                e(url("providers", ["category" => $c["id"]])) .
                '">Find caregivers →</a></div></div></article>';
        }
    }
    echo "</div>";
}
function booking_table(array $items): void
{
    if (!$items) {
        echo '<div class="card empty"><h2>No bookings yet</h2><p>Requests and service updates will appear here.</p>' .
            (in_array(user()["role"], ["customer"])
                ? btn("Browse Services", "services")
                : "") .
            "</div>";
        return;
    }
    echo '<div class="table-wrap" tabindex="0" role="region" aria-label="Bookings table"><table><thead><tr><th>Booking</th><th>Customer</th><th>Provider / Service</th><th>Date & time</th><th>Amount</th><th>Status</th><th>Details</th></tr></thead><tbody>';
    foreach ($items as $b) {
        echo "<tr><td>CC-" .
            str_pad((string) $b["id"], 5, "0", STR_PAD_LEFT) .
            "</td><td>" .
            e($b["elder_name"]) .
            "</td><td>" .
            e($b["provider_name"]) .
            "<small>" .
            e($b["category"]) .
            "</small></td><td>" .
            e($b["service_date"]) .
            "<small>" .
            e(substr($b["start_time"], 0, 5)) .
            " · " .
            e($b["duration"]) .
            " hours</small></td><td>" .
            money($b["total"]) .
            "</td><td>" .
            badge($b["status"]) .
            '</td><td><a class="btn outline" href="' .
            e(url("details", ["id" => $b["id"]])) .
            '">View</a></td></tr>';
    }
    echo "</tbody></table></div>";
}
function heading(string $title, string $subtitle = ""): void
{
    echo "<div><h1>" .
        e($title) .
        "</h1>" .
        ($subtitle ? '<p class="subtitle">' . e($subtitle) . "</p>" : "") .
        "</div>";
}
