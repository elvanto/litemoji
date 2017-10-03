# LitEmoji 🔥

A PHP library simplifying the conversion of unicode, HTML and shortcode emoji.

## Installation

```
$ composer require elvanto/litemoji
```

Or add to `composer.json`:

```
"require": {
    "elvanto/litemoji": "^1.0.0"
}
```

and then run composer update.

Alternatively you can clone or download the library files.

## Usage

```php
use LitEmoji\LitEmoji;

echo LitEmoji::encodeShortCode('Baby you light my 🔥! 😃');
// 'Baby you light my :fire:! :smiley:'

echo LitEmoji::encodeHtml('Baby you light my :fire:! :smiley:');
// 'Baby you light my &#x1F525;! &#x1F603;'

echo LitEmoji::encodeUnicode('Baby you light my :fire:! :smiley:');
// 'Baby you light my 🔥! 😃'
```

## Contributing

Pull requests are welcome. New code must be fully unit tested (the existing
test suite can be run with PHPUnit).

## License

[MIT License](LICENSE)