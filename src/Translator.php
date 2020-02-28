<?php


namespace ArsyTranslation\Client;


use ArsyTranslation\Client\Exception\ArsyTranslateException;
use ArsyTranslation\Client\Exception\ArsyTranslationLanguageException;
use ArsyTranslation\Client\Exception\ArsyTranslationUpdateException;
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
            $response = $this->client->post($_ENV[static::TRANSLATION_SERVICE_API_ENDPOINT_ENV_NAME] . '/v1/translate', [
                'form_params' => [
                    'translation_key' => $translationKey,
                    'language' => $language,
                ],
                'headers' => [
                    'x-api-token' => $_ENV[static::TRANSLATION_SERVICE_API_TOKEN_ENV_NAME],
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

    /**
     * @param string $translationKey
     * @param string $translationValue
     * @param string $language
     *
     * @return bool
     * @throws ArsyTranslationUpdateException
     * @throws ArsyTranslationLanguageException
     */
    public function update(string $translationKey, string $translationValue, string $language = 'en')
    {
        if ($language != 'en') {
            throw new ArsyTranslationLanguageException("The language should be en only!");
        }

        try {
            /** @var ResponseInterface $response */
            $response = $this->client->post($_ENV[static::TRANSLATION_SERVICE_API_ENDPOINT_ENV_NAME] . '/v1/update', [
                'form_params' => [
                    'translation_key' => $translationKey,
                    'translation_value' => $translationValue,
                    'language' => $language,
                ],
                'headers' => [
                    'x-api-token' => $_ENV[static::TRANSLATION_SERVICE_API_TOKEN_ENV_NAME],
                ],
            ]);
        } catch (Exception $exception) {
            throw new ArsyTranslationUpdateException("Translation update failed.");
        }

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
            return true;
        }

        return false;
    }
}