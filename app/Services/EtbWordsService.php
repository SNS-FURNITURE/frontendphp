<?php

namespace App\Services;

class EtbWordsService
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen',
        'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    public function amountInWords(float|string $amount): string
    {
        $money = round(abs((float) $amount), 2);
        $birr = (int) floor($money);
        $santim = (int) round(($money - $birr) * 100);

        $birrWords = $this->integerToWords($birr);
        if ($santim === 0) {
            return $birrWords.' Birr Only';
        }

        return $birrWords.' Birr and '.$this->integerToWords($santim).' Santim Only';
    }

    private function integerToWords(int $n): string
    {
        if ($n === 0) {
            return 'Zero';
        }

        $parts = [];
        $scales = [
            [1_000_000_000, 'Billion'],
            [1_000_000, 'Million'],
            [1_000, 'Thousand'],
        ];

        $remaining = $n;
        foreach ($scales as [$scale, $label]) {
            if ($remaining >= $scale) {
                $chunk = intdiv($remaining, $scale);
                $parts[] = $this->underThousand($chunk).' '.$label;
                $remaining %= $scale;
            }
        }
        if ($remaining > 0) {
            $parts[] = $this->underThousand($remaining);
        }

        return implode(' ', $parts);
    }

    private function underThousand(int $n): string
    {
        if ($n === 0) {
            return '';
        }
        if ($n < 20) {
            return self::ONES[$n];
        }
        if ($n < 100) {
            $t = intdiv($n, 10);
            $o = $n % 10;

            return $o ? self::TENS[$t].'-'.self::ONES[$o] : self::TENS[$t];
        }
        $h = intdiv($n, 100);
        $rest = $n % 100;

        return $rest
            ? self::ONES[$h].' Hundred '.$this->underThousand($rest)
            : self::ONES[$h].' Hundred';
    }
}
