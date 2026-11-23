// BetOnMe — scripts front (aucun script inline, pour respecter la Content-Security-Policy)
document.addEventListener('DOMContentLoaded', () => {
    initCookieBanner();
    initCountdowns();
    initGainPreview();
    initAutoSubmit();
    initConfirm();
});

/* Bandeau cookies : le choix est mémorisé 6 mois (recommandation CNIL) */
function initCookieBanner() {
    const banner = document.querySelector('[data-cookie-banner]');
    if (!banner) return;

    const KEY = 'betonme_consent';
    const SIX_MONTHS = 1000 * 60 * 60 * 24 * 182;
    let saved = null;
    try { saved = JSON.parse(localStorage.getItem(KEY)); } catch (e) { saved = null; }

    if (!saved || Date.now() - saved.date > SIX_MONTHS) {
        banner.hidden = false;
    }

    banner.querySelectorAll('[data-cookie-choice]').forEach((button) => {
        button.addEventListener('click', () => {
            try {
                localStorage.setItem(KEY, JSON.stringify({ choice: button.dataset.cookieChoice, date: Date.now() }));
            } catch (e) { /* stockage indisponible : le bandeau réapparaîtra */ }
            banner.hidden = true;
        });
    });

    document.querySelectorAll('[data-cookie-reopen]').forEach((button) => {
        button.addEventListener('click', () => { banner.hidden = false; });
    });
}

/* Compte à rebours jusqu'à la clôture des paris */
function initCountdowns() {
    document.querySelectorAll('[data-countdown]').forEach((el) => {
        const end = new Date(el.getAttribute('datetime')).getTime();
        const tick = () => {
            const diff = Math.max(0, end - Date.now());
            const d = Math.floor(diff / 86400000);
            const h = Math.floor(diff / 3600000) % 24;
            const m = Math.floor(diff / 60000) % 60;
            const s = Math.floor(diff / 1000) % 60;
            el.textContent = diff === 0 ? 'clôturé' : `${d} j ${h} h ${String(m).padStart(2, '0')} min ${String(s).padStart(2, '0')} s`;
        };
        tick();
        setInterval(tick, 1000);
    });
}

/* Aperçu du gain potentiel (mise x cote) pendant la saisie */
function initGainPreview() {
    const form = document.querySelector('[data-bet-form]');
    if (!form) return;
    const preview = form.querySelector('[data-gain-preview]');
    const amount = form.querySelector('[name="amount"]');

    const update = () => {
        const choice = form.querySelector('[name="choice"]:checked');
        const value = parseInt(amount.value, 10);
        if (!choice || !value || value < 1) return;
        const odds = parseFloat(choice.value === 'yes' ? form.dataset.oddsYes : form.dataset.oddsNo);
        preview.textContent = `Gain potentiel : ${Math.floor(value * odds)} BetCoins`;
    };
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
}

/* Filtres de la liste : soumission automatique au changement */
function initAutoSubmit() {
    document.querySelectorAll('[data-autosubmit]').forEach((el) => {
        el.addEventListener('change', () => el.form.submit());
    });
}

/* Demande de confirmation avant une action irréversible */
function initConfirm() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
}
