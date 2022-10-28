#!/usr/bin/env php
<?php

// build ordered list of codepoints
$shortcodes = require(__DIR__ . '/../src/shortcodes-array.php');

$codepoints = [];
foreach ($shortcodes as $hexcodes) {
    foreach (explode('-', $hexcodes) as $hexcode) {
        $codepoints[] = hexdec($hexcode);
    }
}

sort($codepoints);

// convert codepoints to UTF-8 and build structured list
$utf8 = [];
foreach ($codepoints as $dec) {
    $chr = mb_chr($dec, 'UTF-8');
    $hex = unpack('H*', $chr);
    $chars = array_chunk(str_split(reset($hex)), 2);
    $bytes = array_map('implode', $chars);

    foreach (range(0, 3) as $padding) {
        if ($padding >= count($bytes)) {
            array_unshift($bytes, '00');
        }
    }

    $arr = &$utf8;
    foreach ($bytes as $offset => $byte) {
        if ($offset === 3) {
            $arr[] = $byte;
            continue;
        }

        if (!isset($arr[$byte])) {
            $arr[$byte] = [];
        }

        $arr = &$arr[$byte];
    }
}

// build simplified regex
$expressions = [];
foreach ($utf8 as $firstByte => $secondBytes) {
    foreach ($secondBytes as $secondByte => $thirdBytes) {
        $first = $last = null;
        $little = [];

        foreach ($thirdBytes as $thirdByte => $fourthBytes) {
            if (!$first) {
                $first = $last = hexdec($thirdByte);
                $little = $fourthBytes;
                continue;
            }

            if ((hexdec($thirdByte) - $last) > 1) {
                sort($little);

                $littleFirst = array_shift($little);
                $littleLast = array_pop($little);

                if (!$littleLast) {
                    $littleLast = $littleFirst;
                }

                $expression = sprintf(
                    '\x%s\x%s%s%s',
                    strtoupper($firstByte),
                    strtoupper($secondByte),
                    $first === $last ? sprintf('\x%s', strtoupper(dechex($first))) : sprintf('[\x%s-\x%s]', strtoupper(dechex($first)), strtoupper(dechex($last))),
                    $littleFirst === $littleLast ? sprintf('\x%s', strtoupper($littleFirst)) : sprintf('[\x%s-\x%s]', strtoupper($littleFirst), strtoupper($littleLast)),
                );

                $expression = preg_replace('/^(\\\\x00)+/', '', $expression);
                $expressions[] = $expression;

                $first = $last = hexdec($thirdByte);
                $little = [];
            }

            $last = hexdec($thirdByte);

            foreach ($fourthBytes as $fourthByte) {
                $little[] = $fourthByte;
            }
        }

        sort($little);

        $littleFirst = array_shift($little);
        $littleLast = array_pop($little);

        if (!$littleLast) {
            $littleLast = $littleFirst;
        }

        $expression = sprintf(
            '\x%s\x%s%s%s',
            strtoupper($firstByte),
            strtoupper($secondByte),
            $first === $last ? sprintf('\x%s', strtoupper(dechex($first))) : sprintf('[\x%s-\x%s]', strtoupper(dechex($first)), strtoupper(dechex($last))),
            $littleFirst === $littleLast ? sprintf('\x%s', strtoupper($littleFirst)) : sprintf('[\x%s-\x%s]', strtoupper($littleFirst), strtoupper($littleLast)),
        );

        $expression = preg_replace('/^(\\\\x00)+/', '', $expression);
        $expressions[] = $expression;
    }
}

$match = sprintf('/(%s)/x', implode(PHP_EOL . '|', $expressions));
file_put_contents(__DIR__ . '/../src/unicode-patterns.php', sprintf("<?php\nreturn '%s';", $match));
