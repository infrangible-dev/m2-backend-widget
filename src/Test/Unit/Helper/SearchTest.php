<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Test\Unit\Helper;

use Infrangible\BackendWidget\Helper\Search;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests that the search parameters of autocomplete fields can not be changed without breaking the signature.
 */
class SearchTest extends TestCase
{
    /** @var Search */
    private Search $searchHelper;

    /**
     * Creates the helper with an encryptor that signs with a fixed key.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $encryptor = $this->createStub(EncryptorInterface::class);

        $encryptor->method('hash')->willReturnCallback(
            fn(string $data): string => hash_hmac(
                'sha256',
                $data,
                'key'
            )
        );

        $this->searchHelper = new Search($encryptor);
    }

    /**
     * Returns the parameters of a search.
     *
     * @return array
     * @phpstan-return array<string, string>
     */
    private function getParameters(): array
    {
        return [
            'search_collection' => ProductCollection::class,
            'search_fields'     => 'sku,name',
            'result_id'         => '{{entity_id}}',
            'result_value'      => '{{sku}}',
            'result_label'      => '{{sku}}: {{name}}'
        ];
    }

    /**
     * Tests that signed parameters are valid, also in another order.
     *
     * @return void
     */
    public function testSignedParametersAreValid(): void
    {
        $signedParameters = $this->searchHelper->sign($this->getParameters());

        $signature = $signedParameters[ Search::PARAM_SIGNATURE ];

        $this->assertTrue(
            $this->searchHelper->isValid(
                array_reverse(
                    $this->getParameters(),
                    true
                ),
                $signature
            )
        );
        $this->assertTrue(
            $this->searchHelper->isValid(
                $signedParameters,
                $signature
            )
        );
    }

    /**
     * Returns changed parameters and signatures that must not be valid.
     *
     * @return array
     * @phpstan-return array<string, array{array<string, string>, bool}>
     */
    public static function getChangedParameters(): array
    {
        return [
            'collection'        => [['search_collection' => \stdClass::class], true],
            'added expression'  => [['search_expressions' => '{"x":"(SELECT password FROM admin_user)"}'], true],
            'added condition'   => [['search_conditions' => '{"is_active":{"eq":0}}'], true],
            'result label'      => [['result_label' => '{{password}}'], true],
            'missing signature' => [[], false]
        ];
    }

    /**
     * Tests that changed parameters, a missing signature or a wrong signature are not valid.
     *
     * @dataProvider getChangedParameters
     *
     * @param array $changes
     * @param bool  $withSignature
     *
     * @phpstan-param array<string, string> $changes
     *
     * @return void
     */
    #[DataProvider('getChangedParameters')]
    public function testChangedParametersAreInvalid(array $changes, bool $withSignature): void
    {
        $signedParameters = $this->searchHelper->sign($this->getParameters());

        $this->assertFalse(
            $this->searchHelper->isValid(
                array_merge(
                    $this->getParameters(),
                    $changes
                ),
                $withSignature ? $signedParameters[ Search::PARAM_SIGNATURE ] : ''
            )
        );
    }
}
