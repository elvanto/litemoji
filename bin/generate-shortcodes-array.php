#!/usr/bin/env php
<?php

function normalizeShortcode($shortcode)
{
    return str_replace('-', '_', strtolower($shortcode));
}

// Collect available emoji
$data = json_decode(file_get_contents(__DIR__ . '/../vendor/milesj/emojibase/packages/data/en/data.raw.json'), true);
$shortcodes = json_decode(file_get_contents(__DIR__ . '/../vendor/milesj/emojibase/packages/data/en/shortcodes/emojibase.raw.json'), true);

$emojiList = require(__DIR__ . '/../src/emoji.php');
$existingShortcodes = array_map('normalizeShortcode', array_keys($emojiList));

foreach ($data as $emoji) {
    if (
        !isset($shortcodes[$emoji['hexcode']]) ||
        !array_key_exists('group', $emoji) // Excludes regional indicator emoji that mess with flags
    ) {
        continue;
    }

    if (!is_array($shortcodes[$emoji['hexcode']])) {
        $shortcodes[$emoji['hexcode']] = [$shortcodes[$emoji['hexcode']]];
    }

    foreach ($shortcodes[$emoji['hexcode']] as $shortcode) {
        if (in_array(normalizeShortcode($shortcode), $existingShortcodes)) {
            continue;
        }

        $emojiList[(string)$shortcode] = $emoji['hexcode'];
    }
}

// Order by longest codepoint to ensure replacement of ZWJ emoji first
uasort($emojiList, fn ($a, $b) => strlen($b) <=> strlen($a));

// Generate cachable PHP code
$output = [];
foreach ($emojiList as $shortcode => $codepoints) {
    $output[] = sprintf("'%s'=>'%s'", $shortcode, $codepoints);
};

file_put_contents('src/emoji.php', sprintf('<?php return [%s];', implode(',', $output)));
