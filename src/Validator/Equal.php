<?php
namespace Qing\Lib\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation\Message;
class Equal extends Validator implements ValidatorInterface
{
    
    public function validate(Validation $validator,$attribute)
    {
        $value = $validator->getValue($attribute);
        $equal = $this->getOption('equal');
        if ($value !==$equal) {
            // Check if the developer has defined a custom message
            $message = $this->getOption('message') ?: sprintf('%s is not equal %s', $value,$equal);
            $validator->appendMessage(new Message($message, $attribute, 'Equal'));
            return false;
        }

        return true;
    }
}
