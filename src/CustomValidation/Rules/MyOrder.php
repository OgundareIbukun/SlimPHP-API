<?php

	namespace App\CustomValidation\Rules;
	
	use App\Models\Verification;
    use Respect\Validation\Rules\AbstractRule;
    use RedBeanPHP\R;

	class MyOrder extends AbstractRule
	{
		public function validate($input)
	    {
            $verification = new Verification();
            $payment_type =  $verification->stringToLowerCase($input);
	    	if(empty($payment_type))
	    	{
		        return true;
		    }
		    else
		    {
		    	$exits = R::findOne('paymentmethod', 'method_name=?', [$payment_type]);

		        if(count($exits))
		        {
		        	return true;
		        }
		        else if(!count($exits))
		        {
		        	return false;
		        }
		    }
	    }
	}
?>