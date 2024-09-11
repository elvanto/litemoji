# LitEmoji 🔥

A PHP library simplifying the conversion of unicode, HTML and shortcode emoji.

![Run Tests](https://github.com/elvanto/litemoji/workflows/Run%20Tests/badge.svg)

## Installation

```
$ composer require elvanto/litemoji
```

Alternatively you can clone or download the library files.

## Usage

```php
use LitEmoji\LitEmoji;

echo LitEmoji::encodeShortcode('Baby you light my 🔥! 😃');
// 'Baby you light my :fire:! :smiley:'

echo LitEmoji::encodeHtml('Baby you light my :fire:! :smiley:');
// 'Baby you light my &#x1F525;! &#x1F603;'

echo LitEmoji::encodeUnicode('Baby you light my :fire:! :smiley:');
// 'Baby you light my 🔥! 😃'

echo LitEmoji::removeEmoji('Baby you light my 🔥! 😃!!!');
// 'Baby you light my ! !!!'

```

# Configuration

```php
use LitEmoji\LitEmoji;

// Exclude specific shortcodes when converting from unicode and HTML entities
LitEmoji::config('excludeShortcodes', ['mobile', 'android']);

echo LitEmoji::encodeShortcode('📱');
// ':iphone:'

// Add aliases for custom shortcodes
LitEmoji::config('aliasShortcodes', ['yeah' => 'thumbsup']);
echo LitEmoji::encodeUnicode('Can do :yeah:!');
// 'Can do 👍!'
```

# Encodings

LitEmoji's various functions will do their best to detect the encoding of the
provided text and should work on UTF-8 encoded strings without issue. In cases
where the encoding cannot be detected, UTF-8 is assumed, however a second argument
can be provided to any of the functions to hint the actual encoded of the provided
string.

## Contributing

Pull requests are welcome. New code must be fully unit tested (the existing
test suite can be run with PHPUnit).

## License

[MIT License](LICENSE)
