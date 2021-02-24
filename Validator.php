<?php
/**
 * Validator
 * Created by rawb1t, 2021
 * 
 * Validate and sanitize user input and other variables.
 * 
 */
namespace Validator;

class From
{
	public static function val( $name, $val = null ):Validator
	{
		if( !is_null( $val ) )
		{
			return new Validator( $val, $name );
		}
		
		return new Validator( $name );
	}

	public static function post( $name ):Validator
	{
		return new Validator( $_POST[$name], $name );
	}

	public static function get( $name ):Validator
	{	
		return new Validator( $_GET[$name], $name );
	}

	public static function request( $name ):Validator
	{	
		return new Validator( $_REQUEST[$name], $name );
	}

	public static function cookie( $name ):Validator
	{	
		return new Validator( $_COOKIE[$name], $name );
	}

	public static function session( $name ):Validator
	{	
		return new Validator( $_SESSION[$name], $name );
	}
}

class GlobalSetup
{
	protected static $use_exceptions = false;
	protected static $s_flags = [];
	protected static $v_flags = [];

	public static function alwaysThrow( bool $use_exceptions ):void
	{
		self::$use_exceptions = $use_exceptions;
	}

	public static function setSanitize( ...$s_flags ):void
	{
		self::$s_flags = $s_flags;
	}

	public static function setValidate( array $v_flags ):void
	{
		self::$v_flags = $v_flags;
	}
}

class GlobalValues extends GlobalSetup
{
	private static $valid_values = [];
	private static $invalid_values = [];

	protected static function putValid( ?string $name, $value ):void
	{
		if( !is_null( $name ) )
		{
			self::$valid_values[$name] = $value;
		}
		else
		{
			self::$valid_values[] = $value;
		}
	}

	public static function getValid( $i )
	{
		return self::$valid_values[$i];
	}

	public static function getAllValids():array
	{
		return self::$valid_values;
	}

	protected static function putInvalid( ?string $name, $value ):void
	{
		if( !is_null( $name ) )
		{
			self::$invalid_values[$name] = $value;
		}
		else
		{
			self::$invalid_values[] = $value;
		}
	}

	public static function getInvalid( $i )
	{
		return self::$invalid_values[$i];
	}

	public static function getAllInvalids():array
	{
		return self::$invalid_values;
	}
}

class Validator extends GlobalValues
{
	private $name = null;
	private $val = null;
	private $is_valid = false;
	private $sanitizion_flags = [];
	private $validation_flags = [];
	private $errors = [];

	public function __construct( $val, ?string $name = null )
	{
		$this->name = $name;
		$this->val = $val;
	}

	public function isEmpty():bool
	{
		return is_null( $this->val );
	}

	public function isValid():bool
	{
		return $this->is_valid;
	}

	public function getLength():int
	{
		return is_array( $this->val ) ? count( $this->val ) : strlen( strval( $this->val ) );
	}

	public function hasError( string $validator ):bool 
	{
		return array_search( $validator, $this->errors ) !== false;
	}

	public function getErrors():array
	{
		return $this->errors;
	}

	public function sanitize( ...$sanitizion_flags ):Validator
	{
		if( empty( parent::$s_flags ) &&
			empty( $this->sanitizion_flags ) &&
			empty( $sanitizion_flags ) )
		{
			throw new ValidationException('No sanitizion flags has been set.');
		}

		if( is_null( $sanitizion_flags ) )
		{
			$sanitizion_flags = array_merge( parent::$s_flags, $this->sanitizion_flags );
		}
		else
		{
			$sanitizion_flags = array_merge( parent::$s_flags, $this->sanitizion_flags, $sanitizion_flags );
		}

		foreach( $sanitizion_flags as $flag )
		{
			if( is_callable( $flag ) )
			{
				$this->val = $flag( $this->val );
			}
			else
			{
				$method = 's_' . $flag;

				if( method_exists( $this, $method ) )
				{
					call_user_func_array([ $this, $method ], []);
				}
			}
		}

		return $this;
	}

