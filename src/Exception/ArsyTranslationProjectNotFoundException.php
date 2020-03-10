<?php


namespace ArsyTranslation\Client\Exception;


use Exception;

class ArsyTranslationProjectNotFoundException extends Exception
{
    protected $code = 4040;
    protected $message = 'Project not found.';
}