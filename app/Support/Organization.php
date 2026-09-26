<?php

namespace App\Support;

/**
 * Port of legacy-next ORGANIZATION_DIVISIONS for the static org chart page.
 */
class Organization
{
    /**
     * @return list<array{key: string, name: string, code: string, description: string, headTitle: string, headRole: string, units: list<array{name: string, description: string, roleKey: string, mergedNote?: string, subPositions?: list<string>}>}>
     */
    public static function divisions(): array
    {
        return [
            [
                'key' => 'executive',
                'name' => 'Executive Management',
                'code' => 'EXEC',
                'description' => 'Corporate leadership, strategic oversight, and inter-division management.',
                'headTitle' => 'General Manager',
                'headRole' => 'admin',
                'units' => [
                    [
                        'name' => 'General Management',
                        'description' => 'Company governance, capital approvals, and audit trails.',
                        'roleKey' => 'admin',
                    ],
                    [
                        'name' => 'Operations Desk',
                        'description' => 'Operational coordination across all 4 divisions.',
                        'roleKey' => 'company_manager',
                    ],
                ],
            ],
            [
                'key' => 'admin_hr',
                'name' => 'Administration & Human Resource Division',
                'code' => 'ADM-HR',
                'description' => 'Human capital, staff attendance, leave management, legal compliance, and public relations.',
                'headTitle' => 'HR & Public Relations Officer',
                'headRole' => 'hr',
                'units' => [
                    [
                        'name' => 'HR, Legal & Public Relations Desk',
                        'description' => 'Unified desk handling employee profiles, attendance, leave, legal compliance, and PR communications.',
                        'roleKey' => 'hr',
                        'mergedNote' => 'Legal Service & Public Relations merged with HR Officer',
                        'subPositions' => ['HR Officer', 'Legal Compliance Specialist', 'Public Relations Officer'],
                    ],
                ],
            ],
            [
                'key' => 'finance',
                'name' => 'Finance Division',
                'code' => 'FIN',
                'description' => 'Financial accounting, client invoicing, payment collections, and statutory Ethiopian payroll.',
                'headTitle' => 'Finance Lead',
                'headRole' => 'finance',
                'units' => [
                    [
                        'name' => 'Finance Unit',
                        'description' => 'Accounting, invoicing, payment collections, fund allocations, voucher verification, and statutory payroll.',
                        'roleKey' => 'finance',
                        'mergedNote' => 'General Accountant and Junior Accountant merged into Finance',
                        'subPositions' => ['Accountant', 'Bookkeeper'],
                    ],
                ],
            ],
            [
                'key' => 'commercial',
                'name' => 'Commercial Division',
                'code' => 'COMM',
                'description' => 'Marketing campaigns, client showroom sales, supplier purchasing, and warehouse inventory.',
                'headTitle' => 'Commercial Lead & Supervisor',
                'headRole' => 'sales_supervisor',
                'units' => [
                    [
                        'name' => 'Marketing & Promotion Unit',
                        'description' => 'Inbound customer leads, website quote inquiries, marketing promotions, and commercial document generation.',
                        'roleKey' => 'marketing_manager',
                        'subPositions' => ['Marketing Manager', 'Marketing Specialist', 'Promotion Officer'],
                    ],
                    [
                        'name' => 'Sales Unit',
                        'description' => 'Showroom consultations, client deal pipelines, and custom order requests.',
                        'roleKey' => 'sales_supervisor',
                        'subPositions' => ['Sales Supervisor'],
                    ],
                    [
                        'name' => 'Sales Representatives',
                        'description' => 'Showroom order capture, customer follow-up, and deal progression.',
                        'roleKey' => 'sales',
                        'subPositions' => ['Sales Representative'],
                    ],
                    [
                        'name' => 'Customer Operations',
                        'description' => 'Customer deliveries, site installation coordination, and order fulfillment.',
                        'roleKey' => 'operations_manager_showroom',
                        'subPositions' => ['Operation Manager Showroom (OMS)', 'Delivery Coordinator'],
                    ],
                    [
                        'name' => 'Purchasing Unit',
                        'description' => 'Market price research, supplier sourcing, external carpenter directory, and site installation dispatch.',
                        'roleKey' => 'procurement',
                        'subPositions' => ['Purchasing Officer', 'Sourcing Specialist'],
                    ],
                    [
                        'name' => 'Property & Supply Unit',
                        'description' => 'Store keeper managing warehouse raw materials, component inventory, and material requests.',
                        'roleKey' => 'inventory',
                        'subPositions' => ['Store Keeper', 'Inventory Controller'],
                    ],
                ],
            ],
            [
                'key' => 'production',
                'name' => 'Production Division',
                'code' => 'PROD',
                'description' => 'Furniture manufacturing, CAD design studio, workshop machinery, and quality control inspections.',
                'headTitle' => 'Product Manager & QC Foreman',
                'headRole' => 'product_manager',
                'units' => [
                    [
                        'name' => 'Design Studio',
                        'description' => 'CAD drafting, 3D product specifications, custom client orders, and design concepts.',
                        'roleKey' => 'designer',
                        'subPositions' => ['Furniture Designer', '3D CAD Specialist'],
                    ],
                    [
                        'name' => 'Factory Operations',
                        'description' => 'Workshop production runs, manufacturing orders, stock movements, and machinery upkeep.',
                        'roleKey' => 'operations_manager_factory',
                        'subPositions' => ['Operation Manager Factory (OMF)', 'Assembler'],
                    ],
                    [
                        'name' => 'Production Management & Quality Control',
                        'description' => 'Manufacturing order planning, BOM tracking, quality control inspections, and machinery maintenance.',
                        'roleKey' => 'product_manager',
                        'mergedNote' => 'Quality Control directly supervised by Product Manager & General Foreman',
                        'subPositions' => [
                            'Product Manager',
                            'General Foreman',
                            'Senior Technician',
                            'Senior Machinist & Helpers',
                            'Senior Carpenter & Helpers',
                            'Senior Painter & Helpers',
                            'Senior Welder & Helpers',
                            'Senior Upholsterer',
                            'Workshop Helpers',
                        ],
                    ],
                ],
            ],
        ];
    }
}
