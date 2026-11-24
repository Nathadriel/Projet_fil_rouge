// BetOnMe front scripts (no inline script, to respect the Content-Security-Policy)

document.addEventListener('DOMContentLoaded', () => {
    initCookieBanner();
    initCountdowns();
    initGainPreview();
    initAutoSubmit();
    initConfirm();
});

// Cookie banner: the choice is saved for 6 months (CNIL recommendation)
function initCookieBanner() {
    const banner = document.querySelector('[data-cookie-banner]');
    if (!banner) return;

    const KEY = 'betonme_consent';
    const SIX_MONTHS = 1000 * 60 * 60 * 24 * 182;
    let saved = null;

    try {
        saved = JSON.parse(localStorage.getItem(KEY));
    } catch (e) {
        saved = null;
    }

    if (!saved || Date.now() - saved.date > SIX_MONTHS) {
        banner.hidden = false;
    }

    banner.querySelectorAll('[data-cookie-choice]').forEach((button) => {
        button.addEventListener('click', () => {
            try {
                localStorage.setItem(KEY, JSON.stringify({ choice: button.dataset.cookieChoice, date: Date.now() }));
            } catch (e) {
                // storage not available: the banner will be shown again
            }
            banner.hidden = true;
        });
    });

    document.querySelectorAll('[data-cookie-reopen]').forEach((button) => {
        button.addEventListener('click', () => {
            banner.hidden = false;
        });
    });
}

// Countdown until the bets are closed
function initCountdowns() {
    document.querySelectorAll('[data-countdown]').forEach((element) => {
        const end = new Date(element.getAttribute('datetime')).getTime();

        const tick = () => {
            const diff = Math.max(0, end - Date.now());
            const days = Math.floor(diff / 86400000);
            const hours = Math.floor(diff / 3600000) % 24;
            const minutes = Math.floor(diff / 60000) % 60;
            const seconds = Math.floor(diff / 1000) % 60;

            element.textContent = diff === 0
                ? 'clôturé'
                : `${days} j ${hours} h ${String(minutes).padStart(2, '0')} min ${String(seconds).padStart(2, '0')} s`;
        };

        tick();
        setInterval(tick, 1000);
    });
}

// Shows the potential gain (amount x odds) while typing
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

// Event filters: submit the form when a filter changes
function initAutoSubmit() {
    document.querySelectorAll('[data-autosubmit]').forEach((element) => {
        element.addEventListener('change', () => element.form.submit());
    });
}

// Asks for a confirmation before an irreversible action
function initConfirm() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
}
