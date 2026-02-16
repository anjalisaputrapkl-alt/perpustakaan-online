<?php
/**
 * Theme Loader Engine - V6 (Vibrant Predominance Mode)
 * Introduces layout layer separation for higher contrast and vibrancy.
 */

if (!isset($pdo)) {
    $pdo = require __DIR__ . '/src/db.php';
}

require_once __DIR__ . '/src/ThemeModel.php';
$themeModel = new ThemeModel($pdo);

// 1. Dapatkan theme_key dari database
$school_id = $_SESSION['user']['school_id'] ?? 1;
$activeKey = $themeModel->checkSpecialTheme($school_id);

if ($activeKey) {
    $configPath = __DIR__ . '/theme-config.json';
    if (file_exists($configPath)) {
        $config = json_decode(file_get_contents($configPath), true);
        
        if (isset($config[$activeKey])) {
            $theme = $config[$activeKey];
            
            // Define Fallbacks & Variables
            $primary = $theme['primary_color'];
            $bg = $theme['background_color'];
            $text = $theme['text_color'];
            $pattern = $theme['pattern'];
            $card = $theme['card_color'] ?? '#ffffff';
            $border = $theme['border_color'] ?? '#e2e8f0';
            
            // Helper for RGB conversion
            if (!function_exists('hexToRgb_local')) {
                function hexToRgb_local($hex) {
                    $hex = str_replace("#", "", $hex);
                    if(strlen($hex) == 3) {
                        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
                        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
                        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
                    } else {
                        $r = hexdec(substr($hex,0,2));
                        $g = hexdec(substr($hex,2,2));
                        $b = hexdec(substr($hex,4,2));
                    }
                    return "$r, $g, $b";
                }
            }
            
            $rgb_primary = hexToRgb_local($primary);

            echo "<!-- National Holiday Theme Active: $activeKey (V6 - Vibrant) -->\n";
            echo "<script>window.isSpecialThemeActive = true;</script>\n";
            echo <<<EOT
            <style>
                :root {
                    /* System Variable Overrides */
                    --primary: {$primary} !important;
                    --accent: {$primary} !important;
                    --bg: {$bg} !important;
                    --text: {$text} !important;
                    /* Premium Design System Tokens */
                    --font-main: 'Inter', sans-serif;
                    --font-heading: 'Outfit', sans-serif;
                    
                    --primary: {$primary} !important;
                    --primary-bg: rgba({$rgb_primary}, 0.1);
                    --glass-bg: rgba(255, 255, 255, 0.7);
                    --glass-border: rgba(255, 255, 255, 0.4);
                    --glass-glow: rgba({$rgb_primary}, 0.3);
                    
                    --radius-lg: 24px;
                    --radius-md: 16px;
                    --shadow-premium: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
                    --shadow-glow: 0 0 20px -5px var(--glass-glow);
                }
                
                /* Global Font Override */
                body, .app, .content {
                    font-family: var(--font-main) !important;
                    -webkit-font-smoothing: antialiased;
                }

                h1, h2, h3, h4, h5, .nav-sidebar-header h2, .topbar strong {
                    font-family: var(--font-heading) !important;
                    font-weight: 700 !important;
                    letter-spacing: -0.02em;
                }

                /* State-of-the-Art Mesh Gradient Background */
                html body::before {
                    content: '';
                    position: fixed;
                    top: 0; left: 0; width: 100%; height: 100%;
                    z-index: -1;
                    background-color: {$bg};
                    background-image: 
                        radial-gradient(at 0% 0%, rgba({$rgb_primary}, 0.15) 0px, transparent 50%),
                        radial-gradient(at 100% 0%, rgba({$rgb_primary}, 0.1) 0px, transparent 50%),
                        radial-gradient(at 100% 100%, rgba({$rgb_primary}, 0.05) 0px, transparent 50%),
                        radial-gradient(at 0% 100%, rgba({$rgb_primary}, 0.1) 0px, transparent 50%);
                    animation: meshMove 20s ease infinite alternate;
                }

                @keyframes meshMove {
                    0% { filter: hue-rotate(0deg); }
                    100% { filter: hue-rotate(15deg); }
                }

                /* Advanced Glassmorphism Sidebar */
                html body .sidebar,
                html body #navSidebar:not(.nav-sidebar) {
                    background: var(--glass-bg) !important;
                    backdrop-filter: blur(20px) !important;
                    border-right: 1px solid var(--glass-border) !important;
                    box-shadow: none !important;
                }

                /* Premium Topbar & Header */
                html body .topbar,
                html body .header {
                    background: rgba(255, 255, 255, 0.5) !important;
                    backdrop-filter: blur(15px) !important;
                    border-bottom: 1px solid var(--glass-border) !important;
                    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.02) !important;
                }

                /* Card Overhaul - Modern "Floating" Look */
                html body .card, 
                html body .stat {
                    background: #ffffff !important;
                    border: 1px solid var(--glass-border) !important;
                    border-radius: var(--radius-lg) !important;
                    box-shadow: var(--shadow-premium) !important;
                    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
                    overflow: hidden;
                    position: relative;
                }

                html body .card::after {
                    content: '';
                    position: absolute;
                    top: 0; left: 0; width: 100%; height: 4px;
                    background: var(--primary);
                    opacity: 0.8;
                }

                html body .card:hover {
                    transform: translateY(-8px) scale(1.01) !important;
                    box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.15), var(--shadow-glow) !important;
                }

                /* Buttons - Specialized High-End Look */
                html body .btn-primary {
                    background: var(--primary) !important;
                    color: #ffffff !important;
                    border-radius: 12px !important;
                    padding: 12px 24px !important;
                    font-weight: 600 !important;
                    font-family: var(--font-heading) !important;
                    box-shadow: 0 10px 20px -5px rgba({$rgb_primary}, 0.3) !important;
                    transition: all 0.3s ease !important;
                    border: none !important;
                }

                html body .btn-primary:hover {
                    transform: translateY(-2px) !important;
                    box-shadow: 0 15px 30px -5px rgba({$rgb_primary}, 0.5) !important;
                }

                /* Administrative Components Balance */
                html body .nav-sidebar {
                    background: #0f172a !important; /* Keep Admin Dark for professionalism */
                    border-right: 1px solid rgba(255,255,255,0.05) !important;
                }

                .theme-effect-overlay {
                    position: fixed;
                    top: 0; left: 0; width: 100%; height: 100%;
                    pointer-events: none;
                    z-index: 9999;
                    opacity: 0.6;
                }

                /* Remove legacy ornaments */
                body::before, body::after, .app::before, .app::after {
                    background-image: none !important;
                }
            </style>
EOT;

            // Effect Engine Integration
            $effect = $theme['effect_type'] ?? null;
            if ($effect) {
                echo "<div class='theme-effect-overlay' id='themeEffectLayer'></div>\n";
                // Only load Script if needed
                echo "<script>
                    window.themeEffectConfig = {
                        type: '{$effect}',
                        primary: '{$primary}',
                        activeKey: '{$activeKey}'
                    };
                </script>\n";
                echo "<script src='/perpustakaan-online/assets/js/holiday-effects.js' defer></script>\n";
            }
            
            // Load specific theme CSS file if it exists
            $paths = ["/perpustakaan-online/public/themes/special/{$activeKey}.css"];
            foreach($paths as $p) {
                $fsPath = __DIR__ . str_replace('/perpustakaan-online', '', $p);
                if (file_exists($fsPath)) {
                    echo "<link rel='stylesheet' href='$p'>\n";
                }
            }
        }
    }
}
?>
