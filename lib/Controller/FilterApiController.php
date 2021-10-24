<?php
namespace OCA\DuplicateFinder\Controller;

use OCP\IRequest;
use OCP\IUserSession;
use OCP\ILogger;
use OCP\AppFramework\Http\JSONResponse;
use OCA\DuplicateFinder\Exception\UnableToParseException;
use OCA\DuplicateFinder\Service\ConfigService;

class FilterApiController extends AbstractAPIController
{
    /** @var ConfigService */
    private $configService;

    public function __construct(
        $appName,
        IRequest $request,
        ?IUserSession $userSession,
        ConfigService $configService,
        ILogger $logger
    ) {
        parent::__construct($appName, $request, $userSession, $logger);
        $this->configService = $configService;
    }

    /**
     * @return array<mixed>
     */
    private function getFiler() : array
    {
        $value = json_decode($this->configService->getUserValue($this->getUserId(), 'filter', ''));
        if (is_array($value) && !empty($value)) {
            return $value;
        }
        throw new UnableToParseException('user filter');
    }


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function list(): JSONResponse
    {
        try {
            return $this->success($this->getFiler());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @NoAdminRequired
     * @param array<mixed> $filter
     */
    public function save(array $filter): JSONResponse
    {
        try {
            $value = json_encode($filter);
            if (is_string($value)) {
                $this->configService->setUserValue($this->getUserId(), 'filter', $value);
                return $this->success($this->getFiler());
            }
            return $this->error(new UnableToParseException('user filter'), 500);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
