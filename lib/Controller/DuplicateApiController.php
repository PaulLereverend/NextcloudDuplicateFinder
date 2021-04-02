<?php
namespace OCA\DuplicateFinder\Controller;

use OCP\IRequest;
use OCP\IUserSession;
use OCP\Files\IRootFolder;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCA\DuplicateFinder\Exception\NotAuthenticatedException;
use OCA\DuplicateFinder\Service\FileDuplicateService;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Utils\JSONResponseTrait;

class DuplicateApiController extends ApiController
{
    use JSONResponseTrait;

    /** @var IUserSession|null */
    private $userSession;
    /** @var FileDuplicateService */
    private $fileDuplicateService;
    /** @var FileInfoService */
    private $fileInfoService;
    /** @var IRootFolder */
    private $rootFolder;

    public function __construct(
        $appName,
        IRequest $request,
        ?IUserSession $userSession,
        FileDuplicateService $fileDuplicateService,
        FileInfoService $fileInfoService,
        IRootFolder $rootFolder
    ) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->fileInfoService = $fileInfoService;
        $this->fileDuplicateService = $fileDuplicateService;
        $this->rootFolder = $rootFolder;
    }


    /**
     * @return string
     * @throws NotAuthenticatedException
     */
    private function getUserId(): string
    {
        if ($this->userSession === null) {
            throw new NotAuthenticatedException();
        }
        $user = $this->userSession->getUser();
        if ($user) {
            return $user->getUID();
        } else {
            throw  new NotAuthenticatedException();
        }
    }

    private function handleException(\Exception $e): JSONResponse
    {
        if ($e instanceof NotAuthenticatedException) {
            return $this->error($e, Http::STATUS_FORBIDDEN);
        }
        return $this->error(new \Exception("Unknown Exception occured"), Http::STATUS_NOT_IMPLEMENTED);
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
            return $this->handleException($e);
        }
    }
}
