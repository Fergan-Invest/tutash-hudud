document.addEventListener("DOMContentLoaded", () => {
  initMobileNavigation();
  initSteps();
  initDependentSelects();
  initSearchableSelects();
  initStreetCreator();
  initOwnerType();
  initMasks();
  initCadastreCheck();
  initAreaCalculator();
  initImageSlots();
  initDraftPersistence();
  initFormValidation();
  initSessionKeepAlive();
  initPolygonMap();
  initShowMap();
  initMonitoringMap();
});

function safeStorage(storage) {
  try {
    const key = "__storage_test__";
    storage.setItem(key, key);
    storage.removeItem(key);
    return storage;
  } catch {
    return {
      getItem: () => null,
      setItem: () => {},
      removeItem: () => {},
    };
  }
}

const appSessionStorage = safeStorage(window.sessionStorage);
const appLocalStorage = safeStorage(window.localStorage);
let appSessionExpired = false;
let sessionExpiredBanner = null;

function cssEscape(value) {
  if (window.CSS && typeof window.CSS.escape === "function") {
    return window.CSS.escape(value);
  }

  return String(value).replace(/["\\]/g, "\\$&");
}

function dispatchRequestStepChanged(step) {
  if (typeof window.CustomEvent === "function") {
    window.dispatchEvent(new CustomEvent("requestFormStepChanged", { detail: { step } }));
    return;
  }

  const event = document.createEvent("CustomEvent");
  event.initCustomEvent("requestFormStepChanged", false, false, { step });
  window.dispatchEvent(event);
}

function initMobileNavigation() {
  const button = document.querySelector(".menu-button");
  const sidebar = document.getElementById("app-sidebar");
  const backdrop = document.querySelector("[data-sidebar-close]");
  if (!button || !sidebar || !backdrop) return;

  const setOpen = (open) => {
    document.body.classList.toggle("sidebar-open", open);
    button.setAttribute("aria-expanded", open ? "true" : "false");
    backdrop.hidden = !open;
  };

  button.addEventListener("click", () => setOpen(!document.body.classList.contains("sidebar-open")));
  backdrop.addEventListener("click", () => setOpen(false));
  sidebar.querySelectorAll("a").forEach((link) => link.addEventListener("click", () => setOpen(false)));
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") setOpen(false);
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth > 900) setOpen(false);
  });
}

function initSessionKeepAlive() {
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  if (!csrfMeta) return;

  const endpoint = "/session/keep-alive";
  const interval = 10 * 60 * 1000;
  let lastPing = 0;
  let timer = null;

  const updateCsrfToken = (token) => {
    if (!token) return;
    csrfMeta.setAttribute("content", token);
    document.querySelectorAll('input[name="_token"]').forEach((input) => {
      input.value = token;
    });
  };

  const ping = async (force = false) => {
    const now = Date.now();
    if (!force && now - lastPing < interval) return !appSessionExpired;
    lastPing = now;

    try {
      const response = await fetch(endpoint, {
        method: "GET",
        credentials: "same-origin",
        headers: {
          "Accept": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        cache: "no-store",
      });

      if (!response.ok || response.redirected) {
        appSessionExpired = true;
        showSessionExpiredBanner();
        return false;
      }

      const data = await response.json();
      updateCsrfToken(data.csrf_token);
      appSessionExpired = false;
      hideSessionExpiredBanner();
      return true;
    } catch {
      // Network hiccups in mobile WebViews should not interrupt form filling.
      return !appSessionExpired;
    }
  };

  timer = window.setInterval(() => {
    if (document.visibilityState !== "hidden") ping();
  }, interval);

  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") ping(true);
  });

  window.addEventListener("focus", () => ping(true));
  document.addEventListener("input", () => ping(), { passive: true });

  document.querySelectorAll("form[method='POST'], form[method='post']").forEach((form) => {
    form.addEventListener("submit", async (event) => {
      if (event.defaultPrevented) return;
      if (form.dataset.sessionPreflightPassed === "1") {
        return;
      }

      event.preventDefault();

      const invalid = findFirstInvalidControl(form);
      if (invalid) {
        showFirstInvalidControl(form, invalid);
        return;
      }

      saveCurrentFormDraft(form);

      const alive = await ping(true);
      if (!alive || appSessionExpired) {
        setFormSubmitting(form, false);
        showSessionExpiredBanner();
        showToast("Sessiya muddati tugagan. Sahifani yangilang, kiritilgan ma'lumotlar draftda saqlangan.", "error");
        return;
      }

      setFormSubmitting(form, true, "Tekshirilmoqda...");
      const valid = await validateFormBeforeSubmit(form);
      if (!valid) {
        setFormSubmitting(form, false);
        return;
      }

      setFormSubmitting(form, true, "Saqlanmoqda...");
      form.dataset.sessionPreflightPassed = "1";
      window.setTimeout(() => form.submit(), 80);
    });
  });

  ping(true);
  window.addEventListener("beforeunload", () => {
    if (timer) window.clearInterval(timer);
  });
}

function saveCurrentFormDraft(form) {
  if (!form) return;
  saveDraft(form, `request-form-draft:${location.pathname}`);
}

function clearStepStorageForCurrentPage() {
  const prefix = `requestFormStep:${location.pathname}`;
  try {
    for (let index = window.sessionStorage.length - 1; index >= 0; index -= 1) {
      const key = window.sessionStorage.key(index);
      if (key && key.startsWith(prefix)) {
        appSessionStorage.removeItem(key);
      }
    }
  } catch {
    // Some mobile WebViews can block sessionStorage access.
  }
  appSessionStorage.removeItem("requestFormStep");
}

