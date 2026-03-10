<?php
/**
 * E-Lib Digital Library - Login Page
 * Modern Dark Mode Glassmorphism UI
 */

session_start();

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/auth.php';
require_once 'includes/csrf_protection.php';
require_once 'includes/advanced_input_validator.php';

// Initialize CSRF protection
CSRFProtectionManager::initialize();

$auth = new AuthManager();

if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$rateLimited = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Requête invalide. Veuillez réessayer.';
    } else {
        // Use AdvancedInputValidator for input validation
        $validationRules = [
            'username' => [
                'required' => true,
                'type' => 'string',
                'min_length' => 1,
                'max_length' => 50,
                'sanitize' => true
            ],
            'password' => [
                'required' => true,
                'type' => 'string',
                'min_length' => 1,
                'sanitize' => false
            ]
        ];
        
        $inputData = [
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? ''
        ];
        
        $validationResult = AdvancedInputValidator::validateAndSanitize($inputData, $validationRules);
        
        if (!$validationResult['valid']) {
            $error = 'Veuillez entrer votre nom d\'utilisateur et mot de passe.';
        } else {
            $validatedData = $validationResult['data'];
            
            if (!RateLimiter::checkLoginAttempts($validatedData['username'])) {
                $error = 'Trop de tentatives. Réessayez dans 15 minutes.';
                $rateLimited = true;
            } else {
                if ($auth->login($validatedData['username'], $validatedData['password'])) {
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - E-Lib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' },
                        accent: { 500: '#6366f1', 600: '#4f46e5', 400: '#818cf8' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.1); }
        .glow { box-shadow: 0 0 40px rgba(99, 102, 241, 0.3); }
        .input-glow:focus { box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-20px); } }
        .float { animation: float 6s ease-in-out infinite; }
        .float-delay { animation: float 6s ease-in-out infinite; animation-delay: -3s; }
    </style>
</head>
<body class="bg-dark-900 min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-accent-500/10 rounded-full blur-3xl float"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl float-delay"></div>
    </div>
    
    <div class="relative z-10 w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-accent-500 to-accent-600 mb-4 glow">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">E-Lib</h1>
            <p class="text-gray-400">Bibliothèque Numérique</p>
        </div>
        
        <!-- Login Card -->
        <div class="glass rounded-3xl p-8">
            <h2 class="text-xl font-semibold text-white mb-6 text-center">Connexion</h2>
            
            <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm"><?= escape_html($error) ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" <?= $rateLimited ? 'class="opacity-50 pointer-events-none"' : '' ?>>
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="mb-5">
                    <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Nom d'utilisateur</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input type="text" id="username" name="username" required
                            class="w-full bg-dark-700/50 border border-white/10 rounded-xl pl-12 pr-4 py-3.5 text-white placeholder-gray-500 focus:outline-none focus:border-accent-500 input-glow transition-all duration-300"
                            value="<?= escape_html($_POST['username'] ?? '') ?>"
                            placeholder="Entrez votre nom d'utilisateur">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Mot de passe</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input type="password" id="password" name="password" required
                            class="w-full bg-dark-700/50 border border-white/10 rounded-xl pl-12 pr-4 py-3.5 text-white placeholder-gray-500 focus:outline-none focus:border-accent-500 input-glow transition-all duration-300"
                            placeholder="Entrez votre mot de passe">
                    </div>
                </div>
                
                <button type="submit" <?= $rateLimited ? 'disabled' : '' ?>
                    class="w-full bg-gradient-to-r from-accent-500 to-accent-600 text-white py-3.5 px-4 rounded-xl font-medium hover:from-accent-600 hover:to-accent-700 focus:ring-4 focus:ring-accent-500/30 transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]">
                    Se connecter
                </button>
            </form>
        </div>
        
        <!-- Register Link -->
        <div class="mt-6 text-center">
            <p class="text-gray-400">
                Pas encore de compte? 
                <a href="register.php" class="text-accent-400 hover:text-accent-300 font-medium transition-colors">
                    Créer un compte
                </a>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>&copy; <?= date('Y') ?> Placy Rodnel DIMI MBONGO. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
