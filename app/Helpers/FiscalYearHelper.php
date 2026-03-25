<?php

namespace App\Helpers;

use Carbon\Carbon;

class FiscalYearHelper
{
    /**
     * Get the current Nepali fiscal year in format YYYY/YY
     * 
     * Nepali fiscal year runs from Shrawan 1 to Ashadh 30 (mid-July to mid-July)
     * We'll use July 16 as the start date for simplicity.
     *
     * @return string Format: 2082/83
     */
    public static function getCurrentFiscalYear(): string
    {
        $now = Carbon::now();
        
        // Nepali fiscal year starts on July 16 (Shrawan 1)
        $startOfFY = Carbon::parse($now->year . '-07-16');
        
        if ($now->lt($startOfFY)) {
            // We're before the FY start, so we're in the previous FY
            $endYear = $now->year;
            $startYear = $endYear - 1;
        } else {
            // We're after the FY start
            $startYear = $now->year;
            $endYear = $now->year + 1;
        }
        
        return sprintf('%d/%d', $startYear, substr($endYear, -2));
    }

    /**
     * Validate if a fiscal year is in correct format
     *
     * @param string $fiscalYear Format: 2082/83
     * @return bool
     */
    public static function isValidFormat(string $fiscalYear): bool
    {
        return preg_match('/^\d{4}\/\d{2}$/', $fiscalYear) === 1;
    }

    /**
     * Get the next bill number for a fiscal year
     * Bill numbers are sequential within a fiscal year
     *
     * @param string $fiscalYear
     * @return int
     */
    public static function getNextBillNumber(string $fiscalYear): int
    {
        $lastSale = \App\Models\Sale::where('fiscal_year', $fiscalYear)
            ->orderByDesc('id')
            ->first();

        if (!$lastSale) {
            return 1;
        }

        // Extract the numeric part from bill_number and increment
        // Bill number format: FY-001, FY-002, etc.
        $lastBillNo = (int) preg_replace('/[^\d]/', '', $lastSale->bill_number);
        return $lastBillNo + 1;
    }

    /**
     * Format bill number for a fiscal year
     *
     * @param string $fiscalYear
     * @param int $number
     * @return string Format: 2082/83-001
     */
    public static function formatBillNumber(string $fiscalYear, int $number): string
    {
        return sprintf('%s-%03d', $fiscalYear, $number);
    }
}