async function validateFormBeforeSubmit(form) {
  const url = form.dataset.validateUrl;
  if (!url) return true;

  try {
    const payload = new FormData(form);
    payload.delete("_method");

    const response = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: payload,
    });

    if (response.ok) {
      clearAjaxValidationSummary();
      return true;
    }

    if (response.status === 419) {
      appSessionExpired = true;
      showSessionExpiredBanner();
      showToast("Sessiya muddati tugagan. Sahifani yangilang, kiritilgan ma'lumotlar draftda saqlangan.", "error");
      return false;
    }

    if (response.status === 422) {
      const data = await response.json();
      showAjaxValidationErrors(form, data.errors || {});
      return false;
    }

    showToast("Ma'lumotlarni tekshirishda xatolik yuz berdi. Qayta urinib ko'ring.", "error");
    return false;
  } catch {
    showToast("Internet yoki server bilan aloqa uzildi. Ma'lumotlar yuborilmadi.", "error");
    return false;
  }
}

function clearAjaxValidationSummary() {
  document.querySelector(".ajax-validation-summary")?.remove();
}

function showAjaxValidationErrors(form, errors) {
  clearAjaxValidationSummary();
  const messages = Object.values(errors).flat();
  const summary = document.createElement("div");
  summary.className = "alert danger validation-summary ajax-validation-summary";
  summary.innerHTML = `
    <strong>Ma'lumotlarda xatolik bor. Ushbu ma'lumotlarni to'g'irlang:</strong>
    <ul>${messages.map((message) => `<li>${escapeHtml(message)}</li>`).join("")}</ul>
  `;

  form.insertAdjacentElement("beforebegin", summary);
  const firstField = Object.keys(errors)[0];
  const control = firstField ? findControlByErrorKey(form, firstField) : null;
  if (control) {
    showFirstInvalidControl(form, control);
  } else {
    summary.scrollIntoView({ behavior: "smooth", block: "center" });
  }
}

function findControlByErrorKey(form, key) {
  const candidates = [
    key,
    key.replace(/\.\d+$/, "[]"),
    key.replace(/\.\d+\./g, "."),
  ];

  for (const name of candidates) {
    const control = form.querySelector(`[name="${cssEscape(name)}"]`);
    if (control) return control;
  }

  if (key.startsWith("images.")) {
    return form.querySelector('[name="images[]"]');
  }

  return null;
}

function escapeHtml(value) {
  const div = document.createElement("div");
  div.textContent = String(value);
  return div.innerHTML;
}

function showSessionExpiredBanner() {
  if (sessionExpiredBanner) {
    sessionExpiredBanner.hidden = false;
    return;
  }

  sessionExpiredBanner = document.createElement("div");
  sessionExpiredBanner.className = "session-expired-banner";
  sessionExpiredBanner.setAttribute("role", "alert");
  sessionExpiredBanner.innerHTML = `
    <div>
      <strong>Sessiya muddati tugadi</strong>
      <span>Maydonlar draftda saqlanadi. Sahifani yangilang va davom eting.</span>
    </div>
    <button class="session-reload-button" type="button">
      <span aria-hidden="true">↻</span>
      <span>Yangilash</span>
    </button>
  `;

  sessionExpiredBanner.querySelector("button")?.addEventListener("click", () => {
    document.querySelectorAll("form").forEach(saveCurrentFormDraft);
    window.location.reload();
  });

  document.body.appendChild(sessionExpiredBanner);
}

function hideSessionExpiredBanner() {
  if (sessionExpiredBanner) {
    sessionExpiredBanner.hidden = true;
  }
}

function initSteps() {
  document.querySelectorAll(".stepper").forEach((stepper, index) => {
    const root = stepper.closest(".registry-card") || document;
    const steps = [...root.querySelectorAll("[data-step-target]")];
    const panels = [...root.querySelectorAll("[data-step-panel]")];
    const next = root.querySelector("[data-step-next]");
    const prev = root.querySelector("[data-step-prev]");
    const submit = root.querySelector('button[type="submit"]');
    if (!steps.length || !panels.length) return;

    const storageKey = `requestFormStep:${location.pathname}:${index}`;
    let current = Math.max(1, Number(appSessionStorage.getItem(storageKey) || 1));
    const setStep = (step) => {
      current = Math.max(1, Math.min(step, panels.length));
      appSessionStorage.setItem(storageKey, String(current));
      steps.forEach((button) => {
        const value = Number(button.dataset.stepTarget);
        button.classList.toggle("active", value === current);
        button.classList.toggle("complete", value < current);
      });
      panels.forEach((panel) => panel.classList.toggle("active", Number(panel.dataset.stepPanel) === current));
      if (prev) prev.disabled = current === 1;
      if (next) next.hidden = current === panels.length;
      if (submit) submit.hidden = current !== panels.length;
      dispatchRequestStepChanged(current);
      window.setTimeout(() => window.dispatchEvent(new Event("resize")), 80);
    };

    if (index === 0) {
      window.requestFormSetStep = setStep;
    }

    steps.forEach((button) => button.addEventListener("click", () => setStep(Number(button.dataset.stepTarget))));
    next?.addEventListener("click", () => setStep(current + 1));
    prev?.addEventListener("click", () => setStep(current - 1));
    setStep(current);
  });
}

