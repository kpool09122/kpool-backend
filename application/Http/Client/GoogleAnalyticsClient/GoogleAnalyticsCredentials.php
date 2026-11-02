<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleAnalyticsClient;

use Application\Http\Client\GoogleAnalyticsClient\Exceptions\GoogleAnalyticsRequestFailedException;
use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\CredentialsLoader;
use Google\Auth\FetchAuthTokenInterface;
use Throwable;

final class GoogleAnalyticsCredentials
{
    public const string SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    public static function create(string $configuration): FetchAuthTokenInterface
    {
        try {
            if ($configuration === '') {
                return ApplicationDefaultCredentials::getCredentials(self::SCOPE);
            }
            $json = str_starts_with(ltrim($configuration), '{') ? $configuration : file_get_contents($configuration);
            if ($json === false) {
                throw new GoogleAnalyticsRequestFailedException();
            }
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($data) || array_is_list($data)) {
                throw new GoogleAnalyticsRequestFailedException();
            }

            return CredentialsLoader::makeCredentials(self::SCOPE, $data);
        } catch (Throwable) {
            throw new GoogleAnalyticsRequestFailedException('Google Analytics credentials are not configured correctly.');
        }
    }
}
