<?php
	
	namespace App\CustomValidation\Exceptions;
	
	use Respect\Validation\Exceptions\ValidationException;

	class MyOrderException extends ValidationException
	{
	    public static $defaultTemplates = [
	        self::MODE_DEFAULT => [
	            self::STANDARD => '{{name}} not allowed',
	        ],
	        self::MODE_NEGATIVE => [
	            self::STANDARD => 'Validation message if the negative of MyRule is called and fails validation.',
	        ],
	    ];
	}