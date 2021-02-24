# Validator
Validate and sanitize user input and other variables.

This library will not only allow you to validate all your user inputs (POST, GET, REQUEST, COOKIE, SESSION) but also sanitize them fast and easy. There are a lot of predefined filters to use and you can create custom validations and sanitizers as well.

### Getting started
Let's say you have a $\_POST array full of userdata sent via a simple HTML webform:

- Simple Input:     Real name (max. 100 chars)
- Advanced Input:   Username (only alphanumeric chars)
- Datepicker:       Birthday (date format)
- Advanced Input:   E-Mail (email format)
- Advanced Input:   Password (min. 8 chars, uppercase, lowercase, numbers and special chars for security purposes)
- Select:           Gender (only 'male' or 'female' to choose)
- Simple Checkbox:  Accept your terms of service (a boolean value)
- Multiple Selects: Choose your favorite pies (Apple, Cream, Chocolate) (you have to pick at least a creampie)
- Multiple Selects: Choose your favorite drinks (Coke, Water, Milk) (you have to pick all of them)
- Advanced Input:   Captcha (add two random numbers, numbers only)
- Multiline Input:  Biographie (some text with multiple lines)

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
$_POST['pies'][] = "Cream";
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
use Validator\GlobalSetup;
use Validator\GlobalValues;
use Validator\InputException;
use Validator\From;

$captcha_result = 15;

$name = From::post( 'name' )->validate(['maxLength' => 100]);
$username = From::post( 'username' )->validate(['alphanumericOnly']);
$birthday = From::post( 'birthday' )->validate(['date' => 'mm\/dd\/yyyy']);
$email = From::post( 'email' )->validate(['email']);
$password = From::post( 'password' )->validate(['minLength' => 8, 'mustContainEverything']);
$gender = From::post( 'gender' )->validate(['inArray' => ['male', 'female']]);
$accept_tos = From::post( 'accept_tos' )->validate(['bool']);
$pies = From::post( 'pies' )->validate(['inArray' => 'Cream']);
$pies = From::post( 'drink' )->validate(['equal' => ['Coke', 'Water', 'Milk']]);
$captcha = From::post( 'captcha' )->validate(['number', function( $val ) use ( $captcha_result ) { return $val == $captcha_result; }]);
$bio = From::post( 'bio' );
```
Hint: Setting the second parameter of the validate() function _true_ an exception will be thrown if an input is invalid. By setting it _false_ exceptions can also be deactivated for single validations;

How to check what's valid and what's not?
```PHP
$name->isValid();     // Returns true, because "John Doe" has less than 100 chars
$pies->isValid();     // Returns false, because John refused to choose the creampie
```

What other informations does Validator provide?
```PHP
$name->isEmpty();     // Returns true, if there's no value
$name->getLength();   // Returns the length of a value as an Integer
$name->getErrors();   // Get a list of errors that occured due to the validation
$name->hasError('maxlength');   // Checks of the maxLength validator throw an Error
```

How to get the value?
```PHP
echo $name; // John Doe
echo $name->get(); // John Doe (String)
echo $captcha->asInt(); // 15 (Integer)
echo $accept_tos->asBool(); // true (Boolean)
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
$birthday = From::post( 'birthday' )->validate(['required' => false, 'date' => 'mm\/dd\/yyyy']);
```

I want to know which values are valid and which are invalid:
```PHP
GlobalValues::getAllValids();   // Array with all valid values
GlobalValues::getAllInvalids(); // Array with all invalid values
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
- **minLength** (string) checks if an string has a minimum length
- **maxLength** (string) checks if an string has a maximum length
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
- **match** (array, string) checks if a single value or all values of an array matching a given pattern (via parameter)
- **inArray** (array) checks if the parameter is within an array
- **notInArray** (array) checks if the parameter is not within an array

##### Custom sanitizers
(musst return a bool val)
```PHP
// $val contains the value of the Validator
function( $val )
{
  return strlen( $val ) == 5;
}
```
