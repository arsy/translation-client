<?php


namespace ArsyTranslation\Client;


use ArsyTranslation\Client\Exception\ArsyTranslationCreateException;
use ArsyTranslation\Client\Exception\ArsyTranslationDeleteException;
use ArsyTranslation\Client\Exception\ArsyTranslationException;
use ArsyTranslation\Client\Exception\ArsyTranslationLanguageException;
use ArsyTranslation\Client\Exception\ArsyTranslationLanguageNotFoundException;
use ArsyTranslation\Client\Exception\ArsyTranslationProjectNotFoundException;
use ArsyTranslation\Client\Exception\ArsyTranslationTranslationNotFoundException;
use ArsyTranslation\Client\Exception\ArsyTranslationUpdateException;
use Composer\Autoload\ClassLoader;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Dotenv\Dotenv;

class Translator
{
    private const HTTP_NOT_FOUND_PROJECT = 4040;
    private const HTTP_NOT_FOUND_LANGUAGE = 4041;
    private const HTTP_NOT_FOUND_TRANSLATION = 4042;

    private const TRANSLATION_SERVICE_API_ENDPOINT_ENV_NAME = 'TRANSLATION_SERVICE_ENDPOINT';
    private const TRANSLATION_SERVICE_API_TOKEN_ENV_NAME = 'TRANSLATION_SERVICE_TOKEN';

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
     * @throws ArsyTranslationProjectNotFoundException
     * @throws ArsyTranslationLanguageNotFoundException
     * @throws ArsyTranslationTranslationNotFoundException
     */
    public function translate(string $translationKey, int $source = self::SERVER_STATIC, string $language = 'en'): ?string
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
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
        }

        $responseContents = json_decode($response->getBody()->getContents(), true);

        if (array_key_exists('meta', $responseContents) && !$responseContents['meta']['success']) {
            switch ($responseContents['meta']['customStatusCode']) {
                case self::HTTP_NOT_FOUND_PROJECT:
                    throw new ArsyTranslationProjectNotFoundException();
                    break;
                case self::HTTP_NOT_FOUND_LANGUAGE:
                    throw new ArsyTranslationLanguageNotFoundException();
                    break;
                case self::HTTP_NOT_FOUND_TRANSLATION:
                    throw new ArsyTranslationTranslationNotFoundException();
                    break;
            }
        }

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400 && json_last_error() === JSON_ERROR_NONE) {
            return $responseContents['data']['body']['translation'];
        }

        return null;
    }

    /**
     * @param int $source
     *
     * @param string $language
     *
     * @return array|null
     * @throws ArsyTranslationException
     */
    public function translateAll(int $source = self::SERVER_STATIC, string $language = 'en'): ?array
    {
        try {
            /** @var ResponseInterface $response */
            $response = $this->client->get($_ENV[static::TRANSLATION_SERVICE_API_ENDPOINT_ENV_NAME] . '/v1/translate', [
                'query' => [
                    'type' => $source,
                ],
                'headers' => [
                    'x-project-token' => $_ENV[static::TRANSLATION_SERVICE_API_TOKEN_ENV_NAME],
                    'x-locale' => $language . '_EN',
                ],
            ]);
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
        }

        $responseContents = json_decode($response->getBody()->getContents(), true);

        if (array_key_exists('meta', $responseContents) && !$responseContents['meta']['success']) {
            throw new ArsyTranslationException();
        }

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400 && json_last_error() === JSON_ERROR_NONE) {
            return $responseContents['data']['body']['translations'];
        }

        return null;
    }

    /**
     * @param string $translationKey
     * @param string $translationValue
     * @param string $language
     *
     * @return bool
     * @throws ArsyTranslationCreateException
     * @throws ArsyTranslationLanguageException
     */
    public function create(string $translationKey, string $translationValue, string $language = 'en'): bool
    {
        if ($language != 'en') {
            throw new ArsyTranslationLanguageException("The language should be en only!");
        }

        try {
            /** @var ResponseInterface $response */
            $response = $this->client->post($_ENV[static::TRANSLATION_SERVICE_API_ENDPOINT_ENV_NAME] . '/v1/translate/create', [
                'form_params' => [
                    'translation_key' => $translationKey,
                    'translation_value' => $translationValue,
                    'language' => $language,
                    'type' => self::SERVER_DYNAMIC,
                ],
                'headers' => [
                    'x-project-token' => $_ENV[static::TRANSLATION_SERVICE_API_TOKEN_ENV_NAME],
                ],
            ]);
        } catch (Exception $exception) {
            throw new ArsyTranslationCreateException("Translation update failed.");
        }

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
            return true;
        }

        return false;
    }

    /**
     * @param string $translationKey
     * @param string $translationValue
     * @param string $language
     *
     * @return bool
     * @throws ArsyTranslationLanguageException
     * @throws ArsyTranslationUpdateException
     */
    public function update(string $translationKey, string $translationValue, string $language = 'en'): bool
    {
        if ($language != 'en') {
            throw new ArsyTranslationLanguageException("The language should be en only!");
        }

        try {
            /** @var ResponseInterface $response */
            $response = $this->client->patch($_ENV[static::TRANSLATION_SERVICE_API_ENDPOINT_ENV_NAME] . '/v1/translate/update', [
                'form_params' => [
                    'translation_key' => $translationKey,
                    'translation_value' => $translationValue,
                    'language' => $language,
                    'type' => self::SERVER_DYNAMIC,
                ],
                'headers' => [
                    'x-project-token' => $_ENV[static::TRANSLATION_SERVICE_API_TOKEN_ENV_NAME],
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

    /**
     * @param string $translationKey
     * @param string $language
     *
     * @return bool
     * @throws ArsyTranslationDeleteException
     * @throws ArsyTranslationLanguageException
     */
    public function delete(string $translationKey, string $language = 'en'): bool
    {
        if ($language != 'en') {
            throw new ArsyTranslationLanguageException("The language should be en only!");
        }

        try {
            /** @var ResponseInterface $response */
            $response = $this->client->delete($_ENV[static::TRANSLATION_SERVICE_API_ENDPOINT_ENV_NAME] . '/v1/translate/delete', [
                'form_params' => [
                    'translation_key' => $translationKey,
                    'type' => self::SERVER_DYNAMIC,
                ],
                'headers' => [
                    'x-project-token' => $_ENV[static::TRANSLATION_SERVICE_API_TOKEN_ENV_NAME],
                ],
            ]);
        } catch (Exception $exception) {
            throw new ArsyTranslationDeleteException("Translation update failed.");
        }

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
            return true;
        }

        return false;
    }
}