function initFormValidation() {
  const form = document.querySelector(".stepped-form");
  if (!form) return;

  form.addEventListener("submit", (event) => {
    const invalid = findFirstInvalidControl(form);
    if (!invalid) return;

    event.preventDefault();
    showFirstInvalidControl(form, invalid);
  });
}

function showFirstInvalidControl(form, invalid) {
  setFormSubmitting(form, false);

  const panel = invalid.closest("[data-step-panel]");
  if (panel) {
    window.requestFormSetStep?.(Number(panel.dataset.stepPanel));
  }

  window.setTimeout(() => {
    focusControl(invalid);
    showToast(fieldLabel(invalid) + " maydonini to'ldiring.", "error");
  }, 80);
}

function setFormSubmitting(form, submitting, label = "Saqlanmoqda...") {
  if (!form) return;

  form.classList.toggle("is-submitting", submitting);
  form.querySelectorAll('button[type="submit"]').forEach((button) => {
    button.disabled = submitting;
    if (submitting) {
      if (!button.dataset.originalText) {
        button.dataset.originalText = button.textContent.trim();
      }
      button.innerHTML = `<span class="button-loader" aria-hidden="true"></span><span>${escapeHtml(label)}</span>`;
      return;
    }

    if (button.dataset.originalText) {
      button.textContent = button.dataset.originalText;
    }
  });
}

function findFirstInvalidControl(form) {
  const controls = [...form.querySelectorAll("input, select, textarea")];

  return controls.find((control) => {
    if (control.disabled || control.type === "hidden") return false;
    if (control.required && !hasValue(control)) return true;
    if (control.validity && !control.validity.valid) return true;
    return false;
  });
}

function hasValue(control) {
  if (control.type === "checkbox" || control.type === "radio") {
    return [...document.querySelectorAll(`[name="${cssEscape(control.name)}"]`)].some((field) => field.checked);
  }

  if (control.type === "file") {
    return control.files?.length > 0;
  }

  return String(control.value || "").trim() !== "";
}

function focusControl(control) {
  const searchable = control.matches("select.searchable-select")
    ? control.nextElementSibling?.querySelector(".searchable-select-input")
    : null;
  const target = searchable || control;

  target.scrollIntoView({ behavior: "smooth", block: "center" });
  target.focus({ preventScroll: true });
}

function fieldLabel(control) {
  const label = control.closest("label");
  const text = label?.childNodes?.[0]?.textContent?.trim();
  return text || control.name || "Maydon";
}

function showToast(message, type = "info") {
  let wrap = document.querySelector(".toast-wrap");
  if (!wrap) {
    wrap = document.createElement("div");
    wrap.className = "toast-wrap";
    document.body.appendChild(wrap);
  }

  const toast = document.createElement("div");
  toast.className = `toast ${type}`;
  toast.textContent = message;
  wrap.appendChild(toast);

  window.setTimeout(() => toast.classList.add("show"), 20);
  window.setTimeout(() => {
    toast.classList.remove("show");
    toast.addEventListener("transitionend", () => toast.remove(), { once: true });
  }, 3800);
}

function initDependentSelects() {
  const district = document.getElementById("district_id");
  const mahalla = document.getElementById("mahalla_id");
  const street = document.getElementById("street_id");
  if (!district || !mahalla || !street) return;

  const sync = () => {
    const districtId = district.value;
    const mahallaId = mahalla.value;
    [...mahalla.options].forEach((option) => {
      if (!option.value) return;
      option.hidden = option.dataset.district !== districtId;
      if (option.hidden && option.selected) mahalla.value = "";
    });
    [...street.options].forEach((option) => {
      if (!option.value) return;
      option.hidden = option.dataset.district !== districtId || option.dataset.mahalla !== mahallaId;
      if (option.hidden && option.selected) street.value = "";
    });
    window.refreshSearchableSelects?.();
  };

  district.addEventListener("change", sync);
  mahalla.addEventListener("change", sync);
  sync();
}

function initSearchableSelects() {
  const selects = [...document.querySelectorAll("select.searchable-select")];
  if (!selects.length) return;

  selects.forEach((select) => {
    if (select.dataset.searchableReady === "1") return;
    select.dataset.searchableReady = "1";
    select.classList.add("native-select-hidden");

    const shell = document.createElement("div");
    shell.className = "searchable-select-shell";
    const input = document.createElement("input");
    input.type = "text";
    input.className = "searchable-select-input";
    input.autocomplete = "off";
    input.placeholder = select.options[0]?.textContent?.trim() || "Tanlang";
    const list = document.createElement("div");
    list.className = "searchable-select-list";
    list.hidden = true;

    shell.append(input, list);
    select.insertAdjacentElement("afterend", shell);

    const visibleOptions = () => [...select.options].filter((option) => !option.hidden);
    const selectedLabel = () => select.selectedOptions[0]?.textContent?.trim() || "";

    const close = () => { list.hidden = true; };
    const open = () => {
      render(input.value);
      list.hidden = false;
    };

    const render = (term = "") => {
      const normalized = term.trim().toLowerCase();
      list.innerHTML = "";
      const matches = visibleOptions().filter((option) => option.textContent.toLowerCase().includes(normalized));

      if (!matches.length) {
        const empty = document.createElement("div");
        empty.className = "searchable-select-empty";
        empty.textContent = "Ma'lumot topilmadi";
        list.append(empty);
        return;
      }

      matches.forEach((option) => {
        const item = document.createElement("button");
        item.type = "button";
        item.className = "searchable-select-option";
        item.textContent = option.textContent;
        item.dataset.value = option.value;
        item.setAttribute("aria-selected", option.selected ? "true" : "false");
        item.addEventListener("mousedown", (event) => {
          event.preventDefault();
          select.value = option.value;
          input.value = option.value ? option.textContent.trim() : "";
          select.dispatchEvent(new Event("change", { bubbles: true }));
          close();
        });
        list.append(item);
      });
    };

    const syncInput = () => {
      input.value = select.value ? selectedLabel() : "";
    };
    select.searchableSync = syncInput;

    input.addEventListener("focus", open);
    input.addEventListener("click", open);
    input.addEventListener("input", () => open());
    input.addEventListener("keydown", (event) => {
      if (event.key === "Escape") close();
      if (event.key === "Enter") {
        const first = list.querySelector(".searchable-select-option");
        if (first) {
          event.preventDefault();
          first.dispatchEvent(new MouseEvent("mousedown", { bubbles: true }));
        }
      }
    });
    select.addEventListener("change", syncInput);

    syncInput();
  });

  document.addEventListener("click", (event) => {
    document.querySelectorAll(".searchable-select-shell").forEach((shell) => {
      if (!shell.contains(event.target)) {
        const list = shell.querySelector(".searchable-select-list");
        if (list) list.hidden = true;
      }
    });
  });

  window.refreshSearchableSelects = () => {
    selects.forEach((select) => select.searchableSync?.());
  };
}

