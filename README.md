# json-logic-php

The goal of this project is to share complex logical expressions between front-end and back-end code.

The format takes inspiration from [function calls in Amazon CloudFormation JSON templates](http://docs.aws.amazon.com/AWSCloudFormation/latest/UserGuide/gettingstarted.templatebasics.html#gettingstarted.templatebasics.mappings).

The same format can also be executed in JavaScript by the library [json-logic-js](https://github.com/jwadhams/json-logic-js/)

## Virtues

  1. **Terse.**
  1. **Consistent.** `{"operator" : ["values" ... ]}`  Always.
  1. **Secure.** We never `eval()`. Rules only have access to data you provide.
  1. **Flexible.** Most operands are 1 line of code.



## Examples

### A note about formatting

This is a PHP interpreter of a format designed to be transmitted and stored JSON.  So it makes sense to conceptualize the rules in JSON.

Expressed in JSON, a JsonLogic rule is always one key, with an array of values.

```json
{"==" : ["apples", "apples"]}
```

I prefer working in PHP with [JSON decoded](http://php.net/manual/en/function.json-decode.php) into array syntax, e.g.
```php
["==" => ["apples", "apples"]]
```

The library will happily accept either:
```php
$rule = '{"==":["apples", "apples"]}';

//Decode the JSON string to an array, and evaluate it.
JWadhams\JsonLogic::apply(json_decode($rule, true));
// true

//Decode the JSON string to an object, and evaluate it.
JWadhams\JsonLogic::apply(json_decode($rule, false));
// true
```


### Simple
```php
JWadhams\JsonLogic::apply(( [ "==" => [1, 1] ] );
// true
```

This is a nice, simple test. Does 1 equal 1?  A few things about the format:

  1. The operator is always in the "key" position. There is only one key per object.
  1. The values are typically an array.
  1. Values can be any valid JSON type. (Strings usually, but also numbers, booleans, etc)

### Compound
Here we're beginning to nest rules. 

```php
JWadhams\JsonLogic::apply((
	[ "and" : [
		[ ">" : [3,1] ],
		[ "<" : [1,3] ]
	] ]
);
// true
```
  
In an infix language (like PHP) this could be written as:

```php
( (3 > 1) and (1 < 3) )
```
    
### Data-Driven

Obviously these rules aren't very interesting if they can only take static literal data. Typically `jsonLogic` will be called with a rule object and a data object. You can use the `var` operator to get attributes of the data object:

```php
JWadhams\JsonLogic::apply(
	[ "var" => ["a"] ], // Rule
	[ a => 1, b => 2 ]   // Data
);
// 1
```

If you like, we support [syntactic sugar](https://en.wikipedia.org/wiki/Syntactic_sugar) on unary operators to skip the array around values:

```php
JWadhams\JsonLogic::apply(
	[ "var" => "a" ],
	[ a => 1, b => 2 ]
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
Sometimes the rule you want to process is "Always" or "Never."  If `jsonLogic` is called with a non-object, non-associative-array, it just returns it.

```php
//Always
JWadhams\JsonLogic::apply(true, $data_will_be_ignored);
// true

//Never
JWadhams\JsonLogic::apply(false, $i_wasnt_even_supposed_to_be_here);
// false
```
    
## Supported Operations

  - `==` 
  - `===` 
  - `!=`
  - `!==`
  - `>`
  - `>=`
  - `<`
  - `<=`
  - `!` - Unary negation
  - `and`
  - `or`
  - `?:` - [ternary](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Conditional_Operator), like `a ? b : c;`
  - `var` - Retrieve data from the provided data object
  - `log` - Logs the first value to `error_log`, then passes it through unmodified.
  
