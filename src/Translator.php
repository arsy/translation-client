<?php


namespace ArsyTranslator;


use ArsyTranslator\Exception\ArsyTranslateException;
use Exception;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class Translator
{
    const TRANSLATION_SERVICE_API_ENDPOINT_ENV_NAME = 'TRANSLATION_SERVICE_ENDPOINT';
    const TRANSLATION_SERVICE_API_TOKEN_ENV_NAME = 'TRANSLATION_SERVICE_TOKEN';

    /** @var Client $client */
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param string $translationKey
     * @param string $language
     *
     * @return string
     * @throws ArsyTranslateException
     */
    public function translate(string $translationKey, string $language): ?string
    {
        try {
            /** @var ResponseInterface $response */
            $response = $this->client->post(getenv(static::TRANSLATION_SERVICE_API_ENDPOINT_ENV_NAME), [
                'form_params' => [
                    'translation_key' => $translationKey,
                    'language' => $language,
                ],
                'headers' => [
                    'x-api-token' => getenv(static::TRANSLATION_SERVICE_API_TOKEN_ENV_NAME),
                ],
            ]);
        } catch (Exception $exception) {
            throw new ArsyTranslateException("Translation request failed.");
        }

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
            $responseContents = $response->getBody()->getContents();

            $translation = json_decode($responseContents, true)['body']['translation'];

            if (json_last_error() === JSON_ERROR_NONE) {
                return $translation;
            }

            return null;
        }
    }
}