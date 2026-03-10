<?php
/**
 * E-Lib - Gestionnaire d'erreurs
 * Masque les avertissements VCRUNTIME140.dll sur Windows
 */

// Masquer les avertissements VCRUNTIME140.dll spécifiquement
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Ignorer les avertissements VCRUNTIME140.dll
    if (strpos($errstr, 'VCRUNTIME140.dll') !== false) {
        return true; // Masquer cette erreur
    }
    
    // Laisser passer les autres erreurs
    return false;
}, E_WARNING);

// Alternative : Masquer tous les avertissements (non recommandé en production)
// error_reporting(E_ALL & ~E_WARNING);
?>