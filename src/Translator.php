<?php


namespace ArsyTranslation\Client;


use ArsyTranslation\Client\Exception\ArsyTranslationException;
use ArsyTranslation\Client\Exception\ArsyTranslationLanguageException;
use ArsyTranslation\Client\Exception\ArsyTranslationNotFoundException;
use ArsyTranslation\Client\Exception\ArsyTranslationUpdateException;
use Composer\Autoload\ClassLoader;
use Composer\Factory;
use Exception;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Dotenv\Dotenv;

class Translator
{
    const TRANSLATION_SERVICE_API_ENDPOINT_ENV_NAME = 'TRANSLATION_SERVICE_ENDPOINT';
    const TRANSLATION_SERVICE_API_TOKEN_ENV_NAME = 'TRANSLATION_SERVICE_TOKEN';

    const CLIENT_STATIC = 1;
    const SERVER_STATIC = 2;
    const SERVER_DYNAMIC = 3;

    /** @var Client $client */
    protected $client;

    public function __construct()
    {
        $this->client = new Client();

        if (!class_exists(Dotenv::class)) {
            throw new RuntimeException('Please run "composer require symfony/dotenv" to load the ".env" files configuring the application.');
        } else {
            $reflection = new ReflectionClass(ClassLoader::class);
            $vendorDir = dirname(dirname($reflection->getFileName()));

            // load all the .env files
            (new Dotenv(false))->loadEnv($vendorDir . '/../.env');
        }
    }

    /**
     * @param string $translationKey
     * @param string $language
     * @param int $source
     *
     * @return string
     * @throws ArsyTranslationException
     * @throws ArsyTranslationNotFoundException
     */
    public function translate(string $translationKey, string $language = 'en', int $source = self::SERVER_STATIC): ?string
    {
        try {
            /** @var ResponseInterface $response */
            $response = $this->client->get($_ENV[static::TRANSLATION_SERVICE_API_ENDPOINT_ENV_NAME] . '/v1/translate/show', [
                'query' => [
                    'type' => $source,
                    'translation_key' => $translationKey,
                    'language' => $language,
                ],
                'headers' => [
                    'x-project-token' => $_ENV[static::TRANSLATION_SERVICE_API_TOKEN_ENV_NAME],
                ],
            ]);
        } catch (Exception $exception) {
            if ($exception->getCode() === 404) {
                throw new ArsyTranslationNotFoundException($exception->getMessage());
            }

            throw new ArsyTranslationException("Translation request failed." . $exception->getMessage());
        }

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
            $responseContents = $response->getBody()->getContents();

            $translation = json_decode($responseContents, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $translation['data']['body']['translation'];
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