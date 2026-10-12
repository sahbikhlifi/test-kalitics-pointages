// Remplace le confirm() natif par une fenêtre <dialog> qui rappelle ce qui va être supprimé.
(() => {
    const dialog = document.getElementById('delete-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') return; // navigateur sans <dialog> : suppression directe

    const slot = (name) => dialog.querySelector(`[data-slot="${name}"]`);
    let pendingForm = null;

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-confirm-delete]');
        if (!form) return;
        event.preventDefault();
        pendingForm = form;

        slot('user').textContent = form.dataset.user;
        slot('date').textContent = form.dataset.date;
        slot('total').textContent = form.dataset.total;
        const entries = slot('entries');
        entries.replaceChildren(...form.dataset.entries.split('|').filter(Boolean).map((line) => {
            const [project, hours] = line.split(' : ');
            const li = document.createElement('li');
            li.append(Object.assign(document.createElement('span'), { textContent: project }));
            li.append(Object.assign(document.createElement('span'), { textContent: hours }));
            return li;
        }));

        dialog.returnValue = '';
        dialog.showModal();
    });

    // Clic sur le fond : on ferme sans supprimer.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close('cancel');
    });

    dialog.addEventListener('close', () => {
        if (dialog.returnValue === 'confirm' && pendingForm) {
            pendingForm.querySelector('button[type="submit"]').disabled = true;
            pendingForm.submit(); // submit() ne redéclenche pas l'événement : pas de boucle
        }
        pendingForm = null;
    });
})();