function initStreetCreator() {
  const button = document.getElementById("add-street");
  const wrap = document.getElementById("new-street-wrap");
  const input = document.getElementById("new_street_name");
  const district = document.getElementById("district_id");
  const mahalla = document.getElementById("mahalla_id");
  const type = document.getElementById("street_type");
  const street = document.getElementById("street_id");
  if (!button || !wrap || !input) return;

  button.addEventListener("click", async () => {
    wrap.classList.remove("hidden");
    if (!input.value.trim()) {
      input.focus();
      return;
    }

    const response = await fetch("/streets/store", {
      method: "POST",
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        district_id: district.value,
        mahalla_id: mahalla.value,
        type: type.value,
        name: input.value.trim(),
      }),
    });

    if (!response.ok) {
      alert("Ko‘cha qo‘shishda xatolik bor. Tuman, mahalla va nomni tekshiring.");
      return;
    }

    const created = await response.json();
    const option = new Option(created.name, created.id, true, true);
    option.dataset.district = created.district_id;
    option.dataset.mahalla = created.mahalla_id;
    street.add(option);
    street.dispatchEvent(new Event("change", { bubbles: true }));
    input.value = "";
    wrap.classList.add("hidden");
  });
}

function initOwnerType() {
  const radios = [...document.querySelectorAll('input[name="owner_type"]')];
  const identifierLabel = document.getElementById("owner-identifier-label");
  const identifier = document.getElementById("owner_stir_pinfl");
  const ownerNameLabel = document.getElementById("owner-name-label");
  if (!radios.length || !identifierLabel || !identifier || !ownerNameLabel) return;

  const sync = () => {
    const type = radios.find((radio) => radio.checked)?.value || "yuridik";
    const isPhysical = type === "jismoniy";
    identifierLabel.textContent = isPhysical ? "PINFL" : "STIR";
    identifier.placeholder = isPhysical ? "14 xonali PINFL" : "9 xonali STIR";
    identifier.maxLength = isPhysical ? 14 : 9;
    ownerNameLabel.childNodes[0].textContent = isPhysical ? "F.I.SH " : "Korxona nomi ";
  };

  radios.forEach((radio) => radio.addEventListener("change", sync));
  sync();
}

function initMasks() {
  const phone = document.getElementById("phone_number");
  const ownerId = document.getElementById("owner_stir_pinfl");
  const cadastre = document.getElementById("building_cadastr_number");
  const hokimiyatCadastre = document.getElementById("hokimyatga_biriktirilgan_kadastr_raqami");

  phone?.addEventListener("input", () => {
    const digits = phone.value.replace(/\D/g, "").replace(/^998/, "").slice(0, 9);
    let value = "+998";
    if (digits.length > 0) value += ` (${digits.slice(0, 2)}`;
    if (digits.length >= 2) value += ")";
    if (digits.length > 2) value += ` ${digits.slice(2, 5)}`;
    if (digits.length > 5) value += `-${digits.slice(5, 7)}`;
    if (digits.length > 7) value += `-${digits.slice(7, 9)}`;
    phone.value = value;
  });

  ownerId?.addEventListener("input", () => {
    const type = document.querySelector('input[name="owner_type"]:checked')?.value || "yuridik";
    ownerId.value = ownerId.value.replace(/\D/g, "").slice(0, type === "jismoniy" ? 14 : 9);
  });

  cadastre?.addEventListener("input", () => {
    cadastre.value = formatCadastre(cadastre.value);
  });

  hokimiyatCadastre?.addEventListener("input", () => {
    hokimiyatCadastre.value = formatCadastre(hokimiyatCadastre.value);
  });
}

