<?php

namespace Skyroom\Exception;

use Exception;

class BatchOperationFailedException extends Exception
{
    public $errors;

    public function __construct($errors)
    {
        parent::__construct('Batch operation failed.');
        $this->errors = $errors;
    }
}
