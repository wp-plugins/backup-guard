<?php
require_once(dirname(__FILE__).'/SGException.php');

class SGExceptionHandler
{
	public static function init()
	{
        set_exception_handler('SGExceptionHandler::exceptionHandler');
    }

    public static function log($exception)
    {
        echo $exception;
        //Sns_Log::log_exception( get_class( $ex ) , $ex->getMessage() , $ex->getFile() , $ex->getLine()  );
    }

    public static function exceptionHandler($exception)
    {
        self::log($exception);
        return true;
    }
}