	public function validate( ?array $validation_flags = null, ?bool $throw = null ):Validator
	{
		$is_valid = true;
		$use_exceptions = parent::$use_exceptions;

		if( !is_null( $throw ) )
		{
			$use_exceptions = $throw;
		}

		if( empty( parent::$v_flags ) &&
			empty( $this->validation_flags ) &&
			empty( $validation_flags ) )
		{
			throw new ValidationException('No validation flags has been set.');
		}

		if( is_null( $validation_flags ) )
		{
			$validation_flags = array_merge( parent::$v_flags, $this->validation_flags );
		}
		else
		{
			$validation_flags = array_merge( parent::$v_flags, $this->validation_flags, $validation_flags );
		}

		$validation_flags = $this->fix_validation_flags( $validation_flags );

		$error = null;

		foreach( $validation_flags as $flag => $f )
		{
			if( is_callable( $f ) && !is_string( $f ) )
			{
				$is_valid = boolval( $f( $this->val ) );

				if( !$is_valid )
				{
					$error = "custom-{$flag}";
					break;
				}
			}
			elseif( is_string( $flag ) )
			{
				$method = 'v_' . $flag;

				if( method_exists( $this, $method ) )
				{
					$is_valid = call_user_func_array([ $this, $method ], [ $f ]);

					if( !$is_valid )
					{
						$error = strtolower( $flag );
						break;
					}
				}
			}
			else
			{
				$method = 'v_' . $f;

				if( method_exists( $this, $method ) )
				{
					$is_valid = call_user_func_array([ $this, $method ], [ true ]);

					if( !$is_valid )
					{
						$error = strtolower( $f );
						break;
					}
				}
			}
		}

		if( !\is_null( $error ) )
		{
			$this->errors[] = $error;
		}

		if( !$is_valid && $use_exceptions )
		{
			throw new InputException( $error, $this->name, $this->val );
		}

		if( $is_valid )
		{
			parent::putValid( $this->name, $this->val );
		}
		else
		{
			parent::putInvalid( $this->name, $this->val );
		}

		$this->is_valid = $is_valid;

		return $this;
	}

