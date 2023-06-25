<?php

namespace Leaf\Helpers;

class ApiException extends \Exception
{
    public $errors = [];

    /**
     * @param string $errorStr
     */
    public function __construct($errorStr)
    {
        if (is_array($errorStr)) {
            $this->errors = $errorStr;
            parent::__construct("API Exception");
            return;
        }
        parent::__construct($errorStr);
    }

    public function errors()
    {
        if (count($this->errors) > 0) {
            return $this->errors;
        }
        return [$this->message];
    }

    public function json()
    {
        if (count($this->errors) > 0) {
            return json_encode([
                "status" => "error",
                "errors" => $this->errors
            ]);
        }
        return json_encode([
            "status" => "error",
            "message" => $this->message
        ]);
    }
}
