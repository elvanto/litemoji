<?php

namespace LitEmoji;

class LitEmoji
{
    private static string $preset = 'emojibase';
    private static array $shortcodes = [];
    private static array $shortcodeCodepoints = [];
    private static array $shortcodeEntities = [];
    private static array $entityCodepoints = [];
    private static array $excludedShortcodes = [];
    private static array $aliasedShortcodes = [];

    /**
     * Switches to a different emoji preset, clearing the shortcode cache.
     *
     * @param string $preset
     * @return void
     */
    public static function usePreset(string $preset)
    {
        if (!file_exists(sprintf('%s/%s.php', __DIR__, $preset))) {
            throw new \InvalidArgumentException('Invalid emoji preset.');
        }

        self::$preset = $preset;
        self::invalidateCache();
    }

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
     * @param string      $content
     * @param string|null $encoding
     * @return string
     */
    public static function encodeUnicode(string $content, string $encoding = null): string
    {
        $content = self::shortcodeToUnicode($content, $encoding);
        return self::entitiesToUnicode($content, $encoding);
    }

    /**
     * Converts plaintext shortcodes to HTML entities.
     *
     * @param string      $content
     * @param string|null $encoding
     * @return string
     */
    public static function shortcodeToUnicode(string $content, string $encoding = null): string
    {
        $replacements = self::getShortcodeCodepoints();

        if (!$encoding) {
            $encoding = mb_detect_encoding($content);
        }

        if ($encoding !== false && $encoding !== 'UTF-8' && $encoding !== 'ASCII') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $replaced = str_replace(array_keys($replacements), $replacements, $content);

        if ($encoding !== false && $encoding !== 'UTF-8' && $encoding !== 'ASCII') {
            $replaced = mb_convert_encoding($replaced, $encoding, 'UTF-8');
        }

        return $replaced;
    }

    /**
     * Converts HTML entities to unicode codepoints.
     *
     * @param string      $content
     * @param string|null $encoding
     * @return string
     */
    public static function entitiesToUnicode(string $content, string $encoding = null): string
    {
        $replacements = self::getEntityCodepoints();

        /* Convert HTML entities to uppercase hexadecimal */
        $content = preg_replace_callback('/\&\#(x?[a-zA-Z0-9]*?)\;/', static function($matches) {
            $code = $matches[1];

            if ($code[0] == 'x') {
                return '&#x' . strtoupper(substr($code, 1)) . ';';
            }

            return '&#x' . strtoupper(dechex($code)) . ';';
        }, $content);

        if (!$encoding) {
            $encoding = mb_detect_encoding($content);
        }

        if ($encoding !== false && $encoding !== 'UTF-8' && $encoding !== 'ASCII') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $replaced = str_replace(array_keys($replacements), $replacements, $content);

        if ($encoding !== false && $encoding !== 'UTF-8' && $encoding !== 'ASCII') {
            $replaced = mb_convert_encoding($replaced, $encoding, 'UTF-8');
        }

        return $replaced;
    }

    /**
     * Converts unicode codepoints to plaintext shortcodes.
     *
     * @param string      $content
     * @param string|null $encoding
     * @return string
     */
    public static function unicodeToShortcode(string $content, string $encoding = null): string
    {
        $codepoints = self::getShortcodeCodepoints();

        if (!$encoding) {
            $encoding = mb_detect_encoding($content);
        }

        if ($encoding !== false && $encoding !== 'UTF-8' && $encoding !== 'ASCII') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $replaced = str_replace(array_values($codepoints), array_keys($codepoints), $content);

        if ($encoding !== false && $encoding !== 'UTF-8' && $encoding !== 'ASCII') {
            $replaced = mb_convert_encoding($replaced, $encoding, 'UTF-8');
        }

        return $replaced;
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

                self::invalidateCache();
                break;
            case 'aliasShortcodes':
                self::$aliasedShortcodes = (array) $value;

                self::invalidateCache();
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
        self::$shortcodes = array_filter(require(sprintf('%s/%s.php', __DIR__, self::$preset)), static function($code) {
            return !in_array($code, self::$excludedShortcodes);
        }, ARRAY_FILTER_USE_KEY);

        // Append shortcode aliases
        foreach (self::$aliasedShortcodes as $alias => $code) {
            if (array_key_exists($code, self::$shortcodes)) {
                self::$shortcodes[$alias] = self::$shortcodes[$code];
            }
        }

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

    /**
     * Invalidates the shortcode cache.
     *
     * @return void
     */
    private static function invalidateCache(): void
    {
        self::$shortcodes = [];
        self::$shortcodeCodepoints = [];
        self::$shortcodeEntities = [];
        self::$entityCodepoints = [];
    }
}
