<?php
namespace OCA\DuplicateFinder\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\Events\Node\NodeTouchedEvent;
use OCA\DuplicateFinder\Listener\FilesytemListener;

class Application extends App implements IBootstrap {

  public function __construct() {
      parent::__construct('duplicatefinder');
  }

  public function register(IRegistrationContext $context): void {

    $context->registerEventListener(NodeDeletedEvent::class, FilesytemListener::class);
    $context->registerEventListener(NodeRenamedEvent::class, FilesytemListener::class);
    $context->registerEventListener(NodeCopiedEvent::class, FilesytemListener::class);
    $context->registerEventListener(NodeCreatedEvent::class, FilesytemListener::class);
    $context->registerEventListener(NodeWrittenEvent::class, FilesytemListener::class);
    $context->registerEventListener(NodeTouchedEvent::class, FilesytemListener::class);
  }

  public function boot(IBootContext $context): void {
      //Dummy Required by IBootstrap
  }
}
