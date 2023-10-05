<?php

namespace Nebula\Alerts;

class Flash
{
    private static $statuses = [
        "error",
        "database",
        "success",
        "warning",
        "info",
    ];

    public static function addFlash($status, $message)
    {
        if (in_array($status, self::$statuses)) {
			$flash = session()->get("flash");
            if (
                isset($flash[$status]) &&
                in_array($message, $flash[$status])
            ) {
                return;
            }
			$flash[$status][] = $message;
			session()->set("flash", $flash);
        }
    }

    public static function hasFlash()
    {
        return session()->has("flash");
    }

    public static function hasStatus($status): bool
    {
		$flash = session()->get("flash");
        return isset($flash[$status]);
    }

    public static function clearStatus($status)
    {
		$flash = session()->get("flash");
		unset($flash[$status]);
		session()->set("flash", $flash);
    }

    public static function getSessionFlash()
    {
		$flash = session()->get("flash");
        $alerts = "";
        foreach (self::$statuses as $status) {
            if (isset($flash[$status])) {
                foreach ($flash[$status] as $key => $message) {
                    $alerts .= self::alert($status, $message);
                    unset($flash[$status][$key]);
					session()->set("flash", $flash);
                }
            }
        }
        return $alerts;
    }

    public static function alert($status, $message)
    {
        $var = match ($status) {
            "error" => self::error($message),
            "success" => self::success($message),
            "warning" => self::warning($message),
            "database" => self::database($message),
            "info" => self::info($message),
        };
        return $var;
    }

    public static function error($message)
    {
        return "<div class='flash error'>
            <div><strong>&#9762;</strong></div><div>{$message}</div>
        </div>";
    }
    public static function success($message)
    {
        return "<div class='flash success'>
            <div><strong>&#10003;</strong></div><div>{$message}</div>
        </div>";
    }
    public static function warning($message)
    {
        return "<div class='flash warning'>
            <div><strong>&#9888;</strong></div><div>{$message}</div>
        </div>";
    }
    public static function database($message)
    {
        return "<div class='flash database'>
            <div><strong>&#9888;</strong></div><div>{$message}</div>
        </div>";
    }
    public static function info($message)
    {
        return "<div class='flash info'>
            <div><strong>&#128712;</strong></div><div>{$message}</div>
        </div>";
    }
}
