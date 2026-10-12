// Formulaire de pointage : lignes chantier/durée dynamiques, recherche de collaborateurs, récapitulatif en direct.
(() => {
    const form = document.getElementById('clocking-form');
    if (!form) return;

    const entries = document.getElementById('clocking-entries');
    const people = [...form.querySelectorAll('[data-person]')];
    const search = document.getElementById('people-search');
    const peopleCount = document.getElementById('people-count');
    const submit = form.querySelector('button[type="submit"]');
    const submitLabel = submit.textContent.trim();
    const dateInput = form.querySelector('input[type="date"]');
    const normalize = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    // --- Lignes chantier / durée ---
    document.getElementById('add-clocking-entry').addEventListener('click', () => {
        const index = Number(entries.dataset.index);
        entries.insertAdjacentHTML('beforeend', entries.dataset.prototype.replace(/__name__/g, String(index)));
        entries.dataset.index = String(index + 1);
        entries.lastElementChild.querySelector('select').focus();
        refresh();
    });

    entries.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-entry]');
        if (button && entries.querySelectorAll('[data-entry]').length > 1) {
            button.closest('[data-entry]').remove();
            refresh();
        }
    });

    // --- Collaborateurs ---
    people.forEach((person) => { person.dataset.text = normalize(person.textContent); });

    search.addEventListener('input', () => {
        const query = normalize(search.value.trim());
        people.forEach((person) => { person.hidden = query !== '' && !person.dataset.text.includes(query); });
    });

    document.getElementById('people-clear').addEventListener('click', () => {
        people.forEach((person) => { person.querySelector('input').checked = false; });
        refresh();
    });

    // --- Récapitulatif ---
    const fmtDate = (value) => value ? value.split('-').reverse().join('/') : '—';

    function refresh() {
        const selected = people.filter((person) => person.querySelector('input').checked).length;
        const lines = [...entries.querySelectorAll('[data-entry]')].map((row) => {
            const select = row.querySelector('[data-entry-project]');
            const hours = parseInt(row.querySelector('[data-entry-duration]').value, 10);
            return {
                project: select.value ? select.options[select.selectedIndex].text.replace(/\s*\(.*\)$/, '') : null,
                hours: Number.isFinite(hours) && hours > 0 ? hours : 0,
            };
        }).filter((line) => line.project && line.hours > 0);

        const hours = lines.reduce((sum, line) => sum + line.hours, 0);
        const scale = Math.max(10, hours);

        peopleCount.textContent = selected === 0 ? 'Aucun sélectionné' : `${selected} sélectionné${selected > 1 ? 's' : ''}`;
        document.getElementById('sum-people').textContent = selected;
        document.getElementById('sum-projects').textContent = lines.length;
        document.getElementById('sum-date').textContent = fmtDate(dateInput.value);
        document.getElementById('sum-hours').textContent = hours;
        document.getElementById('sum-scale').textContent = `${scale} h`;

        const tape = document.getElementById('sum-tape');
        tape.style.setProperty('--scale', scale);
        tape.firstElementChild.innerHTML = lines.map((line) =>
            `<span class="tape__seg" style="flex: 0 0 calc(${line.hours} / ${scale} * 100% - 2px)" title="${line.project.replace(/"/g, '&quot;')} : ${line.hours} h">${line.hours} h</span>`
        ).join('');

        submit.textContent = selected > 1 ? `Enregistrer ${selected} pointages` : submitLabel;
        document.getElementById('sum-note').textContent = selected > 1 && hours > 0
            ? `${selected} × ${hours} h = ${selected * hours} h au total`
            : '';
    }

    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    refresh();
})();
