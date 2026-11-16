<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Test\Unit\Controller\Adminhtml\Search;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Controller\Adminhtml\Search\Value;
use Infrangible\BackendWidget\Helper\Search;
use Infrangible\Core\Helper\Attribute;
use Infrangible\Core\Helper\Instances;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests that the search controllers only search with the parameters that an autocomplete form element signed.
 */
#[AllowMockObjectsWithoutExpectations]
class ValueTest extends TestCase
{
    /** @var Search */
    private Search $searchHelper;

    /** @var Instances&MockObject */
    private MockObject $instanceHelper;

    /**
     * Creates the search helper with an encryptor that signs with a fixed key.
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
        $this->instanceHelper = $this->createMock(Instances::class);
    }

    /**
     * Returns the controller with a request that has the parameters.
     *
     * @param array $parameters
     *
     * @phpstan-param array<string, string> $parameters
     *
     * @return Value
     */
    private function createController(array $parameters): Value
    {
        $request = $this->createStub(Http::class);

        $request->method('getParam')->willReturnCallback(
            fn(string $name, mixed $default = null): mixed => $parameters[ $name ] ?? $default
        );

        $context = $this->createStub(Context::class);

        $context->method('getRequest')->willReturn($request);
        $context->method('getResponse')->willReturn($this->createStub(ResponseHttp::class));

        return new Value(
            new Arrays(),
            new Json(),
            $context,
            $this->createStub(LoggerInterface::class),
            $this->instanceHelper,
            $this->createStub(Attribute::class),
            $this->searchHelper,
            new Variables(),
            new Escaper()
        );
    }

    /**
     * Returns the parameters of a value search.
     *
     * @return array
     * @phpstan-return array<string, string>
     */
    private function getParameters(): array
    {
        return [
            'object_id'         => '1',
            'search_collection' => \stdClass::class,
            'result_value'      => '{{sku}}'
        ];
    }

    /**
     * Tests that a request with a changed collection is rejected before the collection is created.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testRejectsChangedParameters(): void
    {
        $parameters = $this->searchHelper->sign($this->getParameters());

        $parameters[ 'search_collection' ] = \DateTime::class;

        $this->instanceHelper->expects($this->never())->method('getInstance');

        $this->expectException(NotFoundException::class);

        $this->createController($parameters)->execute();
    }

    /**
     * Tests that a request without signature is rejected.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testRejectsMissingSignature(): void
    {
        $this->instanceHelper->expects($this->never())->method('getInstance');

        $this->expectException(NotFoundException::class);

        $this->createController($this->getParameters())->execute();
    }

    /**
     * Tests that a signed request creates the collection of the form element.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testSearchesWithSignedParameters(): void
    {
        $this->instanceHelper->expects($this->once())->method('getInstance')->with(\stdClass::class)->willReturn(
            new \stdClass()
        );

        $this->createController($this->searchHelper->sign($this->getParameters()))->execute();
    }
}
