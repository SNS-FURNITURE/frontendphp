<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice-only production launch allowlist
    |--------------------------------------------------------------------------
    |
    | Roles outside this set cannot use the ERP until the full product unlocks.
    | Sales supervisor covers showroom sales for this launch.
    |
    */
    'roles' => [
        'admin',
        'company_manager',
        'finance',
        'marketing_manager',
        'sales_supervisor',
        'sales',
        'operations_customer',
        'operations_factory',
    ],
];
