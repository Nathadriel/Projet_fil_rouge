{{-- BetOnMe logo: a rising neon chart ending with an arrow --}}
<svg class="logo-mark" viewBox="0 0 64 48" width="54" height="40" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="logo-grad" x1="0" y1="1" x2="1" y2="0">
            <stop offset="0" stop-color="#b026ff"/>
            <stop offset=".5" stop-color="#00d4ff"/>
            <stop offset="1" stop-color="#ffe600"/>
        </linearGradient>
        <filter id="logo-glow" x="-30%" y="-30%" width="160%" height="160%">
            <feGaussianBlur stdDeviation="2" result="blur"/>
            <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
        </filter>
    </defs>
    <g filter="url(#logo-glow)" fill="none" stroke="url(#logo-grad)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="4,42 16,30 24,36 38,18 46,24 56,8"/>
    </g>
    <polygon points="60,2 61,20 44,9" fill="#ffe600" filter="url(#logo-glow)"/>
</svg>
