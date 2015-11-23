Recoder
=========

Recoder is a simple shortcode processor that uses Wordpress-like syntax. This library supports multiple formats such as:

```
[shortcode]
[shortcode argument="value"]
[shortcode truevalue argument=simple other="complex value"]
[shortcode]content[/shortcode]
[shortcode argument="value"]content[/shortcode]
```

Requirements
------------

- PHP >= 5.4
- Multibyte string extension

Installation
------------

Composer:

```
composer require onesimus-systems/recoder
```

or in your composer.json:

```
(...)
"require": {
    "onesimus-systems/recoder": "^1.0.0"
}
(...)
```

and run `composer install`.

Usage
-----

### Methods

```php
Recoder Recoder::_construct(string $open = null, string $close = null)
bool Recoder::register(string $shortcode, callable $func)
bool Recoder::registerAlias(string $shortcode, string $aliasedCode)
string Recoder::process(string $text [, mixed $... ])
null Recoder::unregister([string $shortcode = null])
null Recoder::setDelimiters(string $open, string $close = null)
```

### Basic usage

```php
$sc = new Recoder();
$sc->register('name', function(array $options) { return $options['_code']; });
$sc->register('content', function(array $options) { return $options['_content']; });

$parsedText = $sc->process('[name]'); // Return: name
$parsedText = $sc->process('[content]Some content[/content]'); // Return: Some content
```

Shortcodes must be a string and be made up of letters, numbers, hyphens, or underscores. They cannot begin with an underscore. `register` and `registerAlias` will return false for an invalid code, true otherwise. Shortcodes may be overwritten by registering a new function. They can also be unregistered:

```php
$sc->unregister('content'); // Unregister one code
$sc->unregister(); // Unregister all codes
```

### Aliasing Codes

```php
$sc->registerAlias('newCode', 'aliasedCode'); // Now [newCode] will do the samething as [aliasedCode]
```

Aliased codes are resolved at processing time. This means, if the callback for the aliased code changes, the alias will resolve to the new callback instead of the original callback when it was registered.

### Using Arguments

```php
$sc->register('list-args', function(array $options) {
    $r = [];
    foreach($options as $key => $val) {
        if ($key[0] !== '_') {
            $r []= "$key:$val";
        }
    }
    return implode(' & ', $r);
});

$parsedText = $sc->process('[list-args arg1=val1 arg2="Some value"]');
// Return: arg1:val1 & arg2:Some value
```

The options array contains the following library defined keys:

- `_raw` - Raw shortcode including content and ending tag
- `_content` - Text inside a paired shortcode, blank if shortcode is self-closing
- `_offset` - Offset in the text where the shortcode was found
- `_code` - The shortcode name itself
- `_length` - The length of the raw shortcode string

Any other keys are arguments from the shortcode. Keys beginning with an _ are reserved for library use.

### Passing extra arguments to shortcode handlers

```php
$sc->register('code', function(array $options, $someObj) {
    return $someObj->someMethod();
});

$parsedText = $sc->process('[code]', $obj);
// Everything after the first argument is passed to the handlers
```

### Define your own syntax

```php
$sc = new Recoder('{', '}'); // Code syntax is now {code arg=val}
```

Edge Cases
----------

- Unregistered shortcodes will be ignored and left as they are.
- Mismatching closing shortcode (`[code]content[/codex]`) will be ignored, opening tag will be interpreted as self-closing shortcode, eg. `[code]`.
- Overlapping shortcodes (`[code]content[inner][/code]content[/inner]`) are not supported and will be interpreted as self-closing, eg. `[code]content[inner][/code]`, second closing tag will be ignored.
- Nested shortcodes with the same name are also considered overlapping, which means that (assume that shortcode `[c]` returns its content) string `[c]x[c]y[/c]z[/c]` will be interpreted as `xyz[/c]` (first closing tag was matched to first opening tag). This can be solved by aliasing given shortcode handler name, because for example `[c]x[d]y[/d]z[/c]` will be processed correctly.

License
-------

Recoder is licensed under MIT.
