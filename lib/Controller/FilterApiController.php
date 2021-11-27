<?php
namespace OCA\DuplicateFinder\Controller;

use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
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
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request, $userSession, $logger);
        $this->configService = $configService;
    }

    /**
     * @return array<mixed>
     */
    private function getFilter() : array
    {
        $value = json_decode($this->configService->getUserValue($this->getUserId(), 'filter', ''));
        if (is_array($value)) {
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
            return $this->success($this->getFilter());
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
        $error = null;
        try {
            $value = json_encode($filter);
            if (is_string($value)) {
                $this->configService->setUserValue($this->getUserId(), 'filter', $value);
                return $this->success($this->getFilter());
            }
        } catch (\Exception $e) {
            $error = $e;
        }
        return $this->handleException(new UnableToParseException('user filter', $error));
    }
}
