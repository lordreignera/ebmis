<?php

if (!function_exists('numberToWords')) {
    /**
     * Convert number to words (works without intl extension)
     * 
     * @param float|int $number
     * @return string
     */
    function numberToWords($number)
    {
        // Handle intl extension if available
        if (class_exists('NumberFormatter')) {
            try {
                $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
                return ucwords($formatter->format($number));
            } catch (\Exception $e) {
                // Fall through to manual conversion
            }
        }
        
        // Manual conversion (fallback)
        $number = (int) $number;
        
        $ones = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
            14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
            18 => 'Eighteen', 19 => 'Nineteen'
        ];
        
        $tens = [
            0 => '', 2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
        ];
        
        $thousands = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];
        
        if ($number == 0) {
            return 'Zero';
        }
        
        if ($number < 0) {
            return 'Minus ' . numberToWords(abs($number));
        }
        
        $words = [];
        $thousandIndex = 0;
        
        while ($number > 0) {
            $chunk = $number % 1000;
            
            if ($chunk != 0) {
                $chunkWords = '';
                
                // Hundreds
                $hundreds = (int) ($chunk / 100);
                if ($hundreds > 0) {
                    $chunkWords .= $ones[$hundreds] . ' Hundred';
                    if ($chunk % 100 != 0) {
                        $chunkWords .= ' ';
                    }
                }
                
                // Tens and ones
                $remainder = $chunk % 100;
                if ($remainder < 20) {
                    $chunkWords .= $ones[$remainder];
                } else {
                    $tensDigit = (int) ($remainder / 10);
                    $onesDigit = $remainder % 10;
                    $chunkWords .= $tens[$tensDigit];
                    if ($onesDigit > 0) {
                        $chunkWords .= ' ' . $ones[$onesDigit];
                    }
                }
                
                if ($thousands[$thousandIndex]) {
                    $chunkWords .= ' ' . $thousands[$thousandIndex];
                }
                
                array_unshift($words, $chunkWords);
            }
            
            $number = (int) ($number / 1000);
            $thousandIndex++;
        }
        
        return trim(implode(' ', $words));
    }
}
