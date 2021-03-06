# XValidator
Validate and sanitize user input and other variables.

This library will not only allow you to validate all your user inputs (POST, GET, REQUEST, COOKIE, SESSION) but also sanitize them fast and easy. There are a lot of predefined filters to use and you can create custom validations and sanitizers as well.

### Getting started
Let's say you have a $\_POST array full of userdata sent via a simple HTML webform:

- Simple Input:     Real name (max. 100 chars)
- Advanced Input:   Username (only alphanumeric chars)
- Datepicker:       Birthday (date format)
- Advanced Input:   E-Mail (email format and check if email is already in use)
- Advanced Input:   Password (min. 8 chars, uppercase, lowercase, numbers and special chars for security purposes)
- Select:           Gender (only 'male' or 'female' to choose)
- Simple Checkbox:  Accept the terms of service (a boolean value)
- Multiple Selects: Choose your favorite pies (Apple, Cream, Chocolate) (you have to pick at least a creampie)
- Multiple Selects: Choose your favorite drinks (Coke, Water, Milk) (you have to pick all of them)
- Advanced Input:   Captcha (add two random numbers, numbers only)
- Multiline Input:  Biography (some text with multiple lines)

PHP provides us something like this:
```PHP
$_POST['name'] = "John Doe";
$_POST['username'] = "JohnDoe69";
$_POST['birthday'] = "04/20/1969";
$_POST['email'] = "john@example.com";
$_POST['password'] = "123456";
$_POST['gender'] = "male";
$_POST['accept_tos'] = "true";
$_POST['pies'][] = "Apple";
$_POST['pies'][] = "Chocolate";
$_POST['drink'][] = "Coke";
$_POST['drink'][] = "Water";
$_POST['drink'][] = "Milk";
$_POST['captcha'] = "15";
$_POST['bio'] = "Hello, I am John.

<b>I love ambiguous allusions.</b>";
```

Let's validate this:
```PHP
use XValidator\GlobalSetup;
use XValidator\GlobalValues;
use XValidator\InputException;
use XValidator\From;
use XValidator\Has;

$captcha_result = 15;

$name =       From::post( 'name' )->validate(['maxLength' => 100]);
$username =   From::post( 'username' )->validate(['alphanumericOnly']);
$birthday =   From::post( 'birthday' )->validate(['date' => 'mm\/dd\/yyyy']);
$email =      From::post( 'email' )->validate(['email', function( $val ) { return check_if_email_is_available( $val ); }]);
$password =   From::post( 'password' )->validate(['minLength' => 8, 'mustContainEverything']);
$gender =     From::post( 'gender' )->validate(['inArray' => ['male', 'female']]);
$accept_tos = From::post( 'accept_tos' )->validate(['bool']);
$pies =       From::post( 'pies' )->validate(['inArray' => 'Cream']);
$pies =       From::post( 'drink' )->validate(['equal' => ['Coke', 'Water', 'Milk']]);
$captcha =    From::post( 'captcha' )->validate(['number', function( $val ) use ( $captcha_result ) { return $val == $captcha_result; }]);
$bio =        From::post( 'bio' );
```
Hint: Setting the second parameter of the validate() function _true_ an exception will be thrown if an input is invalid. By setting it _false_ exceptions can also be deactivated for single validations. You can also use the shortcuts v() for validate() and s() for sanitize().

There are different methods to collect user inputs:
```PHP
From::post();     // $_POST
From::get();      // $_GET
From::request(); // $_REQUEST
From::cookie();   // $_COOKIE
From::session();  // $_SESSION
From::file();     // $_FILES
From::json();     // JSON Object
From::raw($val, $name);     // A custom variable. First parameter is the value, second a given name
```
The first parameter has to be the field's name as a string ( $\_POST\['username'\] becomes From::post('username') ).

You can also check if a specific input is available at all:
```PHP
Has::post();     // $_POST
Has::get();      // $_GET
Has::request(); // $_REQUEST
Has::cookie();   // $_COOKIE
Has::session();  // $_SESSION
Has::file();     // $_FILES
```
First parameter checks if the specific input is available, for exampe:

```PHP
Has::post('name');

// Leaving the parameter empty will check if the global POST Array has been set at all:
Has::post();
``` 

How to check what's valid and what's not?
```PHP
$name->isValid();     // Returns true, because "John Doe" has less than 100 chars
$pies->isValid();     // Returns false, because John refused to choose the creampie
```

What other informations does Validator provide?
```PHP
$name->isEmpty();     // Returns true, if there's no value
$name->getLength();   // Returns the length of a value or the count of elements if it's an array as an Integer
$name->getErrors();   // Get a list of errors that occured due to the validation
$name->hasError('maxLength');   // Checks if the maxLength validator threw an Error
$name->isMultiple(); // Returns true, if the value is an array
```

