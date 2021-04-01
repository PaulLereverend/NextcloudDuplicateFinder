<?php
namespace OCA\DuplicateFinder\Utils;

use Symfony\Component\Console\Output\OutputInterface;
use OCA\DuplicateFinder\Service\FileInfoService;
use OCA\DuplicateFinder\Service\FileDuplicateService;

class CMDUtils {

  public static function showDuplicates(FileDuplicateService $fileDuplicateService, FileInfoService $fileInfoService,OutputInterface $output,\Closure $abortIfInterrupted, ?string $user = null): void{
    if($user === null){
      $output->writeln("Duplicates are: ");
    }else{
      $output->writeln("Duplicates for user '".$user."' are: ");
    }
    $limit = 20;
    $offset = 0;
    do {
      $duplicates = $fileDuplicateService->findAll($user, $limit, $offset);
      foreach($duplicates as $duplicate){
        $output->writeln($duplicate->getHash()."(".$duplicate->getType().")");
        foreach($duplicate->getFiles() as $id => $owner){
          $file = $fileInfoService->findById($id);
          $output->writeln('     '.$file->getPath());
          $abortIfInterrupted();
        };
      }
      $offset += $limit;
    }while(count($duplicates) === $limit);
  }

}
