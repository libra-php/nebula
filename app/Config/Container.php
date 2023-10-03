<?php

namespace App\Config;

use App\Http\Kernel as HttpKernel;
use Nebula\Http\Request;
use Nebula\UI\Twig\Extension;

return [
    /** Singletons **/
    \Nebula\Interfaces\Http\Kernel::class => HttpKernel::getInstance(),
    \Nebula\Interfaces\Framework\Environment::class => \Nebula\Framework\Environment::getInstance(),
    \Nebula\Interfaces\Http\Request::class => Request::getInstance(),
    \Nebula\Interfaces\Database\Database::class => function () {
        $db = \Nebula\Database\MySQLDatabase::getInstance();
        $config = config("database");
        $enabled = $config["enabled"];
        if ($enabled && !$db->isConnected()) {
            $db->connect($config);
        }
        return $db;
    },
    /** Non-Singletons **/
    \Nebula\Interfaces\Console\Kernel::class => \DI\get(
        \App\Console\Kernel::class
    ),
    \Nebula\Interfaces\Session\Session::class => \DI\get(
        \Nebula\Session\Session::class
    ),
    \Nebula\Interfaces\Http\Response::class => \DI\get(
        \Nebula\Http\Response::class
    ),
    \Nebula\Interfaces\Routing\Router::class => \DI\get(
        \Nebula\Routing\Router::class
    ),
    \Nebula\Interfaces\Model\Model::class => \DI\get(
        \Nebula\Model\Model::class
    ),
    \Nebula\Interfaces\Mail\Email::class => \DI\get(
        \Nebula\Mail\EmailSMTP::class
    ),
    \Nebula\Interfaces\Database\QueryBuilder::class => \DI\get(
        \Nebula\Database\QueryBuilder::class
    ),
    \Twig\Environment::class => function () {
        $config = config("twig");
        $loader = new \Twig\Loader\FilesystemLoader($config["view_path"]);
        $twig = new \Twig\Environment($loader, [
            "cache" => $config["cache_path"],
            "auto_reload" => true,
        ]);
        $twig->addExtension(new Extension());
        return $twig;
    },
    \Latte\Engine::class => function () {
        $config = config("latte");
        $latte = new \Latte\Engine();
        $latte->setLoader(new \Latte\Loaders\FileLoader($config["view_path"]));
        $latte->setTempDirectory($config["cache_path"]);
        $latte->addFunction("csrf", fn() => csrf());
        $latte->addFunction("route", fn(string $name) => route($name));
        $latte->addFunction("buildRoute", fn(string $name, ...$replacements) => buildRoute($name, ...$replacements));
        return $latte;
    },
];
