<?php
namespace OCA\DuplicateFinder\Utils;

class CMDUtils {

  public static function showDuplicates($fileDuplicateService, $fileInfoService, $output, $abortIfInterrupted, ?string $user = null){
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
