[![Build Status](https://travis-ci.org/robert-horvath/media-type.svg?branch=develop)](https://travis-ci.org/robert-horvath/media-type)
[![Code Coverage](https://codecov.io/gh/robert-horvath/media-type/branch/develop/graph/badge.svg)](https://codecov.io/gh/robert-horvath/media-type)
[![Latest Stable Version](https://img.shields.io/packagist/v/robert/media-type.svg)](https://packagist.org/packages/robert/media-type)
# Media Type Interface
The MediaType module is a thin wrapper to access the Media Type Specifications and Registration Procedures.
## Example Usage Of MediaType Class
```php
$mt = new RHo\MediaType\MediaType('application', 'vnd.api+json', [ 'version' => '1' ]);
var_dump(
  $mt,                           // string(34) "application/vnd.api+json;version=1"
  $mt->type(),                   // string(11) "application"
  $mt->subType();                // string(12) "vnd.api+json"
  $mt->structuredSyntaxSuffix(); // string(4)  "json"
  $mt->parameter('version');     // string(1)  "1"
  $mt->parameter('q');           // NULL
  $mt->parameterQ();             // int(1)
);
```
## Example Usage Of MediaTypeFactoryInterface Class
In case of regular expression parsing failure a ```RuntimeException``` is raised.
```php
try {
  $mtf = new RHo\MediaType\MediaTypeFactory();
  $mtArray = $mtf->fromString('application/vnd.api+json;version=1,plain/text')
                 ->build();

  foreach($mtArray => $mt) {
    // $mt is a RHo\MediaType\MediaTypeInterface class if success
    // $mt is NULL if parsing of input string failed
} catch (RuntimeException $e) {
  // Regular expression error. E.g.: PREG_RECURSION_LIMIT_ERROR
}
```