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
        $results = \OCA\Files\Helper::formatFileInfos($files);
        $sizeArr = array();
        foreach ($results as $key => $result) {
			$path = $this->getRelativePath($files[$key]->getPath()). $result['name'];
            $sizeArr[$path] = $result['size'];
		}
		$duplicates = array_intersect($sizeArr, array_diff_assoc($sizeArr, array_unique($sizeArr)));

        $hashArr = array();
        foreach($duplicates as $filePath=>$size){
            if($info = Filesystem::getLocalFile($filePath)) {
                $fileHash = hash_file('md5', $info);
				if($fileHash){
					$hashArr[$filePath] = $fileHash;
				}
            }
        }

        $duplicatesHash = array_intersect($hashArr, array_diff_assoc($hashArr, array_unique($hashArr)));
        asort($duplicatesHash);

        $response = array();
		foreach($duplicatesHash as $filePath=>$fileHash) {
            $file = array();
            $file['infos'] = \OCA\Files\Helper::formatFileInfo(FileSystem::getFileInfo($filePath));
            $file['hash'] = $fileHash;
            $file['path'] = $filePath;
            array_push($response, $file);
        }
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