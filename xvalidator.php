<?php
/**
 * XValidator
 * Created by rawb1t, 2021
 * 
 * Validate and sanitize user input and other variables.
 * 
 */
namespace XValidator;

class Has
{
	public static function post( ...$data )
	{
		if( empty( $_POST ) )
		{
			return false;
		}
		elseif( empty( $data ) )
		{
			return true;
		}

		foreach( $data as $d )
		{
			if( !isset( $_POST[$d] ) )
			{
				return false;
			}
		}

		return true;
	}

	public static function get( ...$data )
	{
		if( empty( $_GET ) )
		{
			return false;
		}
		elseif( empty( $data ) )
		{
			return true;
		}

		foreach( $data as $d )
		{
			if( !isset( $_GET[$d] ) )
			{
				return false;
			}
		}

		return true;
	}

	public static function request( ...$data )
	{
		if( empty( $_REQUEST ) )
		{
			return false;
		}
		elseif( empty( $data ) )
		{
			return true;
		}

		foreach( $data as $d )
		{
			if( !isset( $_REQUEST[$d] ) )
			{
				return false;
			}
		}

		return true;
	}

	public static function cookie( ...$data )
	{
		if( empty( $_COOKIE ) )
		{
			return false;
		}
		elseif( empty( $data ) )
		{
			return true;
		}

		foreach( $data as $d )
		{
			if( !isset( $_COOKIE[$d] ) )
			{
				return false;
			}
		}

		return true;
	}

	public static function session( ...$data )
	{
		if( empty( $_SESSION ) )
		{
			return false;
		}
		elseif( empty( $data ) )
		{
			return true;
		}

		foreach( $data as $d )
		{
			if( !isset( $_SESSION[$d] ) )
			{
				return false;
			}
		}

		return true;
	}

