<?php
declare(strict_types=1);

function render_auth_start(string $title, string $headline, string $tagline, string $subheadline): void
{
    render_head($title);
    echo '<body class="auth-page"><button class="theme-toggle theme-toggle-floating" type="button" data-theme-toggle aria-label="Switch to dark mode" title="Toggle dark mode"><i class="bi bi-moon-stars"></i></button>';
    echo '<main class="auth-layout"><section class="auth-showcase"><a class="brand-mark brand-mark-light" href="index.php"><span class="brand-symbol"><i class="bi bi-buildings"></i></span><span>Baryo<span class="brand-light">Care</span></span></a>';
    echo '<div class="showcase-copy"><span class="showcase-kicker"><span></span> YOUR COMMUNITY, YOUR VOICE</span><h1>' . escape($headline) . '</h1><p>' . escape($subheadline) . '</p>';
    echo '<div class="showcase-illustration" aria-hidden="true"><div class="illustration-glow"></div><div class="illustration-building"><i class="bi bi-buildings-fill"></i></div><div class="illustration-roof"></div>';
    echo '<div class="illustration-card illustration-card-left"><i class="bi bi-chat-left-heart-fill"></i><span>We hear you</span></div><div class="illustration-card illustration-card-right"><i class="bi bi-people-fill"></i><span>Better together</span></div>';
    echo '</div>';
    echo '<div class="showcase-quote"><span class="quote-mark">“</span><p>' . escape($tagline) . '</p><span class="quote-caption">Listen · Respond · Grow together</span></div></div>';
    echo '<p class="showcase-footer"><i class="bi bi-shield-check"></i> A safe, respectful space for every neighbor.</p></section>';
    echo '<section class="auth-main"><a class="brand-mark auth-mobile-brand" href="index.php"><span class="brand-symbol"><i class="bi bi-buildings"></i></span><span>Baryo<span class="brand-light">Care</span></span></a><div class="auth-panel">';
}

function render_auth_end(): void
{
    echo '</div><p class="auth-legal">Community first <i class="bi bi-heart-fill"></i></p></section></main></body></html>';
}
