<?php

namespace Sourceinja\RegisterModule\Exceptions;

use Exception;
use Throwable;

class SourceinjaException extends Exception
{
    /**
     * @var
     */
    protected $message;
    protected $code;

    public function __construct($message = "" , $code = 0 , Throwable $previous = null)
    {
        $this->message = $message;
        $this->code = $code;
        parent::__construct($message , $code , $previous);
    }
}
