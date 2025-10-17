<?php
/**
 * Security Headers - Protection navigateur
 * À inclure au début de chaque page publique
 */

// Content Security Policy (CSP) - Protège contre XSS
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'");

// X-Content-Type-Options - Empêche le browser de deviner le MIME type
header("X-Content-Type-Options: nosniff");

// X-Frame-Options - Protège contre le clickjacking
header("X-Frame-Options: SAMEORIGIN");

// X-XSS-Protection - Active le filtre XSS du navigateur (pour les vieux navigateurs)
header("X-XSS-Protection: 1; mode=block");

// Referrer-Policy - Contrôle les informations envoyées dans le header Referer
header("Referrer-Policy: strict-origin-when-cross-origin");

// Permissions-Policy - Contrôle les APIs browser disponibles
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Strict-Transport-Security - Force HTTPS (activer uniquement en production avec HTTPS)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

// Cache-Control pour les pages dynamiques
if (!isset($_SERVER['REQUEST_URI']) || !preg_match('/\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2)$/', $_SERVER['REQUEST_URI'])) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
}
?>
