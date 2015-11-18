# json-logic-php

This parser accepts [JsonLogic](http://jsonlogic.com) rules and executes them in PHP.

The JsonLogic format is designed to allow you to share rules (logic) between front-end and back-end code (regardless of language difference), even to store logic along with a record in a database.  JsonLogic is documented extensively at [JsonLogic.com](http://jsonlogic.com), including examples of every [supported operation](http://jsonlogic.com/operations.html) and a place to [try out rules in your browser](http://jsonlogic.com/play.html).

The same format can also be executed in JavaScript by the library [json-logic-js](https://github.com/jwadhams/json-logic-js/)

## Examples

### A note about types

This is a PHP interpreter of a format designed to be transmitted and stored as JSON.  So it makes sense to conceptualize the rules in JSON.

Expressed in JSON, a JsonLogic rule is always one key, with an array of values.

```json
{"==" : ["apples", "apples"]}
```

PHP has a way to express associative arrays as literals, and no object equivalent, so all these examples are written as if JsonLogic rules were decoded with  [`json_decode`'s `$assoc` parameter set true](http://php.net/manual/en/function.json-decode.php), e.g.
```php
json_decode('{"==" : ["apples", "apples"]}', true);
// ["==" => ["apples", "apples"]]
```

The library will happily accept either associative arrays or objects:
```php
$rule = '{"==":["apples", "apples"]}';

//Decode the JSON string to an array, and evaluate it.
JWadhams\JsonLogic::apply( json_decode($rule, true) );
// true

//Decode the JSON string to an object, and evaluate it.
JWadhams\JsonLogic::apply( json_decode($rule, false) );
// true
```


### Simple
```php
JWadhams\JsonLogic::apply( [ "==" => [1, 1] ] );
// true
```

This is a simple test, equivalent to `1 == 1`.  A few things about the format:

  1. The operator is always in the "key" position. There is only one key per JsonLogic rule.
  1. The values are typically an array.
  1. Each value can be a string, number, boolean, array, or null

### Compound
Here we're beginning to nest rules. 

```php
JWadhams\JsonLogic::apply(
	[ "and" => [
		[ ">" => [3,1] ],
		[ "<" => [1,3] ]
	] ]
);
// true
```
  
In an infix language (like PHP) this could be written as:

```php
( (3 > 1) and (1 < 3) )
```
    
### Data-Driven

Obviously these rules aren't very interesting if they can only take static literal data. Typically `JsonLogic::apply` will be called with a rule object and a data object. You can use the `var` operator to get attributes of the data object:

```php
JWadhams\JsonLogic::apply(
	[ "var" => ["a"] ], // Rule
	[ "a" => 1, "b" => 2 ]   // Data
);
// 1
```

If you like, we support [syntactic sugar](https://en.wikipedia.org/wiki/Syntactic_sugar) on unary operators to skip the array around values:

```php
JWadhams\JsonLogic::apply(
	[ "var" => "a" ],
	[ "a" => 1, "b" => 2 ]
);
// 1
```

You can also use the `var` operator to access an array by numeric index:

```php
JWadhams\JsonLogic::apply(
	[ "var" => 1 ],
	[ "apple", "banana", "carrot" ]
);
// "banana"
```

Here's a complex rule that mixes literals and data. The pie isn't ready to eat unless it's cooler than 110 degrees, *and* filled with apples.

```php
$rules = [ "and" => [
	[ "<" => [ [ "var" => "temp" ], 110 ] ],
	[ "==" => [ [ "var" => "pie.filling" ], "apple" ] ]
] ];

$data = [ "temp" => 100, "pie" => [ "filling" => "apple" ] ];

JWadhams\JsonLogic::apply($rules, $data);
// true
```

### Always and Never
Sometimes the rule you want to process is "Always" or "Never."  If the first parameter passed to `JsonLogic::apply` is a non-object, non-associative-array, it is returned immediately.

```php
//Always
JWadhams\JsonLogic::apply(true, $data_will_be_ignored);
// true

//Never
JWadhams\JsonLogic::apply(false, $i_wasnt_even_supposed_to_be_here);
// false
```
    
## Installation

The best way to install this library is via [Composer](https://getcomposer.org/):

```bash
composer require jwadhams/json-logic-php
```

If that doesn't suit you, and you want to manage updates yourself, the entire library is self-contained in `src/JWadhams/JsonLogic.php` and you can download it straight into your project as you see fit.

```bash
curl -O https://raw.githubusercontent.com/jwadhams/json-logic-php/master/src/JWadhams/JsonLogic.php
```
