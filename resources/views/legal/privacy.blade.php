@extends('layouts.app')

@section('title', 'Politique de confidentialité')

@section('content')
<div class="narrow prose">
    <h1>Politique de confidentialité</h1>
    <p>Dernière mise à jour : {{ now()->translatedFormat('d F Y') }}</p>

    <h2>Responsable du traitement</h2>
    <p>BetOnMe est un projet pédagogique réalisé par Nathadriel Cocagne dans le cadre de la certification Développeur Full Stack (RNCP 38606).
        Contact / DPO : <a href="mailto:dpo@betonme.fr">dpo@betonme.fr</a>.</p>

    <h2>Données collectées et finalités</h2>
    <ul>
        <li><strong>Compte</strong> : pseudo, adresse e-mail, mot de passe (haché, jamais stocké en clair) — pour vous authentifier.</li>
        <li><strong>Activité de jeu</strong> : paris, événements proposés, mouvements de BetCoins — pour faire fonctionner le service et le classement.</li>
        <li><strong>Journaux techniques</strong> : adresse IP lors des échecs de connexion — pour la sécurité (protection contre le brute force).</li>
    </ul>
    <p>Base légale : exécution du service que vous avez demandé (article 6.1.b du RGPD) et intérêt légitime pour la sécurité (article 6.1.f).
        Le principe de minimisation est appliqué : aucune donnée bancaire, d'identité ou de localisation n'est demandée.</p>

    <h2>Durée de conservation</h2>
    <p>Vos données sont conservées tant que votre compte est actif. Les journaux de sécurité sont conservés 14 jours.
        La suppression de votre compte efface immédiatement vos données personnelles et vos paris.</p>

    <h2>Vos droits</h2>
    <p>Vous disposez d'un droit d'accès, de rectification, d'effacement, de portabilité et d'opposition. Depuis votre
        <a href="{{ route('profile.show') }}">profil</a>, vous pouvez télécharger vos données (format JSON) et supprimer votre compte.
        Pour toute autre demande, écrivez à <a href="mailto:dpo@betonme.fr">dpo@betonme.fr</a>. Vous pouvez aussi saisir la
        <a href="https://www.cnil.fr/fr/plaintes" rel="noopener">CNIL</a>.</p>

    <h2 id="cookies">Cookies</h2>
    <p>Cookies strictement nécessaires (exemptés de consentement) : cookie de session et jeton CSRF.
        Cookie de mesure d'audience : déposé uniquement si vous l'acceptez via le bandeau. Votre choix est conservé 6 mois
        et peut être modifié à tout moment via le lien « Gérer les cookies » en bas de page.</p>

    <h2>Jeu responsable</h2>
    <p>Les BetCoins n'ont aucune valeur monétaire et ne peuvent pas être convertis en argent réel. Si vous ou un proche avez
        un problème avec les jeux d'argent, contactez Joueurs Info Service au 09 74 75 13 13 (appel non surtaxé).</p>
</div>
@endsection