function formatCadastre(value) {
  const raw = String(value || "");
  let digits = "";
  let separator = "";
  let suffix = "";

  for (const char of raw) {
    if (digits.length < 14) {
      if (/\d/.test(char)) digits += char;
      continue;
    }

    if (!separator) {
      if (char === "/" || char === ":") separator = char;
      continue;
    }

    suffix += char.replace(/[^\d:]/g, "");
  }

  const mainGroups = [2, 2, 2, 2, 2, 4];
  const mainParts = [];
  let offset = 0;

  mainGroups.forEach((size) => {
    const part = digits.slice(offset, offset + size);
    if (part) mainParts.push(part);
    offset += size;
  });

  const main = mainParts.join(":");

  if (suffix) return `${main}${separator}${suffix}`;
  if (separator) return `${main}${separator}`;

  return main;
}

function initCadastreCheck() {
  const input = document.getElementById("building_cadastr_number");
  if (!input) return;
  let timer;

  input.addEventListener("input", () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => window.checkCadastreRestriction(input.value), 450);
  });
  input.addEventListener("blur", () => window.checkCadastreRestriction(input.value));
}

function initAreaCalculator() {
  const length = document.getElementById("area_length");
  const width = document.getElementById("area_width");
  const calculated = document.getElementById("calculated_land_area");
  const total = document.getElementById("total_area");
  const manual = document.getElementById("total_area_manual");
  if (!length || !width || !total) return;

  const calculate = () => {
    const result = Number(length.value || 0) * Number(width.value || 0);
    const value = result > 0 ? result.toFixed(2) : "";
    if (calculated) calculated.value = value;
    if (!manual?.checked) total.value = value;
  };

  const syncMode = () => {
    total.readOnly = !manual?.checked;
    if (!manual?.checked) calculate();
  };

  length.addEventListener("input", calculate);
  width.addEventListener("input", calculate);
  manual?.addEventListener("change", syncMode);
  syncMode();
}

function initImageSlots() {
  document.querySelectorAll("[data-image-slot]").forEach((slot) => {
    const input = slot.querySelector('input[type="file"]');
    const preview = slot.querySelector(".image-preview");
    const clear = slot.querySelector(".image-clear");
    if (!input || !preview || !clear) return;

    input.addEventListener("change", () => {
      const file = input.files?.[0];
      if (!file) return;
      preview.src = URL.createObjectURL(file);
      preview.classList.remove("hidden");
      clear.classList.remove("hidden");
    });

    clear.addEventListener("click", (event) => {
      event.preventDefault();
      input.value = "";
      preview.removeAttribute("src");
      preview.classList.add("hidden");
      clear.classList.add("hidden");
      appLocalStorage.removeItem(`request-form-draft:${location.pathname}:images`);
    });
  });

  document.querySelectorAll(".delete-existing-image").forEach((button) => {
    button.addEventListener("click", async () => {
      if (!confirm("Rasm o‘chirilsinmi?")) return;
      const response = await fetch(button.dataset.deleteUrl, {
        method: "DELETE",
        headers: {
          "Accept": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        },
      });
      if (!response.ok) {
        alert("Rasmni o‘chirishda xatolik yuz berdi.");
        return;
      }
      button.closest(".existing-image")?.remove();
    });
  });
}

function initDraftPersistence() {
  const form = document.querySelector(".stepped-form");
  if (!form) return;

  const draftKey = `request-form-draft:${location.pathname}`;
  const imageKey = `${draftKey}:images`;
  const draft = readJson(draftKey, {});

  restoreDraft(form, draft);
  appLocalStorage.removeItem(imageKey);

  form.addEventListener("input", () => saveDraft(form, draftKey));
  form.addEventListener("change", () => {
    saveDraft(form, draftKey);
  });

  form.addEventListener("submit", () => {
    clearStepStorageForCurrentPage();
    appLocalStorage.removeItem(draftKey);
    appLocalStorage.removeItem(imageKey);
  });
}

function saveDraft(form, key) {
  const data = {};
  form.querySelectorAll("input, select, textarea").forEach((field) => {
    if (!field.name || field.type === "file" || field.name === "images[]") return;
    if (field.type === "checkbox") {
      data[field.name] = field.checked;
      return;
    }
    if (field.type === "radio") {
      if (field.checked) data[field.name] = field.value;
      return;
    }
    data[field.name] = field.value;
  });
  try {
    appLocalStorage.setItem(key, JSON.stringify(data));
  } catch (error) {
    console.warn("Draft saqlanmadi:", error);
  }
}

function restoreDraft(form, data) {
  Object.entries(data || {}).forEach(([name, value]) => {
    const fields = [...form.querySelectorAll(`[name="${cssEscape(name)}"]`)];
    fields.forEach((field) => {
      if (field.type === "checkbox") field.checked = Boolean(value);
      else if (field.type === "radio") field.checked = field.value === value;
      else field.value = value;
    });
  });
  document.getElementById("district_id")?.dispatchEvent(new Event("change"));
  document.getElementById("mahalla_id")?.dispatchEvent(new Event("change"));
  document.querySelector('input[name="owner_type"]:checked')?.dispatchEvent(new Event("change"));
  document.getElementById("total_area_manual")?.dispatchEvent(new Event("change"));
  document.getElementById("area_length")?.dispatchEvent(new Event("input"));
}

function readJson(key, fallback) {
  try {
    return JSON.parse(appLocalStorage.getItem(key)) || fallback;
  } catch {
    return fallback;
  }
}

