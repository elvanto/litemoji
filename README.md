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

echo LitEmoji::replaceEmoji('Baby you light my 🔥! 😃!!!', 'heart');
// 'Baby you light my heart! heart!!!'
```

# Configuration

```php
use LitEmoji\LitEmoji;

// Exclude specific shortcodes when converting from unicode and HTML entities
LitEmoji::config('excludeShortcodes', ['mobile', 'android']);

echo LitEmoji::encodeShortcode('📱');
// ':iphone:'
```

## Contributing

Pull requests are welcome. New code must be fully unit tested (the existing
test suite can be run with PHPUnit).

## License

[MIT License](LICENSE)
