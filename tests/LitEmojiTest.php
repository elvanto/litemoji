<?php

namespace LitEmoji;

use PHPUnit\Framework\TestCase;

class LitEmojiTest extends TestCase
{
    protected function setUp(): void
    {
        LitEmoji::config('excludeShortcodes', []);
        LitEmoji::usePreset('emojibase');
    }

    public function testUnicodeToShortcode()
    {
        $text = LitEmoji::encodeShortcode('My mixtape is 🔥. Made in 🇦🇺!');
        $this->assertEquals('My mixtape is :fire:. Made in :australia:!', $text);
    }

    public function testHtmlToShortcode()
    {
        $text = LitEmoji::encodeShortcode('My mixtape is &#x1F525;. Made in &#x1F1E6;&#x1F1FA;!');
        $this->assertEquals('My mixtape is :fire:. Made in :australia:!', $text);
    }

    public function testShortcodeToHtml()
    {
        $text = LitEmoji::encodeHtml('My mixtape is :fire:. Made in :flag-au:!');
        $this->assertEquals('My mixtape is &#x1F525;. Made in &#x1F1E6;&#x1F1FA;!', $text);
    }

    public function testUnicodeToHtml()
    {
        $text = LitEmoji::encodeHtml('My mixtape is 🔥. Made in 🇦🇺!');
        $this->assertEquals('My mixtape is &#x1F525;. Made in &#x1F1E6;&#x1F1FA;!', $text);
    }

    public function testShortcodeToUnicode()
    {
        $text = LitEmoji::encodeUnicode('My mixtape is :fire:. Made in :flag-au:!');
        $this->assertEquals('My mixtape is 🔥. Made in 🇦🇺!', $text);
    }

    public function testHtmlToUnicode()
    {
        $text = LitEmoji::encodeUnicode('My mixtape is &#x1f525;. Made in &#x1f1e6;&#x1f1fa;!');
        $this->assertEquals('My mixtape is 🔥. Made in 🇦🇺!', $text);
    }

    public function testUnicodeToShortcodeTiming()
    {
        $text = LitEmoji::encodeShortcode(file_get_contents(__DIR__ . '/UnicodeIpsum'));
        $this->assertEquals(file_get_contents(__DIR__ . '/ShortcodeIpsum'), $text);
    }

    public function testRemoveEmoji()
    {
        $text = LitEmoji::removeEmoji('Some text 😊 including emoji 🚀');
        $this->assertEquals('Some text  including emoji ', $text); // NB: smile emoji surrounded with TWO spaces.
    }

    public function testConfigExcludeShortcodes()
    {
        LitEmoji::config('excludeShortcodes', ['mobile', 'android', 'mobile_phone']);
        $this->assertEquals(':iphone:', LitEmoji::encodeShortcode('📱'));
    }

    public function testConfigAliasShortcodes()
    {
        LitEmoji::config('aliasShortcodes', ['thumbs_up' => 'thumbsup', 'yeah' => 'thumbsup']);
        $this->assertEquals('👍', LitEmoji::encodeUnicode(':yeah:'));
    }

    public function testUnicodeMatching()
    {
        $shortcodes = require(__DIR__ . '/../src/emojibase.php');
        $shortcodes = array_flip($shortcodes);

        foreach ($shortcodes as $shortcode) {
            $unicode = LitEmoji::encodeUnicode(':' . $shortcode . ':');
            $matched = LitEmoji::unicodeToShortcode($unicode);

            $this->assertNotEquals($unicode, $matched);
        }
    }

    public function testPreset()
    {
        $this->assertEquals('👍', LitEmoji::encodeUnicode(':thumbsup:'));
        LitEmoji::usePreset('cldr');
        $this->assertEquals('👍', LitEmoji::encodeUnicode(':thumbs_up:'));
    }

    public function testIssue25()
    {
        $text = LitEmoji::encodeShortcode('🚀🛒');
        $this->assertEquals(':rocket::shopping_cart:', $text);
    }

    public function testIssue36()
    {
        $text = LitEmoji::encodeShortcode('🚂—🚃');
        $this->assertEquals(':steam_locomotive:—:railway_car:', $text);
    }

    public function testReplaceEmoji()
    {
        // Test basic emoji replacement
        $text = LitEmoji::replaceEmoji('Some text 😊 including emoji 🚀', '[EMOJI]');
        $this->assertEquals('Some text [EMOJI] including emoji [EMOJI]', $text);
        
        // Test with different replacement text
        $text = LitEmoji::replaceEmoji('Baby you light my 🔥! 😃!!!', 'heart');
        $this->assertEquals('Baby you light my heart! heart!!!', $text);
        
        // Test that non-emoji shortcodes are preserved
        $text = LitEmoji::replaceEmoji('Hello :world: and 😊', 'X');
        $this->assertEquals('Hello :world: and X', $text);
        
        // Test with empty replacement (should work same as removeEmoji)
        $text = LitEmoji::replaceEmoji('Text with 🔥 emoji', '');
        $this->assertEquals('Text with  emoji', $text);
    }

    public function testReplaceEmojiPreservesNonEmojiShortcodes()
    {
        // Test that custom shortcodes that aren't emoji are preserved
        $text = LitEmoji::replaceEmoji('Hello :custom_tag: and :fire: and :another_tag:', '[REPLACED]');
        $this->assertEquals('Hello :custom_tag: and [REPLACED] and :another_tag:', $text);
    }

    public function testRemoveEmojiUsesReplaceEmoji()
    {
        // Test that removeEmoji now uses replaceEmoji internally
        $text1 = LitEmoji::removeEmoji('Some text 😊 including emoji 🚀');
        $text2 = LitEmoji::replaceEmoji('Some text 😊 including emoji 🚀', '');
        $this->assertEquals($text1, $text2);
    }
}
