<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice launch gate
    |--------------------------------------------------------------------------
    |
    | When true, only roles listed in config/invoice-launch.php may use the app
    | (except auth me/logout/csrf). Flip to false at CP15 to unlock full ERP.
    |
    */
    // Default false unlocks full ERP for all roles (CP15). Set true to re-gate launch roles.
    'invoice_launch_enabled' => (bool) env('INVOICE_LAUNCH_ENABLED', false),
];
