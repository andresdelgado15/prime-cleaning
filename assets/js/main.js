/* =========================================================
   Prime Cleaning Team – site scripts
   ========================================================= */

// ---- Settings: change these if the number or email change ----
const WHATSAPP_NUMBER = "61452226386"; // used by the WhatsApp buttons (international format, no "+" or spaces)
const EMAIL_ENDPOINT = "send.php";     // PHP script: emails each enquiry and sends it by SMS (works on HostPapa)

document.documentElement.classList.remove("no-js");

/* ---------- Sticky header shadow ---------- */
const header = document.getElementById("header");
const onScroll = () => header.classList.toggle("is-scrolled", window.scrollY > 10);
window.addEventListener("scroll", onScroll, { passive: true });
onScroll();

/* ---------- Mobile menu ---------- */
const burger = document.getElementById("burger");
const nav = document.getElementById("nav");
const setMenu = (open) => {
  nav.classList.toggle("is-open", open);
  burger.setAttribute("aria-expanded", String(open));
  burger.setAttribute("aria-label", open ? "Close menu" : "Open menu");
};
burger.addEventListener("click", () => setMenu(!nav.classList.contains("is-open")));
nav.querySelectorAll("a").forEach((a) => a.addEventListener("click", () => setMenu(false)));
document.addEventListener("keydown", (e) => { if (e.key === "Escape") setMenu(false); });

/* ---------- Reveal on scroll ---------- */
const revealEls = document.querySelectorAll(".reveal");
if ("IntersectionObserver" in window) {
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("is-visible");
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });
  revealEls.forEach((el) => io.observe(el));
} else {
  revealEls.forEach((el) => el.classList.add("is-visible"));
}

/* ---------- Count-up numbers ---------- */
const counters = document.querySelectorAll("[data-count]");
const countUp = (el) => {
  const target = Number(el.dataset.count);
  const start = target > 1000 ? target - 30 : 0;
  const t0 = performance.now();
  const step = (now) => {
    const p = Math.min((now - t0) / 1400, 1);
    el.textContent = Math.round(start + (target - start) * (1 - Math.pow(1 - p, 3)));
    if (p < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
};
if ("IntersectionObserver" in window && !matchMedia("(prefers-reduced-motion: reduce)").matches) {
  const co = new IntersectionObserver((entries) => {
    entries.forEach((e) => { if (e.isIntersecting) { countUp(e.target); co.unobserve(e.target); } });
  }, { threshold: 0.6 });
  counters.forEach((c) => co.observe(c));
}

/* ---------- Tabs ---------- */
document.querySelectorAll("[data-tabs]").forEach((wrap) => {
  const tabs = [...wrap.querySelectorAll('[role="tab"]')];
  const select = (tab) => {
    tabs.forEach((t) => {
      const on = t === tab;
      t.setAttribute("aria-selected", String(on));
      t.tabIndex = on ? 0 : -1;
      document.getElementById(t.getAttribute("aria-controls")).hidden = !on;
    });
  };
  tabs.forEach((tab, i) => {
    tab.addEventListener("click", () => select(tab));
    tab.addEventListener("keydown", (e) => {
      if (e.key !== "ArrowRight" && e.key !== "ArrowLeft") return;
      const next = tabs[(i + (e.key === "ArrowRight" ? 1 : tabs.length - 1)) % tabs.length];
      next.focus();
      select(next);
    });
  });
});

/* ---------- Lead forms → email + SMS (via send.php) ---------- */
const validate = (form) => {
  let firstInvalid = null;
  form.querySelectorAll("input, textarea").forEach((field) => {
    const wrap = field.closest(".field");
    if (!wrap) return;
    let ok = true;
    if (field.required && !field.value.trim()) ok = false;
    if (field.type === "email" && field.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value.trim())) ok = false;
    if (field.type === "tel" && field.value.trim() && field.value.replace(/\D/g, "").length < 8) ok = false;
    wrap.classList.toggle("has-error", !ok);
    if (!ok && !firstInvalid) firstInvalid = field;
  });
  const consent = form.querySelector('input[name="consent"]');
  if (consent) {
    consent.closest(".consent").classList.toggle("has-error", !consent.checked);
    if (!consent.checked && !firstInvalid) firstInvalid = consent;
  }
  if (firstInvalid) firstInvalid.focus();
  return !firstInvalid;
};

document.querySelectorAll("[data-lead-form]").forEach((form) => {
  const status = form.querySelector(".form-status");
  const button = form.querySelector('button[type="submit"]');

  form.addEventListener("input", (e) => e.target.closest(".field, .consent")?.classList.remove("has-error"));

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    status.className = "form-status";
    status.textContent = "";

    if (!validate(form)) {
      status.className = "form-status err";
      status.textContent = "Please complete the highlighted fields and accept the Privacy Policy.";
      return;
    }

    const data = new FormData(form);
    if (data.get("website")) return; // honeypot: bots fill hidden field

    // send.php emails the enquiry and sends it by text message (SMS) to our phones
    button.classList.add("is-loading");
    button.disabled = true;
    fetch(EMAIL_ENDPOINT, { method: "POST", body: data })
      .then((res) => res.json())
      .then((result) => {
        if (!result.ok) throw new Error("send failed");
        status.className = "form-status ok";
        status.textContent = "Thanks! We've received your enquiry and will reply shortly.";
        form.reset();
      })
      .catch(() => {
        status.className = "form-status err";
        status.textContent = "Sorry, your enquiry could not be sent. Please call us on 0452 226 386.";
      })
      .finally(() => {
        button.classList.remove("is-loading");
        button.disabled = false;
      });
  });
});

/* ---------- Footer year ---------- */
document.getElementById("year").textContent = new Date().getFullYear();
