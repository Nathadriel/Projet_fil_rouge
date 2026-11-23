# BetOnMe

Plateforme de paris **fictifs** et humoristiques : les joueurs misent des BetCoins (monnaie sans valeur réelle)
sur des événements improbables. Projet fil rouge de la certification Développeur Full Stack (RNCP 38606).

## Stack

- Laravel 13 (PHP 8.3), architecture MVC
- Blade pour les vues, CSS et JS natifs (aucune dépendance front)
- MySQL (WAMP) en local, SQLite possible
- PHPUnit pour les tests, Pint pour le style de code

## Installation (WAMP)

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Dans `.env`, choisir la base de données :

```dotenv
# SQLite (le plus simple)
DB_CONNECTION=sqlite

# ou MySQL via WAMP (créer la base "betonme" dans phpMyAdmin)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=betonme
DB_USERNAME=root
DB_PASSWORD=
```

Créer les tables avec les données de démonstration, puis lancer le serveur :

```bash
php artisan migrate:fresh --seed
php artisan serve
```

Le site est accessible sur http://127.0.0.1:8000

## Comptes de démonstration

Mot de passe commun : `Password!2026`

| E-mail | Rôle | Particularité |
|---|---|---|
| admin@betonme.test | Administrateur | accès au back-office `/admin` |
| lea@betonme.test | Joueur, grade Parieur | peut proposer des événements |
| tom@betonme.test | Joueur, grade Novice | 100 BetCoins, ne peut pas encore proposer |

## Fonctionnalités

- Inscription (100 BetCoins offerts), connexion avec limitation des tentatives
- Liste des événements avec filtres (ouverts / clôturés / catégorie), détail avec compte à rebours
- Pari OUI / NON avec aperçu du gain (mise × cote), un pari par événement
- Profil : solde, grade, progression, historique des paris et des transactions
- Grades (Novice → Parieur → Expert → Légende) : le grade Parieur débloque la proposition d'événements
- Back-office : valider (en fixant les cotes) ou refuser, résoudre (distribution des gains) ou annuler (remboursement)
- Classement hebdomadaire (top 10 des gains depuis lundi)
- RGPD : bandeau cookies, politique de confidentialité, export JSON des données, suppression du compte

## Tests

```bash
php artisan test
./vendor/bin/pint --test
```

## Documentation

La conception (MCD, cas d'utilisation, diagramme de classes, séquence) est dans [docs/CONCEPTION.md](docs/CONCEPTION.md).
