<?php
namespace OCA\DuplicateFinder\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OC\Files\Filesystem;
class ApiController extends Controller {

    /**
     * @NoAdminRequired
     */
    public function files() {
        $files = $this->getFilesRecursive();
        //var_dump($files);
        $results = \OCA\Files\Helper::formatFileInfos($files);
        $hashArr = array();
        foreach ($results as $key => $result) {
			$path = $this->getRelativePath($files[$key]->getPath()). $result['name'];
			if($info = Filesystem::getLocalFile($path)) {
				$fileHash = hash_file('md5', $info);
				if($fileHash){
					$hashArr[$path] = $fileHash;
				}
			}
		}
        $arr_unique = array_unique($hashArr);
		$arr_duplicates = array_diff_assoc($hashArr, $arr_unique);
		$duplicates = array_intersect($hashArr, $arr_duplicates);
		asort($duplicates);
        $previousHash = 0;
        $response = array();
        //error_log(print_r($duplicates, true));
		foreach($duplicates as $filePath=>$fileHash) {
			if($previousHash != $fileHash){
            }
            $file = array();
            $file['infos'] = \OCA\Files\Helper::formatFileInfo(FileSystem::getFileInfo($filePath));
            $file['hash'] = $fileHash;
            $file['path'] = $filePath;
            array_push($response, $file);
			$previousHash = $fileHash;
        }
        //var_dump($response);
        //$response = \OCA\Files\Helper::formatFileInfos($response);
        return $response;
    }

    private function getFilesRecursive(& $results = [], $path = '') {
        $files = FileSystem::getDirectoryContent($path);
        foreach($files as $file) {
            if ($file->getType() === 'dir') {
                $this->getFilesRecursive($results, $path . '/' . $file->getName());
            } else {
                $results[] = $file;
            }
        }

        return $results;
    }

    private function getRelativePath($path) {
        $path = Filesystem::getView()->getRelativePath($path);
        return substr($path, 0, strlen($path) - strlen(basename($path)));
    }

}