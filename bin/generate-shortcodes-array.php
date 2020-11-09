#!/usr/bin/env php
<?php

function normalizeShortcode($shortcode) {
    return str_replace('-', '_', strtolower($shortcode));
}

$data = json_decode(file_get_contents(__DIR__ . '/../vendor/milesj/emojibase/packages/data/en/data.raw.json'), true);
$shortcodes = json_decode(file_get_contents(__DIR__ . '/../vendor/milesj/emojibase/packages/data/en/shortcodes/emojibase.raw.json'), true);

$emoji_array = require(__DIR__ . '/../src/shortcodes-array.php');
$existing_shortcodes = array_map('normalizeShortcode', array_keys($emoji_array));

foreach ($data as $emoji) {

    if (!isset($shortcodes[$emoji['hexcode']])) {
        continue;
    }

    if (!is_array($shortcodes[$emoji['hexcode']])) {
        $shortcodes[$emoji['hexcode']] = [$shortcodes[$emoji['hexcode']]];
    }

    foreach ($shortcodes[$emoji['hexcode']] as $shortcode) {

        if (in_array(normalizeShortcode($shortcode), $existing_shortcodes)) {
            continue;
        }

        $emoji_array[ (string) $shortcode] = $emoji['hexcode'];
    }
}

ksort($emoji_array, SORT_NATURAL);
$output = "<?php\nreturn [\n";
foreach ($emoji_array as $shortcode => $codepoints) {
    $output .= "  '$shortcode' => '$codepoints',\n";
};
$output .= '];';
file_put_contents('src/shortcodes-array.php', $output);
