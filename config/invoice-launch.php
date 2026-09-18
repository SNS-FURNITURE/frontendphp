<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice-only production launch allowlist
    |--------------------------------------------------------------------------
    |
    | Roles outside this set cannot use the ERP until the full product unlocks.
    | Advisor is treated as supervisor-equivalent for this launch.
    |
    */
    'roles' => [
        'admin',
        'company_manager',
        'finance',
        'marketing_manager',
        'advisor',
        'supervisor',
        'sales_supervisor',
    ],
];
