<?php


namespace ArsyTranslation\Client\Exception;


use Exception;

class ArsyTranslationTranslationNotFoundException extends Exception
{
    protected $code = 4042;
    protected $message = 'Translation not found';
}