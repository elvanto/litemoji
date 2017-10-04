<?php

$data = json_decode(file_get_contents('../vendor/iamcal/emoji-data/emoji.json'), true);

$emoji_array = array();
foreach ($data as $emoji) {
    foreach ($emoji['short_names'] as $short_name) {
        $emoji_array[ (string) $short_name] = $emoji['unified'];
    }
}

ksort($emoji_array, SORT_NATURAL);

$output = "<?php\nreturn " . var_export($emoji_array, true) . ";";

file_put_contents('src/shortcodes-array.php', $output);