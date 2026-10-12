// Filtre texte côté client : <input data-filter="#table"> masque les lignes [data-filterable] qui ne correspondent pas.
// Les lignes d'en-tête de groupe [data-group] sont masquées quand tout leur groupe l'est.
(() => {
    const normalize = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    document.querySelectorAll('input[data-filter]').forEach((input) => {
        const table = document.querySelector(input.dataset.filter);
        const counter = input.dataset.counter ? document.querySelector(input.dataset.counter) : null;
        const rows = [...table.querySelectorAll('[data-filterable]')];
        rows.forEach((row) => { row.dataset.text = normalize(row.textContent); });

        input.addEventListener('input', () => {
            const query = normalize(input.value.trim());
            let visible = 0;
            rows.forEach((row) => {
                row.hidden = query !== '' && !row.dataset.text.includes(query);
                if (!row.hidden) visible++;
            });
            table.querySelectorAll('[data-group]').forEach((header) => {
                header.hidden = !rows.some((row) => row.dataset.groupOf === header.dataset.group && !row.hidden);
            });
            if (counter) counter.textContent = `${visible} pointage${visible > 1 ? 's' : ''}`;
        });
    });
})();
