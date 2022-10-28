<?php

namespace LitEmoji;

class LitEmoji
{
    private static $regex = null;
    private static $shortcodes = [];
    private static $shortcodeCodepoints = [];
    private static $shortcodeEntities = [];
    private static $entityCodepoints = [];
    private static $excludedShortcodes = [];

    /**
     * Converts all unicode emoji and HTML entities to plaintext shortcodes.
     *
     * @param string $content
     * @return string
     */
    public static function encodeShortcode(string $content): string
    {
        $content = self::entitiesToUnicode($content);
        $content = self::unicodeToShortcode($content);

        return $content;
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
        $content = self::shortcodeToEntities($content);

        return $content;
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
        $content = self::entitiesToUnicode($content);

        return $content;
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
        $replacement = '';
        $encoding = mb_detect_encoding($content);
        $codepoints = array_flip(self::getShortcodes());

        /* Break content along codepoint boundaries */
        $parts = preg_split(
            self::getRegex(),
            $content,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        /* Reconstruct content using shortcodes */
        $sequence = [];
        foreach ($parts as $offset => $part) {
            if (preg_match(self::getRegex(), $part)) {
                $part = mb_convert_encoding($part, 'UTF-32', $encoding);
                $words = unpack('N*', $part);
                $codepoint = sprintf('%X', reset($words));

                $sequence[] = $codepoint;

                if (isset($codepoints[$codepoint])) {
                    $replacement .= ":$codepoints[$codepoint]:";
                    $sequence = [];
                } else {
                    /* Check multi-codepoint sequence */
                    $multi = implode('-', $sequence);

                    if (isset($codepoints[$multi])) {
                        $replacement .= ":$codepoints[$multi]:";
                        $sequence = [];
                    }
                }
            } else {
                $replacement .= $part;
            }
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
        $content = preg_replace('/\:\w+\:/', '', $content);
        return $content;
    }

    private static function getRegex()
    {
        if (!is_null(self::$regex)) {
            return self::$regex;
        }

        self::$regex = require(__DIR__ . '/unicode-patterns.php');
        return self::$regex;
    }

    private static function getShortcodes()
    {
        if (!empty(self::$shortcodes)) {
            return self::$shortcodes;
        }

        // Skip excluded shortcodes
        self::$shortcodes = array_filter(require(__DIR__ . '/shortcodes-array.php'), static function($code) {
            return !in_array($code, self::$excludedShortcodes);
        }, ARRAY_FILTER_USE_KEY);

        return self::$shortcodes;
    }

    private static function getShortcodeCodepoints()
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

    private static function getEntityCodepoints()
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

    private static function getShortcodeEntities()
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