	private function s_trim():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = trim( $this->val[$i] );
			}
		}
		else
		{
			$this->val = trim( $this->val );
		}
	}

	private function s_ltrim():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = ltrim( $this->val[$i] );
			}
		}
		else
		{
			$this->val = ltrim( $this->val );
		}
	}

	private function s_rtrim():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = rtrim( $this->val[$i] );
			}
		}
		else
		{
			$this->val = rtrim( $this->val );
		}
	}

	private function s_numberOnly():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = preg_replace( '/\D+/', '', $this->val[$i] );
			}
		}
		else
		{
			$this->val = preg_replace( '/\D+/', '', $this->val );
		}		
	}

	private function s_letterOnly():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = preg_replace( '/([^a-zA-Z]+)/', '', $this->val[$i] );
			}
		}
		else
		{
			$this->val = preg_replace( '/([^a-zA-Z]+)/', '', $this->val );
		}
	}

	private function s_alphanumericOnly():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = preg_replace( '/([^a-zA-Z0-9]+)/', '', $this->val[$i] );
			}
		}
		else
		{
			$this->val = preg_replace( '/([^a-zA-Z0-9]+)/', '', $this->val );
		}
	}

	private function s_specialcharOnly():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = preg_replace( '/([a-zA-Z0-9]+)/', '', $this->val[$i] );
			}
		}
		else
		{
			$this->val = preg_replace( '/([a-zA-Z0-9]+)/', '', $this->val );
		}
	}

	private function s_stripWhitespaces():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = preg_replace( '/\s+/', '', $this->val[$i] );
			}
		}
		else
		{
			$this->val = preg_replace( '/\s+/', '', $this->val );
		}
	}

	private function s_stripMultipleWhitespaces():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = preg_replace( '/\s+/', ' ', $this->val[$i] );
			}
		}
		else
		{
			$this->val = preg_replace( '/\s+/', ' ', $this->val );
		}
	}

	private function s_slash():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = addslashes( $this->val[$i] );
			}
		}
		else
		{
			$this->val = addslashes( $this->val );
		}
	}

	private function s_unslash():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = stripslashes( $this->val[$i] );
			}
		}
		else
		{
			$this->val = addslashes( $this->val );
		}
	}

	private function s_stripTags():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = strip_tags( $this->val[$i] );
			}
		}
		else
		{
			$this->val = strip_tags( $this->val );
		}
	}

	private function s_maskTags():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = htmlspecialchars( $this->val[$i] );
			}
		}
		else
		{
			$this->val = htmlspecialchars( $this->val );
		}
	}

	private function s_capitalize():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = ucfirst( $this->val[$i] );
			}
		}
		else
		{
			$this->val = ucfirst( $this->val );
		}
	}

	private function s_capitalizeAll():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = ucwords( $this->val[$i] );
			}
		}
		else
		{
			$this->val = ucwords( $this->val );
		}
	}

	private function s_uppercase():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = strtoupper( $this->val[$i] );
			}
		}
		else
		{
			$this->val = strtoupper( $this->val );
		}
	}

	private function s_lowercase():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = strtolower( $this->val[$i] );
			}
		}
		else
		{
			$this->val = strtolower( $this->val );
		}
	}

	private function s_break():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = nl2br( $this->val[$i], false );
			}
		}
		else
		{
			$this->val = nl2br( $this->val, false );
		}
	}

	private function s_int():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = filter_var( $this->val[$i], FILTER_SANITIZE_NUMBER_INT );
			}
		}
		else
		{
			$this->val = filter_var( $this->val, FILTER_SANITIZE_NUMBER_INT );
		}
	}

	private function s_float():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = filter_var( $this->val[$i], FILTER_SANITIZE_NUMBER_FLOAT );
			}
		}
		else
		{
			$this->val = filter_var( $this->val, FILTER_SANITIZE_NUMBER_FLOAT );
		}
	}

	private function s_email():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = filter_var( $this->val[$i], FILTER_SANITIZE_EMAIL );
			}
		}
		else
		{
			$this->val = filter_var( $this->val, FILTER_SANITIZE_EMAIL );
		}
	}

	private function s_url():void
	{
		if( \is_array( $this->val ) )
		{
			for( $i = 0; $i < count( $this->val ); $i++ )
			{
				$this->val[$i] = filter_var( $this->val[$i], FILTER_SANITIZE_URL );
			}
		}
		else
		{
			$this->val = filter_var( $this->val, FILTER_SANITIZE_URL );
		}
	}

	private function v_equal( $val )
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( \is_string( $val ) )
		{
			$val = strval( $val );
		}
		elseif( \is_array( $val ) )
		{
			$val = (array) $val;
		}
		elseif( $val === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for equal flag.');
		}

		if( \is_array( $this->val ) && \is_array( $val ) )
		{
			return empty( array_diff( $this->val, $val ) );
		}

		return strval( $this->val ) == $val;
	}

	private function v_equalKey( $val )
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( \is_array( $val ) )
		{
			$val = (array) $val;
		}
		elseif( $val === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for equalKey flag.');
		}

		if( !\is_array( $this->val ) )
		{
			throw new ValidationException('Value is not an array.');
		}

		return empty( array_diff_key( $this->val, $val ) );
	}

	private function v_unequal( $val )
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( \is_string( $val ) )
		{
			$val = strval( $val );
		}
		elseif( \is_array( $val ) )
		{
			$val = (array) $val;
		}
		elseif( $val === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for unequal flag.');
		}

		if( \is_array( $this->val ) && \is_array( $val ) )
		{
			return !empty( array_diff( $this->val, $val ) );
		}

		return strval( $this->val ) != $val;
	}

	private function v_unequalKey( $val )
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( \is_array( $val ) )
		{
			$val = (array) $val;
		}
		elseif( $val === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for unequalKey flag.');
		}

		if( !\is_array( $this->val ) )
		{
			throw new ValidationException('Value is not an array.');
		}

		return !empty( array_diff_key( $this->val, $val ) );
	}

	private function v_required( bool $active ):bool
	{
		if( !$active )
		{
			return true;
		}

		return !empty( $this->val );
	}

	private function v_minLength( int $min ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( \is_int( $min ) )
		{
			$min = intval( $min );
		}
		elseif( $min === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for min flag.');
		}

		return strlen( strval( $this->val ) ) >= $min;
	}

	private function v_maxLength( int $max ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( \is_int( $max ) )
		{
			$max = intval( $max );
		}
		elseif( $max === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for min flag.');
		}

		return strlen( strval( $this->val ) ) <= $max;
	}

	private function v_min( $min ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( \is_int( $min ) )
		{
			$min = intval( $min );
		}
		elseif( \is_float( $min ) )
		{
			$min = floatval( $min );
		}
		elseif( $min === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for min flag.');
		}

		if( is_numeric( $this->val ) )
		{
			if( ((float) $this->val != (int) $this->val) )
			{
				return floatval( $this->val ) >= $min;
			}
			else
			{
				return intval( $this->val ) >= $min;
			}
		}
		elseif( \is_array( $this->val ) )
		{
			return count( $this->val ) >= $min;
		}

		return false;
	}

	private function v_max( $max ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( \is_int( $max ) )
		{
			$max = intval( $max );
		}
		elseif( \is_float( $max ) )
		{
			$max = floatval( $max );
		}
		elseif( $max === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for max flag.');
		}

		if( is_numeric( $this->val ) )
		{
			if( ((float) $this->val != (int) $this->val) )
			{
				return floatval( $this->val ) <= $max;
			}
			else
			{
				return intval( $this->val ) <= $max;
			}
		}
		elseif( \is_array( $this->val ) )
		{
			return count( $this->val ) <= $max;
		}

		return false;
	}

	private function v_email( bool $active ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( !$active )
		{
			return true;
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( \filter_var( $v, FILTER_VALIDATE_EMAIL ) === false )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return \filter_var( $this->val, FILTER_VALIDATE_EMAIL ) !== false;
	}

	private function v_url( bool $active ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( !$active )
		{
			return true;
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( \filter_var( $v, FILTER_VALIDATE_URL ) === false )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return \filter_var( $this->val, FILTER_VALIDATE_URL ) !== false;
	}

	private function v_ip( bool $active ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( !$active )
		{
			return true;
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( \filter_var( $v, FILTER_VALIDATE_IP ) === false )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return \filter_var( $this->val, FILTER_VALIDATE_IP ) !== false;
	}

	private function v_bool( bool $active ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( !$active )
		{
			return true;
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( !\filter_var( $v, FILTER_VALIDATE_BOOLEAN ) )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return \filter_var( $this->val, FILTER_VALIDATE_BOOLEAN );
	}

	private function v_filter( $filter ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( \is_int( $filter ) )
		{
			$filter = intval( $filter );
		}
		elseif( $filter === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for filter flag.');
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( \filter_var( $v, $filter ) === false )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return \filter_var( $this->val, $filter ) !== false;
	}

	private function v_date( $mode ):bool
	{
		$pattern = "yyyy-mm-dd";

		if( empty( $this->val ) )
		{
			return true;
		}

		if( \is_string( $mode ) )
		{
			$pattern = $mode;
		}
		elseif( $mode === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for date flag.');
		}

		$pattern = $this->translate_date_pattern( $pattern );

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				$res = preg_match( "/^" . $pattern . "$/", strval( $v ) );
				if( $res === false )
				{
					throw new ValidationException('Invalid regex');
				}

				if( $res == 0 )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}
		elseif( preg_match( "/^" . $pattern . "$/", strval( $this->val ) ) === false )
		{
			throw new ValidationException('Invalid regex');
		}

		return preg_match( "/^" . $pattern . "$/", strval( $this->val ) ) == 1;
	}

	private function v_time( $mode = null ):bool
	{
		$pattern = "hh:ii:ss";

		if( empty( $this->val ) )
		{
			return true;
		}

		if( \is_string( $mode ) )
		{
			$pattern = $mode;
		}
		elseif( $mode === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for time flag.');
		}

		$pattern = $this->translate_time_pattern( $pattern );

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				$res = preg_match( "/^" . $pattern . "$/", strval( $v ) );
				if( $res === false )
				{
					throw new ValidationException('Invalid regex');
				}

				if( $res == 0 )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}
		elseif( preg_match( "/^" . $pattern . "$/", strval( $this->val ) ) === false )
		{
			throw new ValidationException('Invalid regex');
		}

		return preg_match( "/^" . $pattern . "$/", strval( $this->val ) ) == 1;
	}

	private function v_numberOnly( bool $active ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( !$active )
		{
			return true;
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( preg_match( "/\D+/", strval( $v ) ) == 1 )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return preg_match( "/\D+/", strval( $this->val ) ) == 0;
	}

	private function v_letterOnly( bool $active ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( !$active )
		{
			return true;
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( preg_match( "/([^a-zA-Z]+)/", strval( $v ) ) == 1 )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return preg_match( "/([^a-zA-Z]+)/", strval( $this->val ) ) == 0;
	}

	private function v_alphanumericOnly( bool $active ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( !$active )
		{
			return true;
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( preg_match( "/([^a-zA-Z0-9]+)/", strval( $v ) ) == 1 )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return preg_match( "/([^a-zA-Z0-9]+)/", strval( $this->val ) ) == 0;
	}

	private function v_specialcharOnly( bool $active ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( !$active )
		{
			return true;
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( preg_match( "/([a-zA-Z0-9]+)/", strval( $v ) ) == 1 )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return preg_match( "/([a-zA-Z0-9]+)/", strval( $this->val ) ) == 0;
	}

	private function v_mustContainUppercase( bool $active ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( !$active )
		{
			return true;
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( preg_match( "@[A-Z]@", strval( $v ) ) == 0 )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return preg_match( "@[A-Z]@", strval( $this->val ) ) == 1;
	}

	private function v_mustContainLowercase( bool $active ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( !$active )
		{
			return true;
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( preg_match( "@[a-z]@", strval( $v ) ) == 0 )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return preg_match( "@[a-z]@", strval( $this->val ) ) == 1;
	}

	private function v_mustContainNumbers( bool $active ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( !$active )
		{
			return true;
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( preg_match( "@[0-9]@", strval( $v ) ) == 0 )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return preg_match( "@[0-9]@", strval( $this->val ) ) == 1;
	}

	private function v_mustContainSpecialchars( bool $active ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( !$active )
		{
			return true;
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				if( preg_match( "@[^\w]@", strval( $v ) ) == 0 )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}

		return preg_match( "@[^\w]@", strval( $this->val ) ) == 1;
	}

	private function v_mustContainEverything( bool $active ):bool
	{
		return	$this->v_mustContainUppercase( $active ) &&
				$this->v_mustContainLowercase( $active ) &&
				$this->v_mustContainSpecialchars( $active ) &&
				$this->v_mustContainNumbers( $active );
	}

	private function v_match( $pattern ):bool
	{
		if( empty( $this->val ) )
		{
			return true;
		}

		if( \is_string( $pattern ) )
		{
			$pattern = strval( $pattern );
		}
		elseif( $pattern === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for match flag.');
		}

		if( \is_array( $this->val ) )
		{
			$valid = true;

			foreach( $this->val as $v )
			{
				$res = preg_match( $pattern, strval( $v ) );
				if( $res === false )
				{
					throw new ValidationException('Invalid regex');
				}

				if( $res == 0 )
				{
					$valid = false;
					break;
				}
			}

			return $valid;
		}
		elseif( preg_match( $pattern, strval( $this->val ) ) === false )
		{
			throw new ValidationException('Invalid regex');
		}

		return preg_match( $pattern, strval( $this->val ) ) == 1;
	}

	public function v_inArray( $val ):bool
	{
		$needle = null;
		$haystack = null;

		if( empty( $this->val ) )
		{
			return true;
		}

		if( !\is_array( $val ) && \is_array( $this->val ) )
		{
			$needle = $val;
			$haystack = $this->val;
		}
		elseif( \is_array( $val ) && !\is_array( $this->val ) )
		{
			$needle = $this->val;
			$haystack = $val;
		}
		elseif( \is_array( $val ) && \is_array( $this->val ) )
		{
			return $this->v_equal( $val );
		}
		elseif( $val === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for inArray flag.');
		}

		return \in_array( $needle, $haystack );
	}

	public function v_notInArray( $val ):bool
	{
		$needle = null;
		$haystack = null;

		if( empty( $this->val ) )
		{
			return true;
		}

		if( !\is_array( $val ) && \is_array( $this->val ) )
		{
			$needle = $val;
			$haystack = $this->val;
		}
		elseif( \is_array( $val ) && !\is_array( $this->val ) )
		{
			$needle = $this->val;
			$haystack = $val;
		}
		elseif( \is_array( $val ) && \is_array( $this->val ) )
		{
			return $this->v_unequal( $val );
		}
		elseif( $val === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for inArray flag.');
		}

		return !\in_array( $needle, $haystack );
	}

	public function get()
	{
		return $this->val;
	}

	public function asInt():int
	{
		return intval( $this->val );
	}

	public function asFloat():int
	{
		return floatval( $this->val );
	}

	public function asString():int
	{
		return strval( $this->val );
	}

	public function asBool():int
	{
		return boolval( $this->val );
	}

	private function fix_validation_flags( array $flags ):array
	{
		$fixed_flags = array();

		foreach( $flags as $key => $value )
		{
			if( !\is_string( $key ) && \is_string( $value ) )
			{				
				$fixed_flags[$value] = true;
			}
			else
			{
				$fixed_flags[$key] = $value;
			}
		}

		return $fixed_flags;
	}

	private function translate_date_pattern( string $pattern ):string
	{
		$pattern = \strtolower( $pattern );

		return \str_replace( ['yyyy', 'yy', 'mm', 'dd'], ["([0-9]{4})", "([0-9]{2})", "(0[0-9]|1[0-2])", "(0[1-9]|[1-2][0-9]|3[0-1])"], $pattern );
	}

	private function translate_time_pattern( string $pattern ):string
	{
		$pattern = \strtolower( $pattern );

		return \str_replace( ['hh', 'ii', 'ss'], ["(?:2[0-3]|[01][0-9])", "([0-5][0-9])", "([0-5][0-9])"], $pattern );
	}

	public function __toString()
	{
		return is_array( $this->val ) ? \implode( ',', $this->val ) : ( !is_null( $this->val ) ? strval( $this->val ) : '' );
	}
}

class ValidationException extends \Exception
{
	public function __construct( $e )
	{
		parent::__construct( $e );
	}
}

class InputException extends \Exception
{
	private $error;
	private $field;
	private $value;

	public function __construct( $error, $field, $value )
	{
		parent::__construct( "Field '{$field}' has an invalid value ('{$value}'). Error: {$error}" );

		$this->error = $error;
		$this->field = $field;
		$this->value = $value;
	}

	public function getError()
	{
		return $this->error;
	}

	public function getField()
	{
		return $this->field;
	}
}