	public static function file( ...$data )
	{
		if( empty( $_FILES ) )
		{
			return false;
		}
		elseif( empty( $data ) )
		{
			return true;
		}

		foreach( $data as $d )
		{
			if( empty( $_FILES[$d] ) )
			{
				return false;
			}
		}

		return true;
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
	private static $all_errors = [];

	protected static function putValidFile( string $name, int $i, $value ):void
	{
		if( !empty( $value ) )
		{
			self::$valid_values[$name][$i] = $value;
		}
	}

	protected static function putValid( string $name, $value ):void
	{
		if( !empty( $value ) )
		{
			self::$valid_values[$name] = $value;
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

	protected static function putInvalidFile( string $name, int $i, $value ):void
	{
		if( !empty( $value ) )
		{
			self::$invalid_values[$name][$i] = $value;
		}
	}

	protected static function putInvalid( string $name, $value ):void
	{
		if( !empty( $value ) )
		{
			self::$invalid_values[$name] = $value;
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

	public static function putErrorFile( string $error, string $name, int $i )
	{
		if( !empty( $error ) )
		{
			self::$all_errors[$name][$i] = $error;
		}
	}

	public static function putError( string $error, string $name )
	{
		if( !empty( $error ) )
		{
			self::$all_errors[$name] = $error;
		}
	}

	public static function getError( $i )
	{
		return self::$all_errors[$i];
	}

	public static function getAllErrors():array
	{
		return self::$all_errors;
	}

	public static function getResult( $i )
	{
		if( isset( self::$valid_values[$i] ) )
		{
			return true;
		}

		return ['value' => self::$invalid_values[$i], 'error' => self::$all_errors[$i]];
	}
}

class From extends GlobalValues
{
	public static function raw( $val, $name ):Validator
	{
		return new Validator( $val, $name );
	}

	public static function json( $name ):Validator
	{
		$input = file_get_contents('php://input');
		$_JSON = json_decode( $input, true );

		return new Validator( $_JSON[$name], $name );
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

	public static function file( $name ):FileValidator
	{
		$files = self::rearrange_files( $_FILES[$name] );
		return new FileValidator( $files, $name );
	}

	public static function rearrange_files( ?array $file_post )
	{
		$files = [];

		if( \is_array( $file_post['name'] ) )
		{
			$file_ary = [];
			$file_count = count( $file_post['name'] );
			$file_keys = array_keys( $file_post );
		
			for( $i = 0; $i < $file_count; $i++ )
			{
				foreach( $file_keys as $key )
				{
					$file_ary[$i][$key] = $file_post[$key][$i];
				}
			}

			foreach( $file_ary as $file )
			{
				$files[] = new File( $file );
			}
		}
		else
		{
			return !\is_null( $file_post ) ? [ new File( $file_post ) ] : [];
		}
	
		return array_filter( $files );
	}

	protected static function fix_validation_flags( array $flags ):array
	{
		$fixed_flags = array();

		$flags = array_filter( $flags );

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

	protected function is_closure( $c )
	{
		return $c instanceof \Closure;
	}
}

class FileValidator extends From
{
	private $name = null;
	private $files = null;
	private $ignore_global = false;
	private $provide_array = false;
	private $one_must_match = false;
	private $valid_files = [];
	private $invalid_files = [];
	private $errors = [];

	protected function __construct( array $files, string $name )
	{
		$this->name = $name;
		$this->files = array_filter( $files );
	}

	public function isEmpty():bool
	{
		return empty( $this->files );
	}

	public function isMultiple():bool
	{
		return count( $this->files ) > 1;
	}

	public function isValid():bool
	{
		return !empty( $this->valid_files );
	}

	public function getValidFiles()
	{
		return count( $this->valid_files ) == 1 ? $this->valid_files[0] : $this->valid_files;
	}

	public function getInvalidFiles()
	{
		return count( $this->invalid_files ) == 1 ? $this->invalid_files[0] : $this->invalid_files;
	}

	public function hasError( string $validator ):bool 
	{
		return array_search( $validator, $this->errors ) !== false;
	}

	public function getErrors():array
	{
		return $this->errors;
	}

	public function setIgnoreGlobal( bool $ignore_global = true ):FileValidator
	{
		$this->ignore_global = $ignore_global;
		return $this;
	}

	public function provideArray( bool $provide_array = true ):FileValidator
	{
		$this->provide_array = $provide_array;
		return $this;
	}

	public function oneMustMatch( bool $one_must_match = true ):FileValidator
	{
		$this->one_must_match = $one_must_match;
		return $this;
	}

	public function upload( string $path, ?\Closure $cl = null ):bool
	{
		$all_uploaded = true;

		foreach( $this->valid_files as $file )
		{
			if( !\is_null( $cl ) && parent::is_closure( $cl ) )
			{
				$name = $cl( $file->getName(), $file->getExtension() );
			}

			$uploaded = \move_uploaded_file( $file->getTempName(), $path . $name );
			$file->setUploaded( $uploaded );

			if( !$uploaded )
			{
				$all_uploaded = false;
			}
		}

		return $all_uploaded;
	}

	public function v( ?array $validation_flags = null, ?bool $throw = null ):FileValidator
	{
		return $this->validate( $validation_flags, $throw );
	}

	public function validate( ?array $validation_flags = null, ?bool $throw = null ):FileValidator
	{
		$use_exceptions = parent::$use_exceptions;

		if( !\is_null( $throw ) )
		{
			$use_exceptions = $throw;
		}

		if( !$this->ignore_global )
		{
			if( empty( parent::$v_flags ) &&
				empty( $validation_flags ) )
			{
				throw new ValidationException('No validation flags has been set.');
			}

			if( \is_null( $validation_flags ) )
			{
				$validation_flags = parent::$v_flags;
			}
			else
			{
				$validation_flags = array_merge( parent::$v_flags, $validation_flags );
			}
		}
		else
		{
			if( empty( $validation_flags ) )
			{
				throw new ValidationException('No validation flags has been set.');
			}
		}

		$validation_flags = parent::fix_validation_flags( $validation_flags );

		foreach( $this->files as $i => $file )
		{
			$error = null;
			$is_valid = $this->upload_error( $file );

			if( !$is_valid )
			{
				$error = 'uploaderror';
			}

			foreach( $validation_flags as $flag => $f )
			{
				if( parent::is_closure( $f ) )
				{
					if( $this->provide_array )
					{
						$is_valid = boolval( $f( $this->files ) );

						if( !$is_valid )
						{
							$error = "custom-{$flag}";
							break;
						}
					}
					else
					{
						if( $this->one_must_match )
						{
							$is_valid = boolval( $f( $file ) );

							if( !$is_valid )
							{
								$error = "custom-{$flag}";
								break;
							}
						}
						else
						{
							$new_valid = true;

							foreach( $this->files as $fi )
							{
								$new_valid = boolval( $f( $fi ) );

								if( !$new_valid )
								{
									$error = "custom-{$flag}";
									break;
								}
							}

							$is_valid = $new_valid;
						}
					}
				}
				else
				{
					$method = 'v_' . $flag;
	
					if( method_exists( $this, $method ) )
					{
						$is_valid = call_user_func_array([ $this, $method ], [ $file, $f ]);
	
						if( !$is_valid )
						{
							$error = strtolower( $flag );
							break;
						}
					}
				}
			}

			$file->setFileValid( $is_valid );

			if( !\is_null( $error ) )
			{
				$this->errors[] = $error;
				parent::putErrorFile( $error, $this->name, $i );
			}

			if( !$is_valid && $use_exceptions )
			{
				throw new InputException( $error, $this->name . "{$i}", $file );
			}
	
			if( $is_valid )
			{
				$this->valid_files[] = $file;
				parent::putValidFile( $this->name, $i, $file );
			}
			else
			{
				$this->invalid_files[] = $file;
				parent::putInvalidFile( $this->name, $i, $file );
			}
		}

		return $this;
	}

	private function upload_error( File $file )
	{
		if( $file->getUploadError() != UPLOAD_ERR_OK )
		{
			$this->upload_errors[] = $file;
			return false;
		}

		return true;
	}

	private function v_maxFiles( File $file, $max_files )
	{
		if( empty( $this->files ) )
		{
			return true;
		}

		if( \is_int( $max_files ) )
		{
			$max_files = intval( $max_files );
		}
		elseif( $max_files === false )
		{
			return true;
		}

		return count( $this->files ) <= $max_files;
	}

	private function v_size( File $file, $max_size )
	{
		if( empty( $this->files ) )
		{
			return true;
		}

		if( \is_int( $max_size ) )
		{
			$max_size = intval( $max_size );
		}
		elseif( $max_size === false )
		{
			return true;
		}

		return $file->getSize() <= $max_size;
	}

	private function v_type( File $file, $types )
	{
		if( empty( $this->files ) )
		{
			return true;
		}

		if( !\is_array( $types ) )
		{
			$types = [$types];
		}
		elseif( $types === false )
		{
			return true;
		}

		if( !\in_array( $file->getType(), $types ) && !\in_array( $file->getUniversalType(), $types ) )
		{
			return false;
		}

		return true;
	}

	private function v_extension( File $file, $ext )
	{
		if( empty( $this->files ) )
		{
			return true;
		}

		if( !\is_array( $ext ) )
		{
			$ext = [$ext];
		}
		elseif( $ext === false )
		{
			return true;
		}

		if( !\in_array( $file->getExtension(), $ext ) )
		{
			return false;
		}

		return true;
	}
}

class Validator extends From
{
	private $name = null;
	private $val = null;
	private $is_valid = false;
	private $ignore_global = false;
	private $provide_array = false;
	private $one_must_match = false;
	private $errors = [];

	protected function __construct( $val, string $name )
	{
		$this->name = $name;
		$this->val = \is_array( $val ) && count( $val ) == 1 ? $val[0] : $val;
	}

	public function isEmpty():bool
	{
		return \is_null( $this->val );
	}

	public function isValid():bool
	{
		return $this->is_valid;
	}

	public function isMultiple():bool
	{
		return \is_array( $this->val );
	}

	public function getLength():int
	{
		return \is_array( $this->val ) ? count( $this->val ) : strlen( strval( $this->val ) );
	}

	public function hasError( string $validator ):bool 
	{
		return array_search( $validator, $this->errors ) !== false;
	}

	public function getErrors():array
	{
		return $this->errors;
	}

	public function ignoreGlobals( bool $ignore_global = true ):Validator
	{
		$this->ignore_global = $ignore_global;
		return $this;
	}

	public function provideArray( bool $provide_array = true ):Validator
	{
		$this->provide_array = $provide_array;
		return $this;
	}

	public function oneMustMatch( bool $one_must_match = true ):Validator
	{
		$this->one_must_match = $one_must_match;
		return $this;
	}

	public function s( ...$sanitizion_flags ):Validator
	{
		return call_user_func_array([ $this, 'sanitize' ], $sanitizion_flags);
	}

	public function sanitize( ...$sanitizion_flags ):Validator
	{
		if( !$this->ignore_global )
		{
			if( empty( parent::$s_flags ) &&
				empty( $sanitizion_flags ) )
			{
				throw new ValidationException('No sanitizion flags has been set.');
			}

			if( \is_null( $sanitizion_flags ) )
			{
				$sanitizion_flags = parent::$s_flags;
			}
			else
			{
				$sanitizion_flags = array_merge( parent::$s_flags, $sanitizion_flags );
			}
		}
		else
		{
			if( empty( $sanitizion_flags ) )
			{
				throw new ValidationException('No sanitizion flags has been set.');
			}
		}

		$sanitizion_flags = array_filter( $sanitizion_flags );

		foreach( $sanitizion_flags as $flag )
		{
			if( parent::is_closure( $flag ) )
			{
				if( !\is_array( $this->val ) ||
					( \is_array( $this->val ) && $this->provide_array ) )
				{
					$this->val = $flag( $this->val );
				}
				else
				{
					$new_val = [];

					foreach( $this->val as $v )
					{
						$new_val[] = $flag( $v );
					}

					$this->val = $new_val;
				}
			}
			elseif( is_string( $flag ) )
			{
				$method = 's_' . $flag;

				if( method_exists( $this, $method ) )
				{
					call_user_func_array([ $this, $method ], []);
				}
			}
			else
			{
				throw new ValidationException('Flag has to be a string or a closure.');
			}
		}

		return $this;
	}

	public function v( ?array $validation_flags = null, ?bool $throw = null ):Validator
	{
		return $this->validate( $validation_flags, $throw );
	}

	public function validate( ?array $validation_flags = null, ?bool $throw = null ):Validator
	{
		$is_valid = true;
		$use_exceptions = parent::$use_exceptions;

		if( !\is_null( $throw ) )
		{
			$use_exceptions = $throw;
		}

		if( !$this->ignore_global )
		{
			if( empty( parent::$v_flags ) &&
				empty( $validation_flags ) )
			{
				throw new ValidationException('No validation flags has been set.');
			}

			if( \is_null( $validation_flags ) )
			{
				$validation_flags = parent::$v_flags;
			}
			else
			{
				$validation_flags = array_merge( parent::$v_flags, $validation_flags );
			}
		}
		else
		{
			if( empty( $validation_flags ) )
			{
				throw new ValidationException('No validation flags has been set.');
			}
		}

		$validation_flags = parent::fix_validation_flags( $validation_flags );

		$error = null;

		foreach( $validation_flags as $flag => $f )
		{
			if( parent::is_closure( $f ) )
			{
				if( !\is_array( $this->val ) ||
					\is_array( $this->val ) && $this->provide_array )
				{
					$is_valid = boolval( $f( $this->val ) );

					if( !$is_valid )
					{
						$error = "custom-{$flag}";
						break;
					}
				}
				else
				{
					if( $this->one_must_match )
					{
						$new_valid = false;
						foreach( $this->val as $v )
						{
							if( boolval( $f( $v ) ) )
							{
								$new_valid = true;
								break;
							}
						}

						if( !$new_valid )
						{
							$error = "custom-{$flag}";
						}

						$is_valid = $new_valid;
					}
					else
					{
						foreach( $this->val as $v )
						{
							$is_valid = boolval( $f( $v ) );

							if( !$is_valid )
							{
								$error = "custom-{$flag}";
								break;
							}
						}
					}
				}
			}
			else
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
		}

		if( !\is_null( $error ) )
		{
			$this->errors[] = $error;
			parent::putError( $error, $this->name );
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

	private function v_single( bool $active ):bool
	{
		if( !$active )
		{
			return true;
		}

		return !\is_array( $this->val );
	}

	private function v_multiple( bool $active ):bool
	{
		if( !$active )
		{
			return true;
		}

		return \is_array( $this->val );
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
			throw new ValidationException('Illegal value for minLength flag.');
		}

		if( \is_array( $this->val ) )
		{
			foreach( $this->val as $v )
			{
				if( strlen( strval( $v ) ) < $min )
				{
					return false;
				}
			}

			return true;
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
			throw new ValidationException('Illegal value for maxLength flag.');
		}

		if( \is_array( $this->val ) )
		{
			foreach( $this->val as $v )
			{
				if( strlen( strval( $v ) ) > $max )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( \filter_var( $v, FILTER_VALIDATE_EMAIL ) === false )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( \filter_var( $v, FILTER_VALIDATE_URL ) === false )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( \filter_var( $v, FILTER_VALIDATE_IP ) === false )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( !\filter_var( $v, FILTER_VALIDATE_BOOLEAN ) )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( \filter_var( $v, $filter ) === false )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				$res = preg_match( "/^" . $pattern . "$/", strval( $v ) );
				if( $res === false )
				{
					throw new ValidationException('Invalid regex');
				}

				if( $res == 0 )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				$res = preg_match( "/^" . $pattern . "$/", strval( $v ) );
				if( $res === false )
				{
					throw new ValidationException('Invalid regex');
				}

				if( $res == 0 )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( preg_match( "/\D+/", strval( $v ) ) == 1 )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( preg_match( "/([^a-zA-Z]+)/", strval( $v ) ) == 1 )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( preg_match( "/([^a-zA-Z0-9]+)/", strval( $v ) ) == 1 )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( preg_match( "/([a-zA-Z0-9]+)/", strval( $v ) ) == 1 )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( preg_match( "@[A-Z]@", strval( $v ) ) == 0 )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( preg_match( "@[a-z]@", strval( $v ) ) == 0 )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( preg_match( "@[0-9]@", strval( $v ) ) == 0 )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( preg_match( "@[^\w]@", strval( $v ) ) == 0 )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				$res = preg_match( $pattern, strval( $v ) );
				if( $res === false )
				{
					throw new ValidationException('Invalid regex');
				}

				if( $res == 0 )
				{
					return false;
				}
			}

			return true;
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
			foreach( $this->val as $v )
			{
				if( in_array( $v, $val ) )
				{
					return true;
				}
			}

			return false;
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
			foreach( $this->val as $v )
			{
				if( !in_array( $v, $val ) )
				{
					return true;
				}
			}

			return false;
		}
		elseif( $val === false )
		{
			return true;
		}
		else
		{
			throw new ValidationException('Illegal value for notInArray flag.');
		}

		return !\in_array( $needle, $haystack );
	}

	public function value( ?int $index = null )
	{
		$val = $this->val;

		if( \is_array( $val ) )
		{
			if( !\is_null( $index ) )
			{
				if( !isset( $val[$index] ) )
				{
					throw new ValidationException("Array index {$index} does not exist.");
				}

				return $val[$index];
			}
			elseif( count( $val ) == 1 )
			{
				return $val[0];
			}
		}

		return $val;
	}

	public function asInt( ?int $index = null ):int
	{
		$val = $this->val;

		if( \is_array( $val ) )
		{
			if( !\is_null( $index ) )
			{
				if( !isset( $val[$index] ) )
				{
					throw new ValidationException("Array index {$index} does not exist.");
				}

				return intval( $val[$index] );
			}
			elseif( count( $val ) >= 1 )
			{
				return intval( $val[0] );
			}
		}

		return intval( $val );
	}

	public function asFloat( ?int $index = null ):float
	{
		$val = $this->val;

		if( \is_array( $val ) )
		{
			if( !\is_null( $index ) )
			{
				if( !isset( $val[$index] ) )
				{
					throw new ValidationException("Array index {$index} does not exist.");
				}

				return floatval( $val[$index] );
			}
			elseif( count( $val ) >= 1 )
			{
				return floatval( $val[0] );
			}
		}

		return floatval( $val );
	}

	public function asString( ?int $index = null ):string
	{
		$val = $this->val;

		if( \is_array( $val ) )
		{
			if( !\is_null( $index ) )
			{
				if( !isset( $val[$index] ) )
				{
					throw new ValidationException("Array index {$index} does not exist.");
				}

				return strval( $val[$index] );
			}
			elseif( count( $val ) >= 1 )
			{
				return strval( $val[0] );
			}
		}

		return strval( $val );
	}

	public function asBool( ?int $index = null ):bool
	{
		$val = $this->val;

		if( \is_array( $val ) )
		{
			if( !\is_null( $index ) )
			{
				if( !isset( $val[$index] ) )
				{
					throw new ValidationException("Array index {$index} does not exist.");
				}

				return boolval( $val[$index] );
			}
			elseif( count( $val ) >= 1 )
			{
				return boolval( $val[0] );
			}
		}

		return boolval( $val );
	}

	public function asArray( ?string $separator = null ):array
	{
		if( \is_array( $this->val ) )
		{
			return $this->val;
		}
		elseif( \is_null( $separator ) || strlen( $separator ) == 0 )
		{
			return \str_split( strval( $this->val ), 1 );
		}

		return \explode( $separator, strval( $this->val ) );
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
		return \is_array( $this->val ) ? \implode( ',', $this->val ) : ( !\is_null( $this->val ) ? strval( $this->val ) : '' );
	}
}

class File extends FileValidator
{
	private $name = null;
	private $basename = null;
	private $extension = null;
	private $universal_type = null;
	private $type = null;
	private $tmp_name = null;
	private $error = null;
	private $size = null;
	private $is_valid = false;
	private $is_uploaded = false;

	public function __construct( array $file )
	{
		$this->name = $file['name'];		
		$this->type = $file['type'];
		$this->tmp_name = $file['tmp_name'];
		$this->error = $file['error'];
		$this->size = $file['size'];

		$this->basename = pathinfo( $file['name'], PATHINFO_BASENAME );
		$this->extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
		$this->universal_type = explode('/', $file['type'])[0] . '/*';
	}

	public function getName():?string
	{
		return $this->name;
	}

	public function getBasename():?string
	{
		return $this->basename;
	}

	public function getExtension():?string
	{
		return $this->extension;
	}

	public function getUniversalType():?string
	{
		return $this->universal_type;
	}

	public function getType():?string
	{
		return $this->type;
	}

	public function getTempName():?string
	{
		return $this->tmp_name;
	}

	public function getUploadError():int
	{
		return $this->error;
	}

	public function getSize():int
	{
		return $this->size;
	}

	public function isFileValid():bool 
	{
		return $this->is_valid;
	}

	protected function setFileValid( bool $is_valid ):void
	{
		$this->is_valid = $is_valid;
	}

	public function isUploaded():bool 
	{
		return $this->is_uploaded;
	}

	protected function setUploaded( bool $is_uploaded ):void
	{
		$this->is_uploaded = $is_uploaded;
	}

	public function __toString()
	{
		return "[File Name:{$this->name}, Size: {$this->size}, Type: {$this->type}, Error: {$this->error}]";
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
		if( \is_array( $value ) )
		{
			$value = \implode(', ', $value);
		}

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