function initPolygonMap() {
  const container = document.getElementById("polygon-map");
  if (!container) return;
  if (container.dataset.mapReady === "1") return;
  if (container.offsetParent === null) {
    const initWhenVisible = () => {
      if (container.offsetParent === null) return;
      window.removeEventListener("requestFormStepChanged", initWhenVisible);
      initPolygonMap();
    };
    window.addEventListener("requestFormStepChanged", initWhenVisible);
    return;
  }
  if (typeof L === "undefined") {
    loadLeaflet().then(initPolygonMap).catch(() => {
      container.textContent = "Xaritani yuklab bo'lmadi.";
    });
    return;
  }
  container.dataset.mapReady = "1";

  const latInput = document.getElementById("latitude");
  const lngInput = document.getElementById("longitude");
  const polygonInput = document.getElementById("polygon_coordinates");
  const summary = document.getElementById("polygon-summary");
  const reset = document.getElementById("reset-polygon");
  const undo = document.getElementById("undo-point");
  const fit = document.getElementById("fit-polygon");
  const draw = document.getElementById("draw-polygon");
  const finish = document.getElementById("finish-polygon");
  const locate = document.getElementById("locate-position");
  const map = L.map(container).setView([40.3777, 71.7978], 13);
  addTiles(map);

  const points = [];
  const markers = [];
  let polygon = null;
  let locationMarker = null;
  let locationCircle = null;
  let drawing = true;

  const fromExisting = parseGeoJson(polygonInput.value || container.dataset.polygon);
  if (fromExisting.length) {
    fromExisting.slice(0, -1).forEach(([lng, lat]) => addPoint([lat, lng], false));
    redraw();
    fitBounds();
  }

  map.on("click", (event) => {
    if (drawing) addPoint([event.latlng.lat, event.latlng.lng]);
  });

  draw?.addEventListener("click", () => {
    drawing = !drawing;
    syncDrawingState();
  });
  finish?.addEventListener("click", () => {
    if (points.length < 3) {
      if (summary) summary.textContent = "Yakunlash uchun kamida 3 ta nuqta belgilang.";
      return;
    }
    drawing = false;
    redraw(true);
    syncDrawingState(true);
    fitBounds();
  });
  undo?.addEventListener("click", () => {
    const marker = markers.pop();
    if (marker) marker.remove();
    points.pop();
    redraw();
  });
  fit?.addEventListener("click", fitBounds);
  locate?.addEventListener("click", locatePosition);
  reset?.addEventListener("click", clearPolygon);
  syncDrawingState(false);

  function addPoint(latlng, shouldRedraw = true) {
    points.push(latlng);
    const marker = L.marker(latlng, { draggable: true }).addTo(map);
    marker.on("drag", () => {
      const index = markers.indexOf(marker);
      points[index] = [marker.getLatLng().lat, marker.getLatLng().lng];
      redraw();
    });
    marker.on("contextmenu", () => {
      const index = markers.indexOf(marker);
      marker.remove();
      markers.splice(index, 1);
      points.splice(index, 1);
      redraw();
    });
    markers.push(marker);
    if (shouldRedraw) redraw();
  }

  function redraw(finished = false) {
    if (polygon) polygon.remove();
    polygon = null;
    if (points.length < 3) {
      polygonInput.value = "";
      latInput.value = "";
      lngInput.value = "";
      if (summary) summary.textContent = `${points.length} ta nuqta. Poligon uchun kamida 3 ta nuqta kerak.`;
      return;
    }

    polygon = L.polygon(points, { color: "#159a82", fillColor: "#159a82", fillOpacity: .24 }).addTo(map);
    const center = polygon.getBounds().getCenter();
    latInput.value = center.lat.toFixed(7);
    lngInput.value = center.lng.toFixed(7);
    polygonInput.value = JSON.stringify({
      type: "Feature",
      geometry: {
        type: "Polygon",
        coordinates: [[...points.map(([lat, lng]) => [Number(lng.toFixed(7)), Number(lat.toFixed(7))]), [Number(points[0][1].toFixed(7)), Number(points[0][0].toFixed(7))]]],
      },
      properties: {},
    });
    if (summary) {
      summary.textContent = finished
        ? `Poligon yakunlandi. ${points.length} ta nuqta tanlandi. Markaz: ${latInput.value}, ${lngInput.value}`
        : `${points.length} ta nuqta tanlandi. Yakunlash tugmasini bosing yoki chizishni davom ettiring.`;
    }
  }

  function syncDrawingState(finished = false) {
    draw?.classList.toggle("active", drawing);
    finish?.classList.toggle("active", finished);
    if (draw) draw.textContent = drawing ? "Chizish" : "Davom ettirish";
    if (finish) finish.textContent = finished ? "Yakunlandi" : "Yakunlash";
  }

  function fitBounds() {
    if (polygon) map.fitBounds(polygon.getBounds(), { padding: [30, 30] });
  }

  function clearPolygon() {
    markers.splice(0).forEach((marker) => marker.remove());
    points.splice(0);
    if (polygon) polygon.remove();
    polygon = null;
    drawing = true;
    syncDrawingState(false);
    latInput.value = "";
    lngInput.value = "";
    polygonInput.value = "";
    if (summary) summary.textContent = "Poligon hali chizilmagan.";
  }

  async function locatePosition() {
    if (!navigator.geolocation) {
      showToast("Brauzer joylashuvni aniqlashni qo'llab-quvvatlamaydi yoki sahifa xavfsiz HTTPS orqali ochilmagan.", "error");
      return;
    }

    if (!window.isSecureContext) {
      alert("Joylashuvni aniqlash uchun sahifani ishonchli HTTPS orqali ochish kerak. HTTP yoki self-signed SSL bo'lsa browser ruxsat oynasini ko'rsatmaydi.");
      return;
    }

    if (!confirm("Brauzer joylashuvingizni aniqlash uchun ruxsat so'raydi. Ruxsat berasizmi?")) {
      return;
    }

    try {
      const permission = await navigator.permissions?.query({ name: "geolocation" });
      if (permission?.state === "denied") {
        alert("Joylashuvga ruxsat oldin bloklangan. Browser sozlamalaridan ushbu sayt uchun Location/Geolocation ruxsatini yoqing.");
        return;
      }
    } catch {
      // Some browsers do not expose geolocation through the Permissions API.
    }

    const originalText = locate?.textContent || "Joylashuvni top";
    if (locate) {
      locate.disabled = true;
      locate.textContent = "Aniqlanmoqda...";
    }
    const restoreLocateButton = () => {
      if (locate) {
        locate.disabled = false;
        locate.textContent = originalText;
      }
    };

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const latlng = [position.coords.latitude, position.coords.longitude];
        const accuracy = position.coords.accuracy || 0;

        if (locationMarker) locationMarker.remove();
        if (locationCircle) locationCircle.remove();

        locationMarker = L.marker(latlng).addTo(map).bindPopup("Joriy joylashuv").openPopup();
        locationCircle = L.circle(latlng, {
          radius: accuracy,
          color: "#0b6f78",
          fillColor: "#0b6f78",
          fillOpacity: .12,
          weight: 1,
        }).addTo(map);

        map.setView(latlng, 17);
        if (summary) {
          summary.textContent = "Joriy joylashuv topildi. Poligon chizish uchun xaritada nuqtalarni belgilang.";
        }
        restoreLocateButton();
      },
      (error) => {
        const messages = {
          1: "Joylashuvga ruxsat berilmadi. Browser manzil qatoridagi ruxsat sozlamalaridan Location ruxsatini yoqing.",
          2: "Joylashuvni aniqlab bo'lmadi.",
          3: "Joylashuvni aniqlash vaqti tugadi.",
        };
        showToast(messages[error.code] || "Joylashuvni aniqlashda xatolik yuz berdi.", "error");
        restoreLocateButton();
      },
      {
        enableHighAccuracy: true,
        timeout: 12000,
        maximumAge: 30000,
      },
    );

    window.setTimeout(restoreLocateButton, 13000);
  }
}