How to get the value?
```PHP
echo $name; // "John Doe"
echo $name->value(); // "John Doe" (mixed)
echo $captcha->asInt(); // 15 (Integer)
echo $accept_tos->asBool(); // true (Boolean)
echo $name->asString(); // "John Doe" (String)
echo $pies->asArray(); // array('Cream', 'Chocolate');
echo $username->asArray('o'); // array('J', 'hnD', 'e69');
```

Let's sanitize the values first:
```PHP
// Every name should be capitalized
$name->sanitize('capitalizeAll')->validate(['maxLength' => 100]);

// Username should always be lowercase and everything but alphanumeric chars should be stripped
$username->sanitize('lowercase', 'alphanumericOnly')->validate(['alphanumericOnly']);

// No html in bio allowed, cut after 30 chars but add line <br> linebreaks
$bio->sanitize('stripTags', function( $val ) { return substr( $val, 0, 30 ); }, 'break' );
```

Let's say all inputs have to be required fields, every field should be trimmed and unnecessary whitespaces should be removed and an exception should be thrown if an input is invalid:
```PHP
GlobalSetup::setValidate(['required']);
GlobalSetup::setSanitize('trim', 'stripMultipleWhitespaces');
GlobalSetup::alwaysThrow(true);
```

... except for birthday:
```PHP
$birthday = From::post( 'birthday' )->sanitize()->validate(['required' => false, 'date' => 'mm\/dd\/yyyy']);
```

To ignore global settings for specific inputs you can use ignoreGlobals(true). Every call of the sanitize() or validate() function after this will ignore the global settings.

I want to know which values are valid and which are invalid:
```PHP
GlobalValues::getAllValids();   // Array with all valid values
GlobalValues::getAllInvalids(); // Array with all invalid values
GlobalValues::getAllErrors();   // Array with all triggered errors
GlobalValues::getResult('name');// Returns true, if the value is valid, returns an Array with the value and the error type if value is invalid
```

There is the possibility to sanitize or validate values via closures. If the value is an array every single element will go trough the closure. If you set the provideArray(true) function, the closure will receive the whole array instead of every single element. Usually all of this conditions has to be true. If you want to check if at least a single condition is true set oneMustMatch(true). If you leave the parameter empty is like setting true.

**Example**

```PHP
$pies->provideArray()->validate([function( $val ) { return true; }]); // $val will contain an array of all selected pies. Otherwise the $val variable would contain every single element of $_POST['pies'].

$pies->oneMustMatch()->validate([function( $val ) { return $val == "Apple"; }]); // This would be valid because the user at least picked the Applepie.
```

Let's make every second entry uppercase
```PHP
$i = 0;
$drinks = From::post( 'drinks' )->s(function( $val ) use ( &$i ) { $i++; return ( $i % 2 == 0 ) ? strtoupper($val) : $val; });
```

### Uploads

You can also handle file uploads:
```PHP
// Only files with jpg or png as extension and image type of image/*. After that upload it to the given directory, and the size has to be less then 100.000 bytes.
$upload = From::file( 'upload' )->validate(['size' => 100000, 'extension' => ['jpg','png'], 'type' => 'image/*'])->upload('/var/www/uploads/');

// Only if at least one file matches the string "file.jpg"
$upload2 = From::file( 'upload2' )->oneMustMatch()->validate([function( $file ) { return $file->getName() == "file.jpg": }])->upload('/var/www/uploads/');
```

An additional parameter can be added to upload() to change the file name, like:

```PHP
$upload->upload('/var/www/uploads/', function( $filename, $extenstion ) { return md5($filename) . "." . $extension });
```

