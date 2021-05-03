<?php
namespace OCA\DuplicateFinder\Controller;

use OCP\IRequest;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OC\Files\Filesystem;
use OCA\DuplicateFinder\Service\FileDuplicateService;
use OCA\DuplicateFinder\Service\FileInfoService;

class PageController extends Controller
{

    /** @var string|null */
    private $userId;
    /** @var FileDuplicateService */
    private $fileDuplicateService;
    /** @var FileInfoService */
    private $fileInfoService;
    /** @var IRootFolder */
    private $rootFolder;

    public function __construct(
        string $AppName,
        IRequest $request,
        ?string $UserId,
        FileDuplicateService $fileDuplicateService,
        FileInfoService $fileInfoService,
        IRootFolder $rootFolder
    ) {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
        $this->fileInfoService = $fileInfoService;
        $this->fileDuplicateService = $fileDuplicateService;
        $this->rootFolder = $rootFolder;
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse
    {
        //
        return new TemplateResponse('duplicatefinder', 'index');  // templates/index.php
    }

    /**
     * @NoAdminRequired
     * @return array<mixed>
     */
    public function files(?int $offset = null, ?int $limit = 20):array
    {
        $response = array();
        $duplicates = $this->fileDuplicateService->findAll($this->userId, $limit, $offset);
        foreach ($duplicates as $duplicate) {
            foreach ($duplicate->getFiles() as $fileInfoId => $owner) {
                $fileInfo = $this->fileInfoService->findById($fileInfoId);
                $userFolder = $this->rootFolder->getUserFolder($fileInfo->getOwner());
                $node = $this->rootFolder->get($fileInfo->getPath());
                $response[] = [
                    'hash' => $fileInfo->getFileHash(),
                    'path' => substr($fileInfo->getPath(), strlen($userFolder->getPath())),
                    'infos' => [
                        "id" => $node->getId(),
                        "size" => $node->getSize(),
                        "mimetype" => $node->getMimetype()
                    ]
                ];
            }
        }
        return $response;
    }
}