function initShowMap() {
  const container = document.getElementById("show-map");
  if (!container) return;
  if (container.dataset.mapReady === "1") return;
  if (typeof L === "undefined") {
    loadLeaflet().then(initShowMap).catch(() => {
      container.textContent = "Xaritani yuklab bo'lmadi.";
    });
    return;
  }
  container.dataset.mapReady = "1";
  const map = L.map(container).setView([40.3777, 71.7978], 13);
  addTiles(map);
  const coords = parseGeoJson(container.dataset.polygon);
  if (!coords.length) return;
  const latlngs = coords.map(([lng, lat]) => [lat, lng]);
  const polygon = L.polygon(latlngs, { color: "#159a82", fillColor: "#159a82", fillOpacity: .24 }).addTo(map);
  map.fitBounds(polygon.getBounds(), { padding: [30, 30] });
}

function loadLeaflet() {
  if (typeof L !== "undefined") return Promise.resolve();
  if (window.leafletLoadingPromise) return window.leafletLoadingPromise;

  window.leafletLoadingPromise = new Promise((resolve, reject) => {
    const script = document.createElement("script");
    script.src = "/vendor/leaflet/leaflet.js";
    script.async = true;
    script.onload = resolve;
    script.onerror = reject;
    document.head.appendChild(script);
  });

  return window.leafletLoadingPromise;
}

function addTiles(map) {
  L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    updateWhenIdle: true,
    keepBuffer: 1,
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);
}

function parseGeoJson(value) {
  try {
    const parsed = typeof value === "string" ? JSON.parse(value) : value;
    return parsed?.geometry?.coordinates?.[0] || parsed?.coordinates?.[0] || [];
  } catch {
    return [];
  }
}

