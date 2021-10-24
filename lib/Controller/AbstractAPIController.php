<?php
namespace OCA\DuplicateFinder\Controller;

use OCP\IRequest;
use OCP\IUserSession;
use OCP\ILogger;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCA\DuplicateFinder\Exception\NotAuthenticatedException;
use OCA\DuplicateFinder\Utils\JSONResponseTrait;

abstract class AbstractAPIController extends ApiController
{
    use JSONResponseTrait;

    /** @var IUserSession|null */
    private $userSession;
    /** @var ILogger */
    protected $logger;

    public function __construct(
        $appName,
        IRequest $request,
        ?IUserSession $userSession,
        ILogger $logger
    ) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->logger = $logger;
    }


    /**
     * @return string
     * @throws NotAuthenticatedException
     */
    protected function getUserId(): string
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

    protected function handleException(\Exception $e): JSONResponse
    {
        if ($e instanceof NotAuthenticatedException) {
            return $this->error($e, Http::STATUS_FORBIDDEN);
        }
        $this->logger->logException($e, ['app' => 'duplicatefinder']);
        return $this->error(new \Exception('Unknown Exception occured'), Http::STATUS_NOT_IMPLEMENTED);
    }
}
