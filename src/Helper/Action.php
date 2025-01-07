<?php

declare(strict_types=1);

namespace Infrangible\BackendWidget\Helper;

use FeWeDev\Base\Json;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\ResponseInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Action
{
    /** @var Http */
    protected $response;

    /** @var Json */
    protected $json;

    public function __construct(ResponseInterface $response, Json $json)
    {
        $this->response = $response;
        $this->json = $json;
    }

    public function processResponse(int $responseCode, array $responseData): HttpInterface
    {
        $this->response->setHttpResponseCode($responseCode);
        $this->response->setHeader(
            'Content-type',
            'application/json'
        );

        $this->response->setBody($this->json->encode($responseData));

        return $this->response;
    }
}
