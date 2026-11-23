{{-- Bandeau de consentement (RGPD / recommandations CNIL) : aussi simple de refuser que d'accepter --}}
<div class="cookie-banner" data-cookie-banner hidden role="dialog" aria-labelledby="cookie-title" aria-describedby="cookie-text">
    <div class="container cookie-inner">
        <div>
            <p id="cookie-title" class="cookie-title">Cookies</p>
            <p id="cookie-text">
                BetOnMe utilise uniquement des cookies indispensables (session, sécurité).
                Avec votre accord, nous utiliserions aussi une mesure d'audience anonyme.
                <a href="{{ route('privacy') }}#cookies">En savoir plus</a>
            </p>
        </div>
        <div class="cookie-actions">
            <button type="button" class="btn btn-ghost" data-cookie-choice="refused">Tout refuser</button>
            <button type="button" class="btn" data-cookie-choice="accepted">Tout accepter</button>
        </div>
    </div>
</div>
