<?php

namespace Nebula\Framework\Controller;

use Nebula\Framework\Alerts\Flash;
use Symfony\Component\HttpFoundation\Request;

class Controller
{
    protected array $error_messages = [
        "array" => "{title} must be an array",
        "email" => "Must be a valid email address",
        "float" => "{title} must be a float",
        "int" => "{title} must be an integer",
        "match" => "Must match {arg}",
        "max" => "Value is greater than maximum allowed: {arg}",
        "min" => "Value is less than minimum allowed: {arg}",
        "maxlength" => "Length must be less than or equal to: {arg}",
        "minlength" => "Length must be greater than or equal to: {arg}",
        "numeric" => "{title} must be numeric",
        "required" => "{title} is required",
        "string" => "{title} must be a string",
        "unique" => "{title} is already taken",
        "symbol" => "{title} must contain a special character",
        "not_empty" => "{title} cannot be empty",
    ];
    protected array $request_errors = [];

    public function __construct(protected Request $request)
    {
        $this->bootstrap();
    }

    /**
     * Override method
     * Allows for additional configuration on Y initialization
     */
    protected function bootstrap(): void
    {
    }

    public function pageNotFound(): void
    {
        header("Location: /page-not-found", response_code: 418);
        header("HX-Location: /page-not-found", response_code: 418);
        exit();
    }

    public function permissionDenied(): void
    {
        header("Location: /permission-denied", response_code: 403);
        header("HX-Location: /permission-denied", response_code: 403);
        exit();
    }

    /**
     * Render template response
     * @param string $path template path
     * @param array<string,mixed> $data template data
     */
    public function render(string $path, array $data = []): string
    {
        // Template functions
        $data["request_errors"] = fn (string $field) => $this->getRequestError(
            $field
        );
        $data["has_error"] = fn (string $field) => $this->hasRequestError(
            $field
        );
        $data["escape"] = fn (string $key) => $this->escapeRequest($key);
        $data["messages"] = template("components/flash.php", [
            "flash" => Flash::get(),
        ]);

        return template($path, $data, true);
    }

    /**
     * Return the request error for a given field
     * @param string $field
     */
    public function getRequestError(string $field): ?string
    {
        if (!isset($this->request_errors[$field])) {
            return null;
        }
        return template("components/request_errors.php", [
            "errors" => $this->request_errors[$field],
        ]);
    }

    /**
     * Sanitize value for template
     * @param string $key
     */
    private function escapeRequest(string $key): mixed
    {
        return htmlspecialchars(
            $this->request($key) ?? "",
            ENT_QUOTES | ENT_HTML5,
            "UTF-8"
        );
    }

    /**
     * Return request super global
     */
    public function getRequest(): array
    {
        $request = [];
        $exclude = ["PHPSESSID", "csrf_token"];
        foreach ($this->request()->request as $key => $value) {
            if (!in_array($key, $exclude)) {
                $request[$key] = $value;
            }
        }
        return $request;
    }

    /**
     * Returns a formatted rule and averment
     * @param mixed $rule
     */
    private function getRuleArg(mixed $rule): array
    {
        $raw = explode("|", $rule);
        $rule = $raw[0];
        $arg = isset($raw[1]) ? $raw[1] : "";
        return [strtolower($rule), $arg];
    }

    /**
     * Returns a validated request array
     * @param array $ruleset
     * @return bool|array false if validation fails
     */
    public function validateRequest(array $ruleset): bool|array
    {
        $request = $this->getRequest();
        foreach ($ruleset as $column => $rules) {
            $valid = true;
            $value = isset($request[$column]) ? $request[$column] : null;
            if ($value === "NULL") {
                $value = null;
            }
            $is_required = in_array("required", $rules);
            foreach ($rules as $idx => $rule) {
                if ($idx === "custom" && is_array($rule)) {
                    $method = $rule["callback"] ?? false;
                    $message = $rule["message"] ?? "*message not set*";
                    $valid &= $method($column, $value);
                    if (!$valid) {
                        $this->addRequestError($column, $message);
                    }
                } else {
                    [$rule, $arg] = $this->getRuleArg($rule);
                    if (
                        ($rule != "match" && (is_string($value) && trim($value) === "") ||
                            is_null($value) ||
                            $value === "NULL") &&
                        !$is_required
                    ) {
                        $valid &= true;
                    } else {
                        $valid &= match ($rule) {
                            "not_empty" => trim($value) !== "",
                            "array" => is_array($value),
                            "email" => filter_var(
                                $value,
                                FILTER_VALIDATE_EMAIL
                            ) !== false,
                            "match" => $value == $request[$arg],
                            "max" => intval($value) <= intval($arg),
                            "min" => intval($value) >= intval($arg),
                            "maxlength" => strlen($value) <= intval($arg),
                            "minlength" => strlen($value) >= intval($arg),
                            "numeric" => is_numeric($value),
                            "required" => !is_null($value) && trim($value) !== "" &&
                                $value !== "NULL",
                            "string" => is_string($value),
                            "unique" => !db()->fetch(
                                "SELECT * FROM $arg WHERE $column = ?",
                                $value
                            ),
                            "symbol" => preg_match("/[^\w\s]/", $value),
                            default => false,
                        };
                    }
                }
                if (
                    !$valid &&
                    is_string($rule) &&
                    isset($this->error_messages[$rule])
                ) {
                    $message = $this->error_messages[$rule];
                    $this->addRequestError($column, $message, $arg);
                }
            }
        }
        return empty($this->request_errors) ? $request : false;
    }

    /**
     * Get the current user IP
     * @return string $ip
     */
    public function userIp(): string
    {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        return $ip;
    }

    /**
     * Replace {title} in the error message
     * @param string $field
     * @param string $message
     * @return string
     */
    protected function replaceErrorTitle(string $field, string $message): string
    {
        return str_replace("{title}", ucfirst($field), $message);
    }

    /**
     * Replace {arg} in the error message
     * @param string $field
     * @param string $message
     * @return string
     */
    protected function replaceErrorArg(?string $arg, string $message): string
    {
        if (!$arg) return $message;
        return str_replace("{arg}", $arg, $message);
    }

    /**
     * Add an error to the request_errors array
     * @param string $field
     * @param ?string $arg
     * @param string $message
     */
    public function addRequestError(
        string $field,
        string $message,
        ?string $arg = null
    ): void {
        $message = $this->replaceErrorTitle($field, $message);
        $message = $this->replaceErrorArg($arg, $message);
        $this->request_errors[$field][] = $message;
    }

    /**
     * Does the request_errors array contain errors for a given field
     * @param string $field
     */
    public function hasRequestError(string $field): bool
    {
        return isset($this->request_errors[$field]);
    }

    public function request(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->request;
        }
        return $this->request->get($key, $default);
    }
}
