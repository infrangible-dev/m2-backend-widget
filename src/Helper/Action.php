<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Helper;

use FeWeDev\Base\Json;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\ResponseInterface;

class Action
{
    /** @var HttpInterface */
    protected HttpInterface $response;

    /**
     * @param ResponseInterface $response
     * @param Json $json
     */
    public function __construct(
        ResponseInterface $response,
        protected Json $json
    ) {
        if (! $response instanceof HttpInterface) {
            throw new \InvalidArgumentException('The response has to be an HTTP response');
        }

        $this->response = $response;
    }

    /**
     * Processes the response.
     *
     * @param int $responseCode
     * @param array $responseData
     *
     * @phpstan-param array<int|string, mixed> $responseData
     *
     * @return HttpInterface
     */
    public function processResponse(int $responseCode, array $responseData): HttpInterface
    {
        $this->response->setHttpResponseCode($responseCode);
        $this->response->setHeader(
            'Content-type',
            'application/json'
        );

        $this->response->setBody((string)$this->json->encode($responseData));

        return $this->response;
    }
}
