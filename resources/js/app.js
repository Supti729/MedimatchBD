/*
 * Medimatch BD — small, dependency-free UI behaviours.
 * Every behaviour is opt-in through a data-* attribute so Blade views stay readable
 * and pages still work (links and forms) if JavaScript fails to load.
 */

const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/* Mobile menu: <button data-menu-toggle aria-controls="id"> */
function initMenus() {
    $$('[data-menu-toggle]').forEach((button) => {
        const panel = document.getElementById(button.getAttribute('aria-controls'));
        if (!panel) return;
        button.addEventListener('click', () => {
            // Toggle the Tailwind `hidden` class (not the attribute) so `lg:block` can still show desktop sidebars.
            const open = panel.classList.toggle('hidden') === false;
            button.setAttribute('aria-expanded', String(open));
            button.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        });
    });
}

/* Dropdowns: <div data-dropdown><button data-dropdown-trigger>…</button><div data-dropdown-panel hidden>…</div></div> */
function initDropdowns() {
    const dropdowns = $$('[data-dropdown]');
    const closeAll = (except) => dropdowns.forEach((d) => {
        if (d === except) return;
        const panel = d.querySelector('[data-dropdown-panel]');
        const trigger = d.querySelector('[data-dropdown-trigger]');
        if (panel && !panel.hidden) {
            panel.hidden = true;
            trigger?.setAttribute('aria-expanded', 'false');
        }
    });

    dropdowns.forEach((dropdown) => {
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        const panel = dropdown.querySelector('[data-dropdown-panel]');
        if (!trigger || !panel) return;
        trigger.setAttribute('aria-expanded', 'false');
        trigger.addEventListener('click', (event) => {
            event.stopPropagation();
            const open = panel.hidden;
            closeAll(dropdown);
            panel.hidden = !open;
            trigger.setAttribute('aria-expanded', String(open));
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-dropdown]')) closeAll();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const openDropdown = dropdowns.find((d) => !d.querySelector('[data-dropdown-panel]')?.hidden);
        closeAll();
        openDropdown?.querySelector('[data-dropdown-trigger]')?.focus();
    });
}

/* Dialogs (confirmation modals): <button data-dialog-open="id">, <dialog id="id">…<button data-dialog-close> */
function initDialogs() {
    $$('[data-dialog-open]').forEach((button) => {
        button.addEventListener('click', () => document.getElementById(button.dataset.dialogOpen)?.showModal());
    });
    $$('dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close(); // click on the backdrop
        });
    });
    $$('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });
}

