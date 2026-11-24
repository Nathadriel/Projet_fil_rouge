<footer class="site-footer">
    <div class="container footer-inner">
        <p>© {{ date('Y') }} BetOnMe — monnaie 100 % fictive, aucun argent réel n'est en jeu.</p>
        <ul class="footer-links">
            <li><a href="{{ route('privacy') }}">Confidentialité</a></li>
            <li><a href="{{ route('legal') }}">Mentions légales</a></li>
            <li><button type="button" class="link-button" data-cookie-reopen>Gérer les cookies</button></li>
        </ul>
    </div>
</footer>
