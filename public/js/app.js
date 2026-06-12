document.addEventListener("DOMContentLoaded", () => {
  initSteps();
  initDependentSelects();
  initStreetCreator();
  initOwnerType();
  initMasks();
  initCadastreCheck();
  initAreaCalculator();
  initImageSlots();
  initDraftPersistence();
  initPolygonMap();
  initShowMap();
});

function initSteps() {
  const steps = [...document.querySelectorAll("[data-step-target]")];
  const panels = [...document.querySelectorAll("[data-step-panel]")];
  const next = document.querySelector("[data-step-next]");
  const prev = document.querySelector("[data-step-prev]");
  const submit = document.querySelector('button[type="submit"]');
  if (!steps.length || !panels.length) return;

  let current = Math.max(1, Number(sessionStorage.getItem("requestFormStep") || 1));
  const setStep = (step) => {
    current = Math.max(1, Math.min(step, panels.length));
    sessionStorage.setItem("requestFormStep", String(current));
    steps.forEach((button) => {
      const value = Number(button.dataset.stepTarget);
      button.classList.toggle("active", value === current);
      button.classList.toggle("complete", value < current);
    });
    panels.forEach((panel) => panel.classList.toggle("active", Number(panel.dataset.stepPanel) === current));
    if (prev) prev.disabled = current === 1;
    if (next) next.hidden = current === panels.length;
    if (submit) submit.hidden = current !== panels.length;
    window.setTimeout(() => window.dispatchEvent(new Event("resize")), 80);
  };

  steps.forEach((button) => button.addEventListener("click", () => setStep(Number(button.dataset.stepTarget))));
  next?.addEventListener("click", () => setStep(current + 1));
  prev?.addEventListener("click", () => setStep(current - 1));
  setStep(current);
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
  };

  district.addEventListener("change", sync);
  mahalla.addEventListener("change", sync);
  sync();
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
  const slashIndex = raw.indexOf("/");
  const beforeSlash = slashIndex >= 0 ? raw.slice(0, slashIndex) : raw;
  const typedSuffix = slashIndex >= 0 ? raw.slice(slashIndex + 1) : "";
  const digits = beforeSlash.replace(/\D/g, "").slice(0, 14);
  const overflow = beforeSlash.replace(/\D/g, "").slice(14);
  const mainGroups = [2, 2, 2, 2, 2, 4];
  const mainParts = [];
  let offset = 0;

  mainGroups.forEach((size) => {
    const part = digits.slice(offset, offset + size);
    if (part) mainParts.push(part);
    offset += size;
  });

  const main = mainParts.join(":");
  const suffix = slashIndex >= 0 ? typedSuffix : overflow;

  return suffix ? `${main}/${suffix}` : main;
}

function initCadastreCheck() {
  const input = document.getElementById("building_cadastr_number");
  if (!input) return;

  input.addEventListener("blur", () => window.checkCadastreRestriction(input.value));
}