/* Drawers (mobile filters): <button data-drawer-open="id">, <aside id="id" data-drawer>…<button data-drawer-close> */
function initDrawers() {
    $$('[data-drawer]').forEach((drawer) => {
        let opener = null;
        const open = (trigger) => {
            opener = trigger;
            drawer.dataset.open = 'true';
            document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
            drawer.querySelector(FOCUSABLE)?.focus();
        };
        const close = () => {
            delete drawer.dataset.open;
            document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
            opener?.focus();
        };
        $$(`[data-drawer-open="${drawer.id}"]`).forEach((b) => b.addEventListener('click', () => open(b)));
        $$('[data-drawer-close]', drawer).forEach((b) => b.addEventListener('click', close));
        drawer.addEventListener('keydown', (event) => {
            if (!drawer.dataset.open) return;
            if (event.key === 'Escape') close();
            if (event.key === 'Tab') { // keep keyboard focus inside the open drawer
                const items = $$(FOCUSABLE, drawer).filter((el) => el.offsetParent !== null);
                const first = items[0], last = items[items.length - 1];
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        });
    });
}

/* Division → District → Upazila cascade. Data comes from <script id="bd-locations" type="application/json">. */
function initLocationSelects() {
    const source = document.getElementById('bd-locations');
    if (!source) return;
    const data = JSON.parse(source.textContent);

    const fill = (select, names, placeholder, selected) => {
        select.innerHTML = '';
        select.append(new Option(placeholder, ''));
        names.forEach((name) => select.append(new Option(name, name, false, name === selected)));
        select.disabled = names.length === 0;
    };

    $$('[data-location-group]').forEach((group) => {
        const division = group.querySelector('[data-location="division"]');
        const district = group.querySelector('[data-location="district"]');
        const upazila = group.querySelector('[data-location="upazila"]');
        if (!division || !district) return;

        const refreshDistricts = (selected) => {
            const districts = data[division.value] ? Object.keys(data[division.value]) : [];
            fill(district, districts, district.dataset.placeholder, selected);
            refreshUpazilas(upazila?.dataset.selected);
        };
        const refreshUpazilas = (selected) => {
            if (!upazila) return;
            const list = data[division.value]?.[district.value] ?? [];
            fill(upazila, list, upazila.dataset.placeholder, selected);
        };

        refreshDistricts(district.dataset.selected);
        division.addEventListener('change', () => refreshDistricts());
        district.addEventListener('change', () => refreshUpazilas());
    });
}

/* Loading state: <form data-loading> disables its submit button while the request is in flight. */
function initLoadingForms() {
    $$('form[data-loading]').forEach((form) => {
        form.addEventListener('submit', () => {
            form.setAttribute('aria-busy', 'true');
            $$('button[type="submit"]', form).forEach((button) => {
                button.disabled = true;
                button.dataset.originalText = button.innerHTML;
                button.innerHTML = `<span class="inline-block size-4 animate-spin rounded-full border-2 border-current border-r-transparent align-[-2px]" aria-hidden="true"></span> ${button.dataset.loadingText ?? 'Please wait…'}`;
            });
        });
    });
    // Restore buttons if the page is shown again from the back/forward cache.
    window.addEventListener('pageshow', () => $$('button[data-original-text]').forEach((b) => { b.disabled = false; b.innerHTML = b.dataset.originalText; }));
}

/* Show / hide password: <button data-password-toggle="inputId"> */
function initPasswordToggles() {
    $$('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;
        button.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.textContent = show ? 'Hide' : 'Show';
            button.setAttribute('aria-pressed', String(show));
        });
    });
}

/* Local image preview before upload: <input type="file" data-image-preview="containerId"> */
function initImagePreviews() {
    $$('input[type="file"][data-image-preview]').forEach((input) => {
        const container = document.getElementById(input.dataset.imagePreview);
        if (!container) return;
        input.addEventListener('change', () => {
            container.innerHTML = '';
            Array.from(input.files).slice(0, Number(input.dataset.max ?? 4)).forEach((file) => {
                if (!file.type.startsWith('image/')) return;
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = `Selected photo: ${file.name}`;
                img.className = 'aspect-square w-full rounded-lg border border-line object-cover';
                container.append(img);
            });
        });
    });
}

/* Character counter: <textarea data-counter="counterId" maxlength="…"> */
function initCounters() {
    $$('[data-counter]').forEach((field) => {
        const counter = document.getElementById(field.dataset.counter);
        const update = () => { counter.textContent = `${field.value.length} / ${field.maxLength}`; };
        field.addEventListener('input', update);
        update();
    });
}

/* Dismissible alerts and keeping chat scrolled to the newest message. */
function initMisc() {
    $$('[data-dismiss]').forEach((button) => button.addEventListener('click', () => button.closest('[data-dismissible]')?.remove()));
    $$('[data-scroll-bottom]').forEach((el) => { el.scrollTop = el.scrollHeight; });
    $$('select[data-autosubmit]').forEach((select) => select.addEventListener('change', () => select.form?.requestSubmit()));
}

document.addEventListener('DOMContentLoaded', () => {
    initMenus();
    initDropdowns();
    initDialogs();
    initDrawers();
    initLocationSelects();
    initLoadingForms();
    initPasswordToggles();
    initImagePreviews();
    initCounters();
    initMisc();
});