window.checkCadastreRestriction = async function (cadastreNumber) {
  const warningDiv = document.getElementById("cadastre-restriction-warning");
  const messageEl = document.getElementById("cadastre-restriction-message");
  const titleEl = document.getElementById("cadastre-restriction-title");
  const input = document.getElementById("building_cadastr_number");
  const submitButton = document.querySelector('button[type="submit"]');
  if (!warningDiv || !messageEl || !input) return;

  const value = String(cadastreNumber || "").trim();
  if (value === "") {
    warningDiv.classList.add("hidden");
    warningDiv.classList.remove("danger", "success");
    input.classList.remove("border-red-500");
    if (submitButton) submitButton.disabled = false;
    return;
  }

  if (!/^\d{2}:\d{2}:\d{2}:\d{2}:\d{2}:\d{4}([/:].+)?$/.test(value)) {
    warningDiv.classList.remove("hidden", "success");
    warningDiv.classList.add("danger");
    if (titleEl) titleEl.textContent = "Kadastr formati noto‘g‘ri";
    messageEl.textContent = "Asosiy qism 10:08:04:01:02:5006 formatida bo'lishi shart. Undan keyingi / yoki : qismi erkin.";
    input.classList.add("border-red-500");
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.classList.add("opacity-50", "cursor-not-allowed");
    }
    return;
  }

  try {
    warningDiv.classList.remove("hidden", "danger", "success");
    if (titleEl) titleEl.textContent = "Kadastr tekshirilmoqda";
    messageEl.textContent = "Kadastr raqami bo‘yicha cheklov tekshirilmoqda...";
    if (submitButton) submitButton.disabled = true;

    const response = await fetch("/api/check-cadastre-restriction", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        cadastre_number: value,
        registry_request_id: input.closest("form")?.dataset.requestId || null,
      }),
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const data = await response.json();

    if (data.restricted) {
      warningDiv.classList.remove("success");
      warningDiv.classList.add("danger");
      if (titleEl) titleEl.textContent = "Kadastr cheklovi";
      messageEl.textContent = data.message;
      input.classList.add("border-red-500");
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.classList.add("opacity-50", "cursor-not-allowed");
      }
      warningDiv.scrollIntoView({ behavior: "smooth", block: "center" });
    } else {
      warningDiv.classList.remove("danger");
      warningDiv.classList.add("success");
      if (titleEl) titleEl.textContent = "Kadastr tekshirildi";
      messageEl.textContent = data.message || "Bu kadastr raqami bo‘yicha cheklov topilmadi.";
      input.classList.remove("border-red-500");
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.classList.remove("opacity-50", "cursor-not-allowed");
      }
    }
  } catch (error) {
    console.error("Error checking cadastre restriction:", error);
    warningDiv.classList.remove("hidden", "success");
    warningDiv.classList.add("danger");
    if (titleEl) titleEl.textContent = "Kadastr tekshiruvi ishlamadi";
    messageEl.textContent = "Kadastrni hozir tekshirib bo‘lmadi. Ma’lumotni saqlash bloklanmaydi.";
    input.classList.remove("border-red-500");
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.classList.remove("opacity-50", "cursor-not-allowed");
    }
  }
};

async function initMonitoringMap() {
  const root = document.querySelector("[data-monitoring-map]");
  if (!root) return;

  const dataNode = root.querySelector("[data-monitoring-districts]");
  const panel = root.querySelector("[data-monitoring-panel]");
  const canvas = root.querySelector(".fergana-map-canvas");
  if (!dataNode || !panel || !canvas) return;

  if (!canvas.querySelector("svg") && canvas.dataset.mapSrc) {
    try {
      const response = await fetch(canvas.dataset.mapSrc, { cache: "force-cache" });
      if (response.ok) {
        canvas.insertAdjacentHTML("beforeend", await response.text());
      }
    } catch {
      canvas.insertAdjacentHTML("beforeend", "<p>Xaritani yuklab bo'lmadi.</p>");
    }
  }

  const svg = canvas.querySelector("svg");
  if (!svg) return;

  const normalize = (value) => String(value || "")
    .toLowerCase()
    .replace(/[ʻ’‘`´]/g, "'")
    .replace(/qo'/g, "qo")
    .replace(/g'/g, "g")
    .replace(/o'/g, "o")
    .replace(/[^a-z0-9\u0400-\u04ff]+/g, "")
    .trim();

  let rows = [];
  try {
    rows = JSON.parse(dataNode.textContent || "[]");
  } catch {
    rows = [];
  }

  const dataByName = new Map(rows.map((row) => [normalize(row.name), row]));
  const paths = [...svg.querySelectorAll("path[aria-label]")];

  const fields = {
    name: panel.querySelector("[data-panel-name]"),
    summary: panel.querySelector("[data-panel-summary]"),
    count: panel.querySelector("[data-panel-count]"),
    area: panel.querySelector("[data-panel-area]"),
    approved: panel.querySelector("[data-panel-approved]"),
    types: panel.querySelector("[data-panel-types]"),
    link: panel.querySelector("[data-panel-link]"),
    mapLabel: root.querySelector("[data-map-selected-label]"),
  };

  const number = (value, decimals = 0) => Number(value || 0).toLocaleString("uz-UZ", {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });

  const rowForPath = (path) => {
    const label = path.getAttribute("aria-label") || "";
    return dataByName.get(normalize(label)) || {
      name: label || "Hudud",
      count: 0,
      total_area: 0,
      approved: 0,
      street_types: {},
      url: "/requests",
    };
  };

  const renderPanel = (row) => {
    fields.name.textContent = row.name;
    if (fields.mapLabel) fields.mapLabel.textContent = row.name;
    const areaLabel = row.total_area_label || number(row.total_area, 2);
    fields.summary.textContent = `${number(row.count)} ta xatlov, ${areaLabel} kv/m maydon.`;
    fields.count.textContent = number(row.count);
    fields.area.textContent = areaLabel;
    fields.approved.textContent = number(row.approved);
    fields.link.href = row.url || "/requests";

    fields.types.innerHTML = "";
    Object.entries(row.street_types || {}).forEach(([label, value]) => {
      const item = document.createElement("div");
      item.innerHTML = `<span>${escapeHtml(label)}</span><strong>${number(value)}</strong>`;
      fields.types.appendChild(item);
    });
  };

  const selectPath = (path) => {
    paths.forEach((item) => item.classList.remove("selected"));
    path.classList.add("selected");
    renderPanel(rowForPath(path));
  };

  paths.forEach((path) => {
    const row = rowForPath(path);
    path.dataset.count = String(row.count || 0);
    path.classList.toggle("has-data", Number(row.count || 0) > 0);
    path.setAttribute("tabindex", "0");
    path.setAttribute("role", "button");
    path.addEventListener("click", () => selectPath(path));
    path.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        selectPath(path);
      }
    });
  });

  const firstWithData = paths.find((path) => Number(path.dataset.count || 0) > 0);
  if (firstWithData) selectPath(firstWithData);
}
