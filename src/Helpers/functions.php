<?php

use App\Auth;
use App\Models\User;
use Idearia\Logger;
use Nebula\Framework\Application;
use Nebula\Http\Request;
use Nebula\Interfaces\Database\Database;
use Nebula\Interfaces\Framework\Environment;
use Nebula\Interfaces\Session\Session;
use Nebula\Validation\Validate;
use Composer\ClassMapGenerator\ClassMapGenerator;
use Nebula\Mail\EmailSMTP;

/**
 * This is a file that contains generic application functions
 * Do not add a namespace to this file
 */

/**
 * Dump args
 */
function dump(...$args)
{
    $out = array_map(fn($arg) => print_r($arg, true), $args);
    printf("<pre>%s</pre>", implode("\n\n", $out));
}

/**
 * Dump args and die
 */
function dd(...$args)
{
    dump(...$args);
    die();
}

function json(mixed $data)
{
    return json_encode($data, JSON_PRETTY_PRINT);
}

/**
 * Used in a module
 * for defining select_options values
 */
function option(string $id, mixed $name)
{
    return (object) ["id" => $id, "name" => $name];
}

/**
 * Get the middleware index of a given middleware name
 */
function middlewareIndex(array $middleware, string $name)
{
    foreach ($middleware as $key => $one) {
        if (preg_match("/$name/", $one)) {
            return $key;
        }
    }
}

/**
 * Return a token string
 */
function token(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Generate a class map for the given directory
 * @return array<class-string,non-empty-string>
 */
function classMap(string $directory): array
{
    if (!file_exists($directory)) {
        throw new \Exception("class map directory doesn't exist");
    }
    return ClassMapGenerator::createMap($directory);
}

function redirect(string $url, int $code = 301, int $delay = 0): never
{
    logger("timeEnd", "Nebula");
    if ($delay > 0) {
        header("Refresh: $delay; URL=$url", response_code: $code);
    } else {
        header("Location: $url", response_code: $code);
    }
    exit();
}

function route(string $name): ?string
{
    $route = app()
        ->use()
        ->router->findRouteByName($name);
    return $route->getPath();
}

function buildRoute(string $name, ...$replacements): ?string
{
    $route_path = route($name);
    if ($route_path) {
        foreach ($replacements as $val) {
            $route_path = preg_replace("/\{[A-z_]+\}/", $val, $route_path, 1);
        }
        return $route_path;
    }
    return null;
}

function redirectRoute(string $name, int $code = 301, int $delay = 0): never
{
    $route = app()
        ->use()
        ->router->findRouteByName($name);
    if ($route) {
        redirect($route->getPath(), $code, $delay);
    }
}

function moduleRoute(
    string $route_name,
    string $module_name,
    ?string $id = null
) {
    $path = route($route_name);
    $path = str_replace("{module}", $module_name, $path);
    $path = str_replace("{id}", $id ?? "", $path);
    return $path;
}

function redirectModule(
    string $route_name,
    string $module_name,
    ?string $id = null,
    ?string $source = null,
    ?string $target = null
) {
    $route_path = moduleRoute($route_name, $module_name, $id);
    if ($source && $target) {
        header(
            'HX-Location: {"path":"' .
                $route_path .
                '", "source":"' .
                $source .
                '", "target":"' .
                $target .
                '"}'
        );
    } else {
        header("HX-Location: $route_path");
    }
}

function initLogger()
{
    try {
        $log_path = config("paths.logs");
        $log_name = "nebula";
        $log_ext = "log";
        $log_file = $log_path . $log_name . "." . $log_ext;
        if (!file_exists($log_file)) {
            touch($log_file);
        }
        Logger::$write_log = true;
        Logger::$log_level = "debug";
        Logger::$log_dir = $log_path;
        Logger::$log_file_name = $log_name;
        Logger::$log_file_extension = $log_ext;
        Logger::$print_log = false;
    } catch (\Exception) {
    }
}

function logger(string $level, string $message, string $title = "")
{
    $enabled = config("app.logging");
    if ($enabled) {
        try {
            match ($level) {
                "time" => Logger::time($message),
                "timeEnd" => Logger::timeEnd($message),
                "debug" => Logger::debug($message, $title),
                "info" => Logger::info($message, $title),
                "warning" => Logger::warning($message, $title),
                "error" => Logger::error($message, $title),
                default => throw new \Exception("unknown log level"),
            };
        } catch (\Exception) {
        }
    }
}

/**
 * Return the SMTP emailer
 */
function smtp(): EmailSMTP
{
    $emailer = app()->get(EmailSMTP::class);
    $emailer->init();
    return $emailer;
}

/**
 * Return client IP
 */
function ip(): string
{
    if (!empty(request()->server()["HTTP_CLIENT_IP"])) {
        $ip = request()->server()["HTTP_CLIENT_IP"];
    } elseif (!empty(request()->server()["HTTP_X_FORWARDED_FOR"])) {
        $ip = request()->server()["HTTP_X_FORWARDED_FOR"];
    } else {
        $ip = request()->server()["REMOTE_ADDR"];
    }
    return $ip;
}

/**
 * Return the application singleton
 */
function app(): Application
{
    return Application::getInstance();
}

/**
 * Returns the application request singleton
 */
function request(): Request
{
    return app()->get(Request::class);
}

/**
 * Return the application configuration by name
 */
function config(string $name)
{
    $name_split = explode(".", $name);
    if (count($name_split) > 1) {
        $config = \Nebula\Config\Config::get($name_split[0]);
        return $config[$name_split[1]] ??
            throw new \Exception("Configuration item doesn't exist");
    }
    return \Nebula\Config\Config::get($name);
}

/**
 * Return the app user
 */
$app_user = null;
function user(): ?User
{
    global $app_user;
    $uuid = session()->get("user");
    if ($uuid) {
        if (!$app_user) {
            $user = User::search(["uuid", $uuid]);
            if ($user) {
                $app_user = $user;
                return $user;
            }
        } else {
            return $app_user;
        }
    }
    return null;
}

/**
 * Return the application environment variable by name
 */
function env(string $name, ?string $default = null)
{
    $env = app()->get(Environment::class);
    return $env->get($name) ?? $default;
}

/**
 * Return the application database
 */
function db(): Database
{
    return app()->get(Database::class);
}

function csrf(): string
{
    $token = session()->get("csrf_token");
    $input = <<<EOT
  <input type="hidden" name="csrf_token" value="$token">
EOT;
    return $input;
}

/**
 * Return the application session class
 */
function session(): Session
{
    return app()->get(Session::class);
}

/**
 * Return a twig rendered string
 */
function twig(string $path, array $data = []): string
{
    $twig = app()->get(\Twig\Environment::class);
    $form_errors = Validate::$errors;
    $data["form_errors"] = $form_errors;
    $data["form_error_keys"] = array_keys($form_errors);
    $data["app"] = config("app");
    return $twig->render($path, $data);
}

/**
 * Return a latte rendered string
 */
function latte(string $path, array $data = [], ?string $block = null): string
{
    $latte = app()->get(\Latte\Engine::class);
    $form_errors = Validate::$errors;
    $data["form_errors"] = $form_errors;
    $data["form_error_keys"] = array_keys($form_errors);
    $data["app"] = config("app");
    $data["user"] = Auth::getUser();
    return $latte->renderToString($path, $data, $block);
}