The following will return an array of multiple File Objects (if multiple files have been provided (<input type="file" name="upload[]" multiple>) or a single File Object.
The file object provides multiple functions:

```PHP
$file->getName();       // Returns the complete filename
$file->getBasename();   // Returns the filename without the extension (basename)
$file->getExtension();  // Returns the file extension
$file->getUniversalType(); // Returns the universal Mimetype (for example: image/*, video/*, application/*)
$file->getType();       // Returns the mime type
$file->getTempName();   // tmp_name of the file
$file->getUploadError(); // Returns the error of an file
$file->getSize();       // Returns size in bytes
```


### All available sanitizers

- **trim** (like trim())
- **ltrim** (like ltrim())
- **rtrim** (like rtrim())
- **numberOnly** (removes everything but numbers)
- **letterOnly** (removes everything but chars a-z, A-Z)
- **alphanumericOnly** (removes everything but alphanumeric chars)
- **specialcharOnly** (removes everything but special chars)
- **stripWhitespaces** (removes all whitespaces)
- **stripMultipleWhitespaces** (removes multiple whitespaces that appear in a row and replace it with a single one)
- **slash** (like addslashes())
- **unslash** (like stripslashes())
- **stripTags** (like strip_tags())
- **maskTags** (like htmlspecialchars())
- **capitalize** (capitalizes the first character of a string)
- **capitalizeAll** (capitalizes every word of a string)
- **uppercase** (like strtoupper())
- **lowercase** (like strtolower())
- **break** (like nl2br() without XHTML syntax)
- **int** (like filter_var() with FILTER_SANITIZE_NUMBER_INT)
- **float** (like filter_var() with FILTER_SANITIZE_NUMBER_FLOAT)
- **email** (like filter_var() with FILTER_SANITIZE_EMAIL)
- **url** (like filter_var() with FILTER_SANITIZE_URL)

##### Custom sanitizers
```PHP
// $val contains the value of the Validator
function( $val )
{
  return $val;
}
```
### All available validators

- **equal** (number, string, array) if parameter is an array: checks if arrays contain the same elements otherwise compares simple variables
- **equalKey** (array) same as _equal_ but only with arrays. The keys will be compared
- **unequal** (number, string, array) if parameter is an array: checks if arrays don't contain the same elements otherwise compares simple variables if they are different
- **unequalKey** (array) same as _unequal_ but only with arrays. The keys will be compared
- **required** (number, string, array) checks if an array contains at least one element or a single variable a value with at least one character
- **single** checks if the value is not an array
- **multiple** checks if the value is an array
- **minLength** (string, array) checks if an string has a minimum length
- **maxLength** (string, array) checks if an string has a maximum length
- **min** (number, array) checks if the amount of elements of an array or a number is bigger than the given parameter
- **min** (number, array) checks if the amount of elements of an array or a number is smaller than the given parameter
- **email** (array, string) checks if a single value or all values of an array are a correct e-mail address
- **url** (array, string) checks if a single value or all values of an array are a correct URL
- **ip** (array, string) checks if a single value or all values of an array are a correct ip address
- **bool** (array, string) checks if a single value or all values of an array are a correct boolean value
- **filter** (array, string) checks if a single value or all values of an array are filter_var()-validated (parameter contains the FILTER_VALIDATE_*)
- **date** (array, string) checks if a single value or all values of an array are a correct date (based on the default modell ("yyyy-mm-dd") or based on an own modell which can be provided with the parameter. yyyy = Year with 4 digits, yy = year with 2 digits, mm = month, dd = day. Caution: Special chars like slashes need to be escaped because this validator uses regex and preg_match();
- **time** (array, string) checks if a single value or all values of an array are a correct time (based on the default modell ("hh:ii:ss") or based on an own modell which can be provided with the parameter. hh = hours, ii = minutes, ss = seconds. Caution: Special chars like slashes need to be escaped because this validator uses regex and preg_match()
- **numberOnly** (array, string) checks if a single value or all values of an array are a number
- **letterOnly** (array, string) checks if a single value or all values of an array are a text without numbers or special chars
- **alphanumericOnly** (array, string) checks if a single value or all values of an array are an alphanumeric string
- **specialcharOnly** (array, string) checks if a single value or all values of an array are a specialchar string
- **mustContainUppercase** (array, string) checks if a single value or all values of an array contain at least one uppercase letter
- **mustContainLowercase** (array, string) checks if a single value or all values of an array contain at least one lowercase letter
- **mustContainNumbers** (array, string) checks if a single value or all values of an array contain at least one digit
- **mustContainSpecialchars** (array, string) checks if a single value or all values of an array contain at least one special char
- **mustContainEverything** (array, string) combines _mustContainUppercase_, _mustContainLowercase_, _mustContainNumbers_ and _mustContainSpecialchars_
- **match** (array, string) checks if a single value or all values of an array matching a given Regex pattern (via parameter)
- **inArray** (string, array) the comparison depends on the constellation: if the value to be validated is a string and the parameter is an array, its like in_array(value, parameter). If the value to validate is an array and the parameter is a string its also like in_array(parameter, value). If both, the value and the parameter are arrays in will be checked if at least on element of an value array is available in the parameter array.
- **notInArray** (string, array) same as _inArray_ but vice versa. Checks if something is NOT in an array

##### Custom validators
(must return a bool val)
```PHP
// $val contains the value of the Validator
function( $val )
{
  return strlen( $val ) == 5;
}
```
##### Validators for files
- **maxFiles** checks if the number of files is less then the given parameter
- **size** checks if the filesize of every single file is less then the given parameter
- **type** checks if the filetype of every single file matches the provides filetypes given in the parameter or if it matches an universal mimetype like image/\*, video/\*, ...
- **extension** checks if the file extension of every single file matches the provided file extensions given in the parameter
