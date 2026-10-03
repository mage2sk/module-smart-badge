<?php
declare(strict_types=1);

namespace Panth\SmartBadge\Test\Unit\Fixture;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;

/**
 * Builds a backend action context whose collaborators record what the
 * controller did (messages, redirect target, JSON payload).
 */
trait BuildsBackendContext
{
    /** @var Http */
    private $request;

    /** @var array<string, string[]> */
    private array $messages = ['error' => [], 'success' => [], 'warning' => []];

    private ?string $redirectPath = null;
    private array $redirectParams = [];
    private $jsonPayload = null;

    private function backendContext(): Context
    {
        if ($this->request === null) {
            $this->request = $this->createStub(Http::class);
        }

        $messageManager = $this->createStub(ManagerInterface::class);
        foreach (['error' => 'addErrorMessage', 'success' => 'addSuccessMessage', 'warning' => 'addWarningMessage'] as $type => $method) {
            $messageManager->method($method)->willReturnCallback(function ($message) use ($type, $messageManager) {
                $this->messages[$type][] = (string)$message;
                return $messageManager;
            });
        }

        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setPath')->willReturnCallback(function ($path, $params = []) use ($redirect) {
            $this->redirectPath = $path;
            $this->redirectParams = $params;
            return $redirect;
        });
        $redirectFactory = $this->createStub(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($redirect);
        $resultFactory = $this->createStub(ResultFactory::class);
        $resultFactory->method('create')->willReturn($redirect);

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getMessageManager')->willReturn($messageManager);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);
        $context->method('getResultFactory')->willReturn($resultFactory);

        return $context;
    }

    private function jsonFactory(): JsonFactory
    {
        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(function ($data) use ($json) {
            $this->jsonPayload = $data;
            return $json;
        });
        $factory = $this->createStub(JsonFactory::class);
        $factory->method('create')->willReturn($json);

        return $factory;
    }
}
