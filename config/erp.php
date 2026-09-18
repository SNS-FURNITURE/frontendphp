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
    'invoice_launch_enabled' => (bool) env('INVOICE_LAUNCH_ENABLED', true),
];
