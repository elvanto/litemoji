<?php

namespace LitEmoji;

class LitEmoji
{
    private static array $shortcodes = [];
    private static array $shortcodeCodepoints = [];
    private static array $shortcodeEntities = [];
    private static array $entityCodepoints = [];
    private static array $excludedShortcodes = [];

    /**
     * Converts all unicode emoji and HTML entities to plaintext shortcodes.
     *
     * @param string $content
     * @return string
     */
    public static function encodeShortcode(string $content): string
    {
        $content = self::entitiesToUnicode($content);
        return self::unicodeToShortcode($content);
    }

    /**
     * Converts all plaintext shortcodes and unicode emoji to HTML entities.
     *
     * @param string $content
     * @return string
     */
    public static function encodeHtml(string $content): string
    {
        $content = self::unicodeToShortcode($content);
        return self::shortcodeToEntities($content);
    }

    /**
     * Converts all plaintext shortcodes and HTML entities to unicode codepoints.
     *
     * @param string $content
     * @return string
     */
    public static function encodeUnicode(string $content): string
    {
        $content = self::shortcodeToUnicode($content);
        return self::entitiesToUnicode($content);
    }

    /**
     * Converts plaintext shortcodes to HTML entities.
     *
     * @param string $content
     * @return string
     */
    public static function shortcodeToUnicode(string $content): string
    {
        $replacements = self::getShortcodeCodepoints();
        return str_replace(array_keys($replacements), $replacements, $content);
    }

    /**
     * Converts HTML entities to unicode codepoints.
     *
     * @param string $content
     * @return string
     */
    public static function entitiesToUnicode(string $content): string
    {
        /* Convert HTML entities to uppercase hexadecimal */
        $content = preg_replace_callback('/\&\#(x?[a-zA-Z0-9]*?)\;/', static function($matches) {
            $code = $matches[1];

            if ($code[0] == 'x') {
                return '&#x' . strtoupper(substr($code, 1)) . ';';
            }

            return '&#x' . strtoupper(dechex($code)) . ';';
        }, $content);

        $replacements = self::getEntityCodepoints();
        return str_replace(array_keys($replacements), $replacements, $content);
    }

    /**
     * Converts unicode codepoints to plaintext shortcodes.
     *
     * @param string $content
     * @return string
     */
    public static function unicodeToShortcode(string $content): string
    {
        $tokenizer = new Tokenizer($content);
        $codepoints = array_flip(self::getShortcodes());
        $replacement = '';

        while ($char = $tokenizer->consume()) {
            $possibleReplacements = [];
            $test = $char;
            $limit = 8;

            do {
                $possibleReplacements[] = $codepoints[$test] ?? null;

                if (!$next = $tokenizer->consume()) {
                    break;
                }

                $test = sprintf('%s-%s', $test, $next);
                $limit--;
            } while ($limit > 0);

            while (count($possibleReplacements)) {
                $tokenizer->rewind();

                if ($shortcode = array_pop($possibleReplacements)) {
                    $replacement .= sprintf(':%s:', $shortcode);

                    continue 2;
                }

            }

            $replacement .= $tokenizer->raw();
        }

        return $replacement;
    }

    /**
     * Converts plain text shortcodes to HTML entities.
     *
     * @param string $content
     * @return string
     */
    public static function shortcodeToEntities(string $content): string
    {
        $replacements = self::getShortcodeEntities();
        return str_replace(array_keys($replacements), $replacements, $content);
    }

    /**
     * Sets a configuration property.
     *
     * @param string $property
     * @param mixed $value
     */
    public static function config(string $property, $value): void
    {
        switch ($property) {
            case 'excludeShortcodes':
                self::$excludedShortcodes = [];

                if (!is_array($value)) {
                    $value = [$value];
                }

                foreach ($value as $code) {
                    if (is_string($code)) {
                        self::$excludedShortcodes[] = $code;
                    }
                }

                // Invalidate shortcode cache
                self::$shortcodes = [];
                break;
        }
    }

    /**
     * Removes all emoji-sequences from string.
     *
     * @param string $source
     * @return string
     */
    public static function removeEmoji(string $source): string
    {
        $content = self::encodeShortcode($source);
        return preg_replace('/:\w+:/', '', $content);
    }

    private static function getShortcodes(): array
    {
        if (!empty(self::$shortcodes)) {
            return self::$shortcodes;
        }

        // Skip excluded shortcodes
        self::$shortcodes = array_filter(require(__DIR__ . '/emoji.php'), static function($code) {
            return !in_array($code, self::$excludedShortcodes);
        }, ARRAY_FILTER_USE_KEY);

        return self::$shortcodes;
    }

    private static function getShortcodeCodepoints(): array
    {
        if (!empty(self::$shortcodeCodepoints)) {
            return self::$shortcodeCodepoints;
        }

        foreach (self::getShortcodes() as $shortcode => $codepoint) {
            $parts = explode('-', $codepoint);
            $codepoint = '';

            foreach ($parts as $part) {
                $codepoint .= mb_convert_encoding(pack('N', hexdec($part)), 'UTF-8', 'UTF-32');
            }

            self::$shortcodeCodepoints[':' . $shortcode . ':'] = $codepoint;
        }

        return self::$shortcodeCodepoints;
    }

    private static function getEntityCodepoints(): array
    {
        if (!empty(self::$entityCodepoints)) {
            return self::$entityCodepoints;
        }

        foreach (self::getShortcodes() as $shortcode => $codepoint) {
            $parts = explode('-', $codepoint);
            $entity = '';
            $codepoint = '';

            foreach ($parts as $part) {
                $entity .= '&#x' . $part . ';';
                $codepoint .= mb_convert_encoding(pack('N', hexdec($part)), 'UTF-8', 'UTF-32');
            }

            self::$entityCodepoints[$entity] = $codepoint;
        }

        return self::$entityCodepoints;
    }

    private static function getShortcodeEntities(): array
    {
        if (!empty(self::$shortcodeEntities)) {
            return self::$shortcodeEntities;
        }

        foreach (self::getShortcodes() as $shortcode => $codepoint) {
            $parts = explode('-', $codepoint);
            self::$shortcodeEntities[':' . $shortcode . ':'] = '';

            foreach ($parts as $part) {
                self::$shortcodeEntities[':' . $shortcode . ':'] .= '&#x' . $part .';';
            }
        }

        return self::$shortcodeEntities;
    }
}
