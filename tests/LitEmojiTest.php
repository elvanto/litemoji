<?php

namespace LitEmoji;

class LitEmojiTest extends \PHPUnit_Framework_TestCase
{
    public function testUnicodeToShortcode()
    {
        $text = LitEmoji::encodeShortcode('My mixtape is 🔥. Made in 🇦🇺!');
        $this->assertEquals('My mixtape is :fire:. Made in :flag-au:!', $text);
    }

    public function testShortcodeToHtml()
    {
        $text = LitEmoji::encodeHtml('My mixtape is :fire:. Made in :flag-au:!');
        $this->assertEquals('My mixtape is &#x1F525;. Made in &#x1F1E6;&#x1F1FA;!', $text);
    }

    public function testShortcodeToUnicode()
    {
        $text = LitEmoji::encodeUnicode('My mixtape is :fire:. Made in :flag-au:!');
        $this->assertEquals('My mixtape is 🔥. Made in 🇦🇺!', $text);
    }
}
