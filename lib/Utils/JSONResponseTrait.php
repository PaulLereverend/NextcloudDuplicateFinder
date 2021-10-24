<?php

namespace OCA\DuplicateFinder\Utils;

use \OCP\AppFramework\Http\JSONResponse;

trait JSONResponseTrait
{
    /**
     * @param \Exception $exception
     * @param int $statusCode
     * @return JSONResponse
     */
    public function error(\Exception $exception, int $statusCode = 500) : JSONResponse
    {
        $data = $this->getEnvelop(false, $statusCode);
        $data['error'] = [
          'message' => $exception->getMessage(),
          'code' =>  $exception->getCode()
        ];
        return new JSONResponse($data, $statusCode);
    }

    /**
     * @param \JsonSerializable|array<mixed> $responseData
     * @param int $statusCode
     * @return JSONResponse
     */
    public function success($responseData, int $statusCode = 200) : JSONResponse
    {
        $data = $this->getEnvelop(true, $statusCode);
        $data['data'] = $responseData;
        return new JSONResponse($data, $statusCode);
    }

    /**
     * @return array<mixed>
     */
    private function getEnvelop(bool $success = true, int $statusCode = 200) : array
    {
        return [
        'success' => $success,
        'status' => $statusCode
        ];
    }
}
