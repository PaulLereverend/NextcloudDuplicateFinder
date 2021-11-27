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
use OCA\DuplicateFinder\Utils\JSONResponseTrait;

abstract class AbstractAPIController extends ApiController
{
    use JSONResponseTrait;

    /** @var IUserSession|null */
    private $userSession;
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        $appName,
        IRequest $request,
        ?IUserSession $userSession,
        LoggerInterface $logger
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
        $this->logger->error('A unknown exception occured', ['app' => Application::ID, 'exception' => $e]);
        return $this->error(new \Exception('Unknown Exception occured'), Http::STATUS_NOT_IMPLEMENTED);
    }
}
