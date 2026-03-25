<?php

namespace App\Helpers;

class BarcodeHelper
{
    /**
     * Generate a barcode for a variant if not already set
     * Format: VARIANT-ID (9 digits with leading zeros)
     *
     * @param int $variantId
     * @return string
     */
    public static function generate(int $variantId): string
    {
        return 'VAR' . str_pad($variantId, 10, '0', STR_PAD_LEFT);
    }

    /**
     * Validate barcode format
     *
     * @param string $barcode
     * @return bool
     */
    public static function isValid(string $barcode): bool
    {
        // Allow alphanumeric barcodes, 5-20 characters
        return preg_match('/^[A-Z0-9]{5,20}$/i', $barcode) === 1;
    }
}
