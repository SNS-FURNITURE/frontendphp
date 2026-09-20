<?php

declare(strict_types=1);

/**
 * Optional stub if Plesk document root is the Laravel project root.
 * Preferred fix: Hosting Settings → Document root = .../public
 */
$publicIndex = __DIR__.'/public/index.php';
if (is_file($publicIndex)) {
    require $publicIndex;

    return;
}

http_response_code(500);
header('Content-Type: text/plain; charset=UTF-8');
echo "Document root misconfigured. In Plesk, set Document root to the Laravel public/ folder.\n";
