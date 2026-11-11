const menu = document.querySelector("#sidebar"),
  toggle = document.querySelector("#menu-toggle"),
  scrim = document.querySelector("#scrim");
function setMenu(open) {
  menu?.classList.toggle("open", open);
  scrim?.classList.toggle("visible", open);
  toggle?.setAttribute("aria-expanded", String(open));
  if (open) menu.querySelector(".menu-close").focus();
  else toggle?.focus();
}
toggle?.addEventListener("click", () =>
  setMenu(!menu.classList.contains("open")),
);
scrim?.addEventListener("click", () => setMenu(false));
document
  .querySelector(".menu-close")
  ?.addEventListener("click", () => setMenu(false));
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && menu?.classList.contains("open"))
    setMenu(false);
  if (event.key === "Tab" && menu?.classList.contains("open")) {
    const f = [...menu.querySelectorAll("a,button,input")].filter(
      (x) => x.offsetParent !== null && x.type !== "hidden",
    );
    if (event.shiftKey && document.activeElement === f[0]) {
      event.preventDefault();
      f.at(-1).focus();
    } else if (!event.shiftKey && document.activeElement === f.at(-1)) {
      event.preventDefault();
      f[0].focus();
    }
  }
});
window.matchMedia("(min-width: 992px)").addEventListener("change", (e) => {
  if (e.matches) {
    menu?.classList.remove("open");
    scrim?.classList.remove("visible");
    toggle?.setAttribute("aria-expanded", "false");
  }
});
document.querySelectorAll("[data-confirm]").forEach((form) =>
  form.addEventListener("submit", (e) => {
    if (!confirm(form.dataset.confirm)) e.preventDefault();
  }),
);
document.querySelector("#share-location")?.addEventListener("click", () => {
  const result = document.querySelector("#location-result");
  if (!navigator.geolocation) {
    result.textContent =
      "Location is unavailable. You can send an alert without it.";
    return;
  }
  result.textContent = "Requesting location permission…";
  navigator.geolocation.getCurrentPosition(
    (p) => {
      document.querySelector("[name=latitude]").value = p.coords.latitude;
      document.querySelector("[name=longitude]").value = p.coords.longitude;
      result.textContent = "Location attached to this alert.";
    },
    () => {
      result.textContent = "Location not shared. You can still send an alert.";
    },
    { timeout: 10000, maximumAge: 60000 },
  );
});
const duration = document.querySelector("#duration"),
  total = document.querySelector("#booking-total");
duration?.addEventListener("input", () => {
  if (total)
    total.textContent =
      "BDT " +
      (
        Number(duration.value) * Number(total.dataset.rate) +
        100
      ).toLocaleString();
});
