<?php
namespace OCA\DuplicateFinder\Controller;

use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCA\DuplicateFinder\AppInfo\Application;
use OCA\DuplicateFinder\Exception\NotAuthenticatedException;
use OCA\DuplicateFinder\Service\FileDuplicateService;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Utils\JSONResponseTrait;

class DuplicateApiController extends AbstractAPIController
{
    /** @var FileDuplicateService */
    private $fileDuplicateService;
    /** @var FileInfoService */
    private $fileInfoService;

    public function __construct(
        $appName,
        IRequest $request,
        ?IUserSession $userSession,
        FileDuplicateService $fileDuplicateService,
        FileInfoService $fileInfoService,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request, $userSession, $logger);
        $this->fileInfoService = $fileInfoService;
        $this->fileDuplicateService = $fileDuplicateService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function list(int $offset = 0, int $limit = 20): JSONResponse
    {
        try {
            $duplicates = $this->fileDuplicateService->findAll($this->getUserId(), $limit, $offset, true);
            return $this->success($duplicates);
        } catch (\Exception $e) {
            $this->logger->error('A unknown exception occured', ['app' => Application::ID, 'exception' => $e]);
            return $this->handleException($e);
        }
    }
}
