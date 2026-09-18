<?php

namespace Tests\Unit;

use App\Services\Payroll\EthiopiaPayrollCalculator;
use PHPUnit\Framework\TestCase;

class EthiopiaPayrollCalculatorTest extends TestCase
{
    public function test_paye_brackets_and_pension_on_salary_month(): void
    {
        $line = EthiopiaPayrollCalculator::computePayrollLine([
            'basic_salary' => 30000,
            'days_worked' => 30,
        ]);

        $this->assertSame(30000.0, $line['salary_month']);
        $this->assertSame(2100.0, $line['employee_pension']);
        $this->assertSame(3300.0, $line['employer_pension']);
        $this->assertSame(30000.0, $line['gross']);
        $this->assertSame(8450.0, $line['paye']);
        $this->assertSame(19450.0, $line['net']);
    }

    public function test_transport_added_after_deductions(): void
    {
        $line = EthiopiaPayrollCalculator::computePayrollLine([
            'basic_salary' => 10000,
            'days_worked' => 30,
            'transport_allowance' => 500,
        ]);

        $this->assertSame(10000.0, $line['gross']);
        $this->assertSame(1650.0, $line['paye']);
        $this->assertSame(700.0, $line['employee_pension']);
        $this->assertSame(8150.0, $line['net']);
    }
}
