<?php

namespace App\Services;

use RuntimeException;

/** Erreur métier affichable à l'utilisateur (solde insuffisant, pari fermé...) */
class BettingException extends RuntimeException {}
