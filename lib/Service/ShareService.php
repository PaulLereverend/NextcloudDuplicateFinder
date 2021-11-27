<?php
namespace OCA\DuplicateFinder\Service;

use OCA\DuplicateFinder\Utils\PathConversionUtils;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;
use OCP\Files\Node;
use OCP\Files\IRootFolder;
use OCP\Share\IManager;

class ShareService
{
    /** @var IRootFolder */
    private $rootFolder;
    /** @var LoggerInterface */
    private $logger;
    /** @var IManager */
    private $shareManager;

    public function __construct(
        IRootFolder $rootFolder,
        IManager $shareManager,
        LoggerInterface $logger
    ) {
        $this->rootFolder = $rootFolder;
        $this->shareManager = $shareManager;
        $this->logger = $logger;
    }

    /**
     * @return array<IShare>
     */
    public function getShares(
        string $user,
        ?Node $node = null,
        int $limit = -1
    ): array {
        $shares = array();
        $shareTypes = [IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_CIRCLE, IShare::TYPE_ROOM];
        //TYPE_DECK is not supported by NC 20
        if (defined('OCP\Share\IShare::TYPE_DECK')) {
            $shareTypes[] = IShare::TYPE_DECK;
        }
        foreach ($shareTypes as $shareType) {
            try {
                $shares = array_merge($shares, $this->shareManager->getSharedWith(
                    $user,
                    $shareType,
                    $node,
                    $limit
                ));
            } catch (\Throwable $e) {
                $this->logger->error('Failed to get shares', ['exception'=> $e]);
            }
            
            if ($limit > 0 && count($shares) >= $limit) {
                break;
            }
        }
        unset($shareType);
        return $shares;
    }

    public function hasAccessRight(Node $sharedNode, string $user) : ?string
    {
        $accessList = $this->shareManager->getAccessList($sharedNode, true, true);
        if (isset($accessList['users']) && isset($accessList['users'][$user])) {
            $node = $sharedNode;
            $stripedFolders = 0;
            while ($node) {
                $shares = $this->getShares($user, $node, 1);
                if (!empty($shares)) {
                    $this->logger->debug('Target Path: @'.$shares[0]->getTarget().'@ '.$shares[0]->getNodeType());
                    return PathConversionUtils::convertSharedPath(
                        $this->rootFolder->getUserFolder($user),
                        $this->rootFolder->getUserFolder($shares[0]->getSharedWith()),
                        $sharedNode,
                        $shares[0],
                        $stripedFolders
                    );
                }
                $node = $node->getParent();
                $stripedFolders++;
            }
        }
        return null;
    }
}
