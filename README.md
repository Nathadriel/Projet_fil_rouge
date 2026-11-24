# BetOnMe

Plateforme de paris **fictifs** et humoristiques : les joueurs misent des BetCoins (monnaie sans valeur réelle)
sur des événements improbables. Projet fil rouge de la certification Développeur Full Stack (RNCP 38606).

## Stack

- Laravel 13 (PHP 8.3), architecture MVC
- Blade (layout, partials, composant `x-event-card`), CSS et JS compilés par Vite
- spatie/laravel-permission pour les rôles, Policies pour les autorisations
- stripe/stripe-php pour le paiement (mode test)
- Laravel Sanctum pour l'API, l5-swagger pour sa documentation
- MySQL (WAMP) en local, SQLite possible
- PHPUnit pour les tests, Pint pour le style de code

## Installation (WAMP)

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
npm run build
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

## Paiement Stripe (mode test)

1. Créer un compte gratuit sur https://dashboard.stripe.com et rester en **mode test**.
2. Copier la clé secrète (Développeurs → Clés API) dans `.env` :

```dotenv
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

3. Lancer la Stripe CLI pour recevoir le webhook en local (elle affiche le `whsec_...`) :

```bash
stripe listen --forward-to 127.0.0.1:8000/stripe/webhook
```

4. Payer avec la carte de test `4242 4242 4242 4242`, une date future et n'importe quel CVC.

## Actualités (NewsAPI)

Créer une clé gratuite sur https://newsapi.org et l'ajouter dans `.env` :

```dotenv
NEWSAPI_KEY=...
```

Les derniers titres s'affichent sur la page des événements (filtrés par catégorie) et sur le formulaire de proposition.
Les résultats sont gardés en cache 30 minutes. Sans clé, la section n'apparaît simplement pas.

## API REST (Sanctum)

L'API est disponible sous `/api/v1` et documentée avec Swagger :

```bash
php artisan l5-swagger:generate
```

Puis ouvrir http://127.0.0.1:8000/api/documentation. Se connecter avec `POST /login`, copier le `access_token`,
cliquer sur **Authorize** et coller le token pour tester les routes protégées.

| Méthode | Route | Accès |
|---|---|---|
| POST | /register, /login | public (10 essais / minute) |
| GET | /events, /events/{id}, /leaderboard | public |
| POST | /logout | token |
| GET | /me, /me/bets | token |
| POST | /events/{id}/bets | token |
| POST | /events | token, grade Parieur |

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
- Boutique : packs de BetCoins payés par Stripe Checkout (mode test), limite de 3 achats par 24 h
- RGPD : bandeau cookies, politique de confidentialité, export JSON des données, suppression du compte

## Tests

```bash
php artisan test
./vendor/bin/pint --test
```

## Documentation

La conception (MCD, cas d'utilisation, diagramme de classes, séquence) est dans [docs/CONCEPTION.md](docs/CONCEPTION.md).
