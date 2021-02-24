# Validator
Validate and sanitize user input and other variables.

This library will not only allow you to validate all your user inputs (POST, GET, REQUEST, COOKIE, SESSION) but also sanitize them fast and easy. There are a lot of predefined filters to use and you can create custom validations and sanitizers as well.

### Getting started
Let's say you have a $\_POST array full of userdata sent via a simple HTML webform:

- Simple Input:     Real name (max. 100 chars)
- Advanced Input:   Username (only alphanumeric chars)
- Datepicker:       Birthday (date format)
- Advanced Input:   E-Mail (email format)
- Advanced Input:   Password (min. 8 chars for security purposes)
- Select:           Gender (only 'male' and 'female' to choose)
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
$_POST['captcha'] = 15;
$_POST['bio'] = "Hello, I am John.

<b>I love ambiguous allusions.</b>";
```

Let's validate this:
```PHP
$name = Validator\Value::post( 'name' )->validate(['limit' => 100]);
$username = Validator\Value::post( 'username' )->validate(['alphanumeric']);
$birthday = Validator\Value::post( 'birthday' )->validate(['date' => 'mm\/dd\/yyyy']);
$email = Validator\Value::post( 'email' )->validate(['email']);
$password = Validator\Value::post( 'password' )->validate(['minLength' => 8]);
$gender = Validator\Value::post( 'gender' )->validate(['inArray' => ['male', 'female']]);
$accept_tos = Validator\Value::post( 'accept_tos' )->validate(['bool']);
$pies = Validator\Value::post( 'pies' )->validate(['inArray' => 'Cream']);
$pies = Validator\Value::post( 'drink' )->validate(['equal' => ['Coke', 'Water', 'Milk']]);
$captcha = Validator\Value::post( 'captcha' )->validate(['numbers', function( $val ) use ( $captcha_result ) { return $val == $captcha_result; }]);
$bio = Validator\Value::post( 'bio' );
```

How to check what's valid and what's not?
```PHP
$name->isValid();     // Returns true, because "John Doe" has less than 100 chars
$pies->isValid();     // Returns false, because John refused to choose the creampie
```

What other informations does Validator provide?
```PHP
$name->isEmpty();     // Returns true, if there's no value
$name->getLength();   // Returns the length of a value as an Integer
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
$name->sanitize('capitalizeAll')->validate(['limit' => 100]);

// Username should always be lowercase
$username->sanitize('lowercase')->validate(['alphanumeric']);

// No html in bio allowed, cut after 30 chars but add line <br> linebreaks
$bio->sanitize('stripTags', function( $val ) { return substr( $val, 0, 30 ); }, 'break' );
```

Let's say all inputs have to be required fields:
```PHP
Validator\GlobalSetup::setValidate('required');
```

I want to know which values are valid and which are invalid:
```PHP
Validator\GlobalValues::getAllValids();   // Array with all valid values
Validator\GlobalValues::getAllInvalids(); // Array with all invalid values
```
