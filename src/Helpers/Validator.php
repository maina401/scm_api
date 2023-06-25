<?php

namespace Leaf\Helpers;

use Exception;
use Ramsey\Uuid\Uuid;

class Validator
{
    public static $errors = [];

    /**
     * @throws Exception
     */
    public static function make($data, $rules): Validator
    {
        $validator = new Validator();
        $validator::$errors = []; // clear any previous errors

        if (gettype($data) !== 'array') {
            $data = json_decode(json_encode($data), true);
        }
        if (!is_array($data)) {
            throw new ApiException('Invalid data format');
        }

        foreach ($rules as $field => $rule) {
            $rulesList = explode('|', $rule);

            foreach ($rulesList as $r) {
                $params = [];

                if (strpos($r, ':') !== false) {
                    list($r, $paramStr) = explode(':', $r);
                    $params = explode(',', $paramStr);
                }

                $valid = true;
                switch ($r) {
                    case 'required':
                        $valid = isset($data[$field]);
                        $message = self::getMessage('required', $field);
                        break;
                    case 'string':
                        $valid = isset($data[$field]) && is_string($data[$field]);
                        $message = self::getMessage('string', $field);
                        break;
                    case 'email':
                        $valid = isset($data[$field]) && filter_var($data[$field], FILTER_VALIDATE_EMAIL);
                        $message = self::getMessage('email', $field);
                        break;
                    case 'min':
                        if (!isset($params[0])) {
                            throw new ApiException('Min rule requires a minimum value. eg: min:6');
                        }
                        $valid = isset($data[$field]) && strlen($data[$field]) >= $params[0];
                        $message = self::getMessage('min', $field, $params[0]);
                        break;
                    case 'max':
                        $valid = isset($data[$field]) && strlen($data[$field]) <= $params[0];
                        $message = self::getMessage('max', $field);
                        break;
                    case 'uuid':
                        $valid = isset($data[$field]) && Uuid::isValid($data[$field]);
                        if (!$valid) {
                            throw new ApiException($field . ' is not a valid uuid');
                        }
                        $message = self::getMessage('uuid', $field);
                        break;
                    case 'confirmed':
                        if (!isset($data[$field . '_confirmation'])) {
                            throw new ApiException($field . '_confirmation' . ' field is required');
                        }
                        $valid = isset($data[$field]) && $data[$field] === $data[$field . '_confirmation'];
                        $message = self::getMessage('confirmed', $field);
                        break;
                    case 'image_url':
                        $valid = isset($data[$field]) && self::isImageUrl($data[$field]);
                        $message = self::getMessage('image_url', $field);
                        break;
                    case 'array':
                        $valid = isset($data[$field]) && is_array($data[$field]);
                        $message = self::getMessage('array', $field);
                        break;
                    case 'regex':
                        if (!isset($params[0])) {
                            throw new ApiException('Regex rule requires a regex pattern. eg: regex:/[a-z]/');
                        }
                        $valid = isset($data[$field]) && preg_match($params[0], $data[$field]);
                        $message = self::getMessage('regex', $field);
                        break;
                        break;
                    case 'unique':
                        if (!isset($params[0]) || !isset($params[1])) {
                            throw new ApiException('Unique rule requires table and column name. eg: unique:users,email');
                        }
                        $valid = isset($data[$field]) && self::unique($data[$field], $params[0], $params[1]);
                        $message = self::getMessage('unique', $field);
                        break;
                    case 'exists':
                        if (!isset($params[0]) || !isset($params[1])) {
                            throw new ApiException('Exists rule requires table and column name. eg: exists:users,email');
                        }
                        $valid = isset($data[$field]) && self::exists($data[$field], $params[0], $params[1]);
                        $message = self::getMessage('exists', $field);
                        break;
                    default:
                        continue 2;

                }

                if (!$valid) {
                    $errors = $validator::$errors;
                    $validator::$errors[] = $message;
                }
            }
        }
        if (count($validator::$errors) > 0) {
            throw new ApiException($validator::$errors);
        }
        return $validator;
    }


    private static function isImage(mixed $field): bool
    {
        if (is_array($field)) {
            $field = $field['tmp_name'];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $field);
        return str_starts_with($mime, 'image');
    }

    private static function isImageUrl(mixed $field): bool
    {
        if (!is_string($field) || empty($field) || !filter_var($field, FILTER_VALIDATE_URL)) {
            return false;
        }
        $headers = get_headers($field);
        return str_starts_with($headers[0], 'HTTP/1.1 200 OK');//check if url is valid
    }

    public function errors(): array
    {
        return self::$errors;
    }

    //json errors
    private static function getMessage(string $string, string $field, $min = null, $max = null): array|string
    {
        $message = self::errorMessages()[$string];
        if ($min) {
            $message = str_replace(':min', $min, $message);
        }
        if ($max) {
            $message = str_replace(':max', $max, $message);
        }
        return str_replace(':attribute', $field, $message);
    }

    private static function unique($field, $model, $column): bool
    {
        //capitalize first letter and singularize
        $model = rtrim(ucfirst($model), 's');
        $model = "Leaf\\Models\\$model";
        $model = new $model();
        $model = $model->where($column, $field)->first();
        return $model === null;
    }

    private static function exists($field, $model, $column): bool
    {
        //capitalize first letter
        $model = rtrim(ucfirst($model), 's');
        $model = "Leaf\\Models\\$model";
        $model = new $model();

        if (is_numeric($field)) {
            $field = (int)$field;
        }
        $model = $model->query();
        //if column has #, then its multi-column check. Split
        if (str_contains($column, '#')) {
            $columns = explode('#', $column);
            foreach ($columns as $col) {
                //if column is id, then check if its a valid uuid
                if ($col === 'id') {
                    $valid = Uuid::isValid($field);
                    if ($valid) {
                        $model->orWhere($col, $field);
                    }
                } else {
                    $model->orWhere($col, $field);
                }
            }
        } else {
            $model->where($column, $field);
        }

        $model = $model->first();
        return $model !== null;
    }


    public static function hasErrors(): bool
    {
        return count(self::$errors) > 0;
    }

    //error messages
    public static function errorMessages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'email' => 'The :attribute field must be a valid email address.',
            'unique' => 'The :attribute field must be unique.',
            'min' => 'The :attribute field must be at least :min characters.',
            'max' => 'The :attribute field must be at most :max characters.',
            'in' => 'The :attribute field must be one of the following types: :values',
            'integer' => 'The :attribute field must be an integer.',
            'float' => 'The :attribute field must be a float.',
            'array' => 'The :attribute field must be an array.',
            'boolean' => 'The :attribute field must be a boolean.',
            'confirmed' => 'The :attribute field confirmation does not match.',
            'regex' => 'The :attribute field format is invalid.',
            'exists' => 'The :attribute field does not exist.',
            'valid_phone' => 'The :attribute field must be a valid phone number.',
            'image' => 'The :attribute field must be an image.',
            'image_url' => 'The :attribute field must be a valid image url.',
            'uuid' => 'The :attribute field must be a valid uuid.',

        ];
    }
}
