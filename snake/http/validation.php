<?php
namespace Snake\Http;

class Validation
{
    protected $request;
    protected $errors = [];

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function validate($rules)
    {
        foreach ($rules as $field => $ruleString) {
            $value = $this->getValue($field);
            $ruleList = explode('|', $ruleString);

            foreach ($ruleList as $rule) {
                $params = null;
                if (strpos($rule, ':') !== false) {
                    [$rule, $params] = explode(':', $rule, 2);
                }

                $method = "validate" . ucfirst($rule);
                if (method_exists($this, $method)) {
                    $this->{$method}($field, $value, $params);
                }
            }
        }

        return $this;
    }

    public function fails()
    {
        return !empty($this->errors);
    }

    public function errors()
    {
        return $this->errors;
    }

    protected function getValue($field)
    {
        return $this->request->body->{$field} ?? null;
    }

    protected function addError($field, $message)
    {
        $this->errors[$field][] = $message;
    }

    // --- Validators ---
    protected function validateRequired($field, $value)
    {
        if (is_null($value) || $value === '') {
            $this->addError($field, "$field is required.");
        }
    }

    protected function validateString($field, $value)
    {
        if (!is_null($value) && !is_string($value)) {
            $this->addError($field, "$field must be a string.");
        }
    }

    protected function validateInt($field, $value)
    {
        if (!is_null($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, "$field must be an integer.");
        }
    }

    protected function validateEmail($field, $value)
    {
        if (!is_null($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "$field must be a valid email.");
        }
    }

    protected function validatePhone($field, $value)
    {
        if (!is_null($value) && !preg_match('/^\+?[0-9]{7,15}$/', $value)) {
            $this->addError($field, "$field must be a valid phone number.");
        }
    }

    protected function validateMax($field, $value, $param)
    {
        if (!is_null($value) && strlen($value) > (int)$param) {
            $this->addError($field, "$field must not exceed $param characters.");
        }
    }

    protected function validateMin($field, $value, $param)
    {
        if (!is_null($value) && strlen($value) < (int)$param) {
            $this->addError($field, "$field must be at least $param characters.");
        }
    }

    protected function validateSame($field, $value, $param)
    {
        $other = $this->getValue($param);
        if ($value !== $other) {
            $this->addError($field, "$field must match $param.");
        }
    }

    protected function validateEnum($field, $value, $param)
    {
        $allowed = explode(',', $param);
        if (!in_array($value, $allowed)) {
            $this->addError($field, "$field must be one of: " . implode(', ', $allowed));
        }
    }

}
