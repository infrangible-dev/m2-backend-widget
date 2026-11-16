<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Helper;

use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Signs the search parameters of autocomplete fields, so that the search controllers only run searches that a form
 * defined, and not collections, expressions or conditions sent by the browser.
 */
class Search
{
    public const PARAM_SIGNATURE = 'signature';

    /**
     * @param EncryptorInterface $encryptor
     */
    public function __construct(private readonly EncryptorInterface $encryptor)
    {
    }

    /**
     * Returns the parameters with their signature added.
     *
     * @param array $parameters
     *
     * @phpstan-param array<string, string> $parameters
     *
     * @return array
     * @phpstan-return array<string, string>
     */
    public function sign(array $parameters): array
    {
        $parameters[ self::PARAM_SIGNATURE ] = $this->getSignature($parameters);

        return $parameters;
    }

    /**
     * Checks whether the signature matches the parameters.
     *
     * @param array  $parameters
     * @param string $signature
     *
     * @phpstan-param array<string, string> $parameters
     *
     * @return bool
     */
    public function isValid(array $parameters, string $signature): bool
    {
        return $signature !== '' && hash_equals(
            $this->getSignature($parameters),
            $signature
        );
    }

    /**
     * Returns the signature of the parameters, created with the encryption key of the installation.
     *
     * @param array $parameters
     *
     * @phpstan-param array<string, string> $parameters
     *
     * @return string
     */
    private function getSignature(array $parameters): string
    {
        unset($parameters[ self::PARAM_SIGNATURE ]);

        ksort($parameters);

        return $this->encryptor->hash(http_build_query($parameters));
    }
}
