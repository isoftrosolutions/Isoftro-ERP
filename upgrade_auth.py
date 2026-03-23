import os
import re

CONFIG_FILE = 'config/config.php'

def upgrade_config_php():
    if not os.path.exists(CONFIG_FILE):
        return

    with open(CONFIG_FILE, 'r', encoding='utf-8') as f:
        content = f.read()

    # Define function blocks
    blocks = {
        'isLoggedIn': """if (!function_exists('isLoggedIn')) {
    function isLoggedIn()
    {
        // 1. Check Session (Classic/Web)
        if (isset($_SESSION['userData']) && !empty($_SESSION['userData']['id'])) {
            return true;
        }
        
        // 2. Check JWT (Modern API)
        try {
            if (auth('api')->check()) {
                return true;
            }
        } catch (\\Exception $e) {
            // Silently fail if not an API request context
        }

        return false;
    }
}""",
        'getCurrentUser': """if (!function_exists('getCurrentUser')) {
    function getCurrentUser()
    {
        // 1. Try Session
        if (isset($_SESSION['userData'])) {
            return $_SESSION['userData'];
        }
        
        // 2. Try JWT
        try {
            if (auth('api')->check()) {
                $user = auth('api')->user();
                if ($user) {
                    return [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->full_name ?? $user->name ?? $user->email,
                        'role' => $user->role,
                        'tenant_id' => $user->tenant_id,
                        'avatar' => $user->avatar ?? $user->photo_url ?? null,
                        'is_jwt' => true
                    ];
                }
            }
        } catch (\\Exception $e) {
             // Not an API context
        }
        
        return null;
    }
}""",
        'verifyCSRFToken': """if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token)
    {
        // 1. Bypass CSRF for JWT/API requests
        try {
            if (auth('api')->check()) {
                return true; // JWT is its own security, CSRF not needed
            }
        } catch (\\Exception $e) {}

        // 2. Check traditional CSRF
        return \\App\\Helpers\\CsrfHelper::validateCsrfToken($token);
    }
}"""
    }

    # Use regex to find and replace the whole block
    for name, new_block in blocks.items():
        pattern = re.compile(rf"if \(!function_exists\('{name}'\)\) \{{.*?\}}", re.DOTALL)
        # Use lambda for replacement to avoid interpreting escapes
        content = pattern.sub(lambda m: new_block, content)

    with open(CONFIG_FILE, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Upgraded config.php successfully")

if __name__ == "__main__":
    upgrade_config_php()
