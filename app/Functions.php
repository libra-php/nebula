<?php

/**
 * Useful application helper functions
 * NOTE: do not add namespace
 */

use App\Application;
use App\Http\Kernel as HttpKernel;
use App\Console\Kernel as ConsoleKernel;
use App\Models\User;
use Lunar\Interface\DB;
use Nebula\Framework\Database\Database;
use Nebula\Framework\Auth\Auth;
use Nebula\Framework\Session\Session;
use Nebula\Framework\Template\Engine;

/**
 * Get the app instance
 */
function app(): Application
{
    $kernel = HttpKernel::getInstance();
    return Application::getInstance($kernel);
}

/**
 * Get the console instance
 */
function console(): Application
{
    $kernel = ConsoleKernel::getInstance();
    return Application::getInstance($kernel);
}

/**
 * Get the session instance
 */
function session(): Session
{
    return Session::getInstance();
}

/**
 * Get database instance
 */
function db(): ?DB
{
    $database = Database::getInstance();
    $config = config("database");
    return $database->init($config)?->db();
}

/**
 * Get the currently authenticated user model
 */
function user(): ?User
{
    return Auth::user();
}

/**
 * Print a debug
 */
function dump(...$data)
{
    $debug = debug_backtrace()[0];
    $pre_style =
        "overflow-x: auto; font-size: 0.6rem; border-radius: 10px; padding: 10px; background: #133; color: azure; border: 3px dotted azure;";
    $scrollbar_style =
        "scrollbar-width: thin; scrollbar-color: #5EFFA1 #113333;";

    foreach ($data as $datum) {
        if (php_sapi_name() === "cli") {
            print_r($datum);
        } else {
            printf(
                "<pre style='%s %s'><div style='margin-bottom: 5px;'><strong style='color: #5effa1;'>DUMP</strong></div><div style='margin-bottom: 5px;'><strong>File:</strong> %s:%s</div><div style='margin-top: 10px;'>%s</div></pre>",
                $pre_style,
                $scrollbar_style,
                $debug["file"],
                $debug["line"],
                print_r($datum, true)
            );
        }
    }
}

/**
 * Print a debug and die
 */
function dd($data)
{
    dump($data);
    die();
}

/**
 * Get application environment setting
 * If the env key is not present, then a
 * default value may be specified and returned
 */
function env(string $name, $default = "")
{
    if (isset($_ENV[$name])) {
        $lower = strtolower($_ENV[$name]);
        return match ($lower) {
            "true" => true,
            "false" => false,
            default => $_ENV[$name],
        };
        return $_ENV[$name];
    }
    return $default;
}

/**
 * Get application configuration settings
 * @param string $name name of the configuration attribute
 * @return mixed configuration settings
 */
function config(string $name): mixed
{
    // There could be a warning if $attribute
    // is not set, so let's silence it
    @[$file, $key] = explode(".", $name);
    $config_path = __DIR__ . "/../config/$file.php";
    if (file_exists($config_path)) {
        $config = require $config_path;
        return $key && key_exists($key, $config)
            ? $config[$key]
            : $config;
    }
    return false;
}

/**
 * Generate content using template
 * @param string $path template path
 * @param array<string,mixed> $data template data
 * @param bool $decode decode html entities
 */
function template(string $path, array $data = [], bool $decode = false): string
{
    $engine = Engine::getInstance();
    $template = config("path.templates");
    return $decode
        ? html_entity_decode($engine->render("$template/$path", $data))
        : $engine->render("$template/$path", $data);
}
