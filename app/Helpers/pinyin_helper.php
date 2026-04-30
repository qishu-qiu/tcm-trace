<?php

if (!function_exists('pinyin_first_letter')) {
    function pinyin_first_letter(string $chineseStr): string
    {
        static $pinyinMap = null;
        
        if ($pinyinMap === null) {
            $pinyinMap = require __DIR__ . '/pinyin_map.php';
        }

        $result = '';
        $strLen = mb_strlen($chineseStr, 'UTF-8');

        for ($i = 0; $i < $strLen; $i++) {
            $char = mb_substr($chineseStr, $i, 1, 'UTF-8');
            $code = ord($char);

            if ($code >= 0x4E00 && $code <= 0x9FA5) {
                $found = false;
                foreach ($pinyinMap as $range => $letter) {
                    list($start, $end) = explode('-', $range);
                    $start = hexdec($start);
                    $end = hexdec($end);
                    
                    if ($code >= $start && $code <= $end) {
                        $result .= $letter;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $result .= 'A';
                }
            } else {
                $result .= strtoupper($char);
            }
        }

        return $result;
    }
}

if (!function_exists('generate_batch_no')) {
    function generate_batch_no(string $productName, int $sequence): string
    {
        $letters = pinyin_first_letter($productName);
        $letters = strtoupper(substr($letters, 0, 4));
        $year = date('y');
        $seq = sprintf('%06d', $sequence);
        
        return $letters . $year . $seq;
    }
}