function initAreaCalculator() {
  const length = document.getElementById("area_length");
  const width = document.getElementById("area_width");
  const calculated = document.getElementById("calculated_land_area");
  const total = document.getElementById("total_area");
  if (!length || !width || !calculated) return;

  const calculate = () => {
    const result = Number(length.value || 0) * Number(width.value || 0);
    calculated.value = result > 0 ? result.toFixed(2) : "";
    if (total && !total.value) total.value = calculated.value;
  };

  length.addEventListener("input", calculate);
  width.addEventListener("input", calculate);
  calculate();
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
      const imageKey = `request-form-draft:${location.pathname}:images`;
      const index = [...document.querySelectorAll('[data-image-slot] input[type="file"]')].indexOf(input);
      const data = readJson(imageKey, {});
      delete data[index];
      localStorage.setItem(imageKey, JSON.stringify(data));
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
  const imageDraft = readJson(imageKey, {});

  restoreDraft(form, draft);
  restoreImageDrafts(form, imageDraft, imageKey);

  form.addEventListener("input", () => saveDraft(form, draftKey));
  form.addEventListener("change", (event) => {
    saveDraft(form, draftKey);
    if (event.target.matches('[data-image-slot] input[type="file"]')) {
      saveImageSlot(event.target, imageKey);
    }
  });

  form.addEventListener("submit", () => {
    sessionStorage.removeItem("requestFormStep");
    localStorage.removeItem(draftKey);
    localStorage.removeItem(imageKey);
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
    localStorage.setItem(key, JSON.stringify(data));
  } catch (error) {
    console.warn("Draft saqlanmadi:", error);
  }
}

function restoreDraft(form, data) {
  Object.entries(data || {}).forEach(([name, value]) => {
    const fields = [...form.querySelectorAll(`[name="${CSS.escape(name)}"]`)];
    fields.forEach((field) => {
      if (field.type === "checkbox") field.checked = Boolean(value);
      else if (field.type === "radio") field.checked = field.value === value;
      else field.value = value;
    });
  });
  document.getElementById("district_id")?.dispatchEvent(new Event("change"));
  document.getElementById("mahalla_id")?.dispatchEvent(new Event("change"));
  document.querySelector('input[name="owner_type"]:checked')?.dispatchEvent(new Event("change"));
  document.getElementById("area_length")?.dispatchEvent(new Event("input"));
}

async function saveImageSlot(input, imageKey) {
  const file = input.files?.[0];
  if (!file) return;
  const slot = [...document.querySelectorAll('[data-image-slot] input[type="file"]')].indexOf(input);
  const data = readJson(imageKey, {});
  try {
    data[slot] = {
      name: file.name,
      type: file.type || "image/jpeg",
      dataUrl: await fileToDataUrl(file),
    };
    localStorage.setItem(imageKey, JSON.stringify(data));
  } catch (error) {
    console.warn("Rasm draft sifatida saqlanmadi:", error);
  }
}

function restoreImageDrafts(form, imageDraft, imageKey) {
  form.querySelectorAll("[data-image-slot]").forEach((slot, index) => {
    const saved = imageDraft?.[index];
    if (!saved?.dataUrl) return;
    const input = slot.querySelector('input[type="file"]');
    const preview = slot.querySelector(".image-preview");
    const clear = slot.querySelector(".image-clear");
    if (!input || !preview || !clear) return;

    const file = dataUrlToFile(saved.dataUrl, saved.name || `rasm-${index + 1}.jpg`, saved.type || "image/jpeg");
    const transfer = new DataTransfer();
    transfer.items.add(file);
    input.files = transfer.files;
    preview.src = saved.dataUrl;
    preview.classList.remove("hidden");
    clear.classList.remove("hidden");

    clear.addEventListener("click", () => {
      const data = readJson(imageKey, {});
      delete data[index];
      localStorage.setItem(imageKey, JSON.stringify(data));
    });
  });
}

function fileToDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

function dataUrlToFile(dataUrl, name, type) {
  const [header, body] = dataUrl.split(",");
  const mime = type || header.match(/data:(.*?);/)?.[1] || "application/octet-stream";
  const binary = atob(body);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
  return new File([bytes], name, { type: mime });
}

function readJson(key, fallback) {
  try {
    return JSON.parse(localStorage.getItem(key)) || fallback;
  } catch {
    return fallback;
  }
}

function initPolygonMap() {
  const container = document.getElementById("polygon-map");
  if (!container || typeof L === "undefined") return;

  const latInput = document.getElementById("latitude");
  const lngInput = document.getElementById("longitude");
  const polygonInput = document.getElementById("polygon_coordinates");
  const summary = document.getElementById("polygon-summary");
  const reset = document.getElementById("reset-polygon");
  const undo = document.getElementById("undo-point");
  const fit = document.getElementById("fit-polygon");
  const draw = document.getElementById("draw-polygon");
  const map = L.map(container).setView([40.3777, 71.7978], 13);
  addTiles(map);

  const points = [];
  const markers = [];
  let polygon = null;
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
    draw.classList.toggle("active", drawing);
    draw.textContent = drawing ? "Chizish" : "Pauza";
  });
  undo?.addEventListener("click", () => {
    const marker = markers.pop();
    if (marker) marker.remove();
    points.pop();
    redraw();
  });
  fit?.addEventListener("click", fitBounds);
  reset?.addEventListener("click", clearPolygon);

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

  function redraw() {
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
    if (summary) summary.textContent = `${points.length} ta nuqta tanlandi. Markaz: ${latInput.value}, ${lngInput.value}`;
  }

  function fitBounds() {
    if (polygon) map.fitBounds(polygon.getBounds(), { padding: [30, 30] });
  }

  function clearPolygon() {
    markers.splice(0).forEach((marker) => marker.remove());
    points.splice(0);
    if (polygon) polygon.remove();
    polygon = null;
    latInput.value = "";
    lngInput.value = "";
    polygonInput.value = "";
    if (summary) summary.textContent = "Poligon hali chizilmagan.";
  }
}

function initShowMap() {
  const container = document.getElementById("show-map");
  if (!container || typeof L === "undefined") return;
  const map = L.map(container).setView([40.3777, 71.7978], 13);
  addTiles(map);
  const coords = parseGeoJson(container.dataset.polygon);
  if (!coords.length) return;
  const latlngs = coords.map(([lng, lat]) => [lat, lng]);
  const polygon = L.polygon(latlngs, { color: "#159a82", fillColor: "#159a82", fillOpacity: .24 }).addTo(map);
  map.fitBounds(polygon.getBounds(), { padding: [30, 30] });
}

function addTiles(map) {
  L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
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

  if (!/^\d{2}:\d{2}:\d{2}:\d{2}:\d{2}:\d{4}(\/.+)?$/.test(value)) {
    warningDiv.classList.remove("hidden", "success");
    warningDiv.classList.add("danger");
    if (titleEl) titleEl.textContent = "Kadastr formati noto‘g‘ri";
    messageEl.textContent = "Asosiy qism 10:08:04:01:02:5006 formatida bo‘lishi shart. Undan keyingi / qismi erkin.";
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
      body: JSON.stringify({ cadastre_number: value }),
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
