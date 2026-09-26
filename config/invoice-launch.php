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
        'hr',
        'marketing_manager',
        'sales_supervisor',
        'sales',
        'operations_manager_showroom',
        'operations_manager_factory',
        'assembler',
        'procurement',
        'designer',
        'product_manager',
    ],
];
