<?php


namespace ArsyTranslation\Client\Exception;


use Exception;

class ArsyTranslationLanguageNotFoundException extends Exception
{
    protected $code = 4041;
    protected $message = 'Language not found.';
}