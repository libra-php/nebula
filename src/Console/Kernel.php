<?php

namespace Nebula\Console;

use App\Auth;
use App\Models\Factories\UserFactory;
use Nebula\Interfaces\Framework\Kernel as ConsoleKernel;
use Nebula\Interfaces\Http\Response;
use Throwable;
use App\Models\User;


/**
 * Class Kernel
 * @package Nebula\Console
 */
class Kernel implements ConsoleKernel
{
    protected Response $response;
    protected array $paths;
    protected string $output = "";
    protected array $opts = [
        "short" => [
            "h" => "Print help and exit.",
            "s" => "Start development server.",
            "t" => "Run tests.",
        ],
        "long" => [
            "help" => "Print help and exit.",
            "fix-permissions" => "Automatically fix permissions on directories.",
            "migration-table:" => "Create new table migration. Usage: --migration-table=<table_name>",
            "migration-create:" => "Create new empty migration. Usage: --migration-create=<migration_name>",
            "migration-list" => "List all migrations and their status.",
            "migration-run" => "Run all migrations that have not been run yet.",
            "migration-up:" => "Run migration up on file. Usage: --migration-up=<filename>.php",
            "migration-down:" => "Run migration down on file. Usage: --migration-down=<filename>.php",
            "migration-fresh" => "Create new database and run all migrations. Be careful!",
            "seed-user" => "Seed application user. A secure password will be generated. It is advised to change this password immediately.",
        ],
    ];
    protected array $commands = [];

    protected function registerCommand(
        string $type,
        string $option,
        string $description,
        callable $callback
    ): void {
        if (!in_array($type, ["short", "long"])) {
            throw new \Exception("Invalid command type!");
        }
        if (isset($this->opts[$type][$option])) {
            throw new \Exception("Command already registered!");
        }
        $this->opts[$type][$option] = $description;
        $option = str_replace(":", "", $option);
        $this->commands[$option] = $callback;
    }

    protected function run(): void
    {
        $long_opts = $this->opts["long"];
        $short_opts = $this->opts["short"];

        $longopts = array_keys($long_opts);
        $shortopts = implode("", array_keys($short_opts));
        $options = getopt($shortopts, $longopts);

        if (empty($options)) {
            $this->write(
                "Unknown option(s) provided. Use -h or --help for help."
            );
        }
        foreach ($options as $opt => $value) {
            match ($opt) {
                "s" => $this->startServer(),
                "t" => $this->runTests(),
                "h", "help" => $this->displayHelp(),
                "fix-permissions" => $this->fixPermissions(),
                "migration-table" => $this->migrationCreate($value, "_table"),
                "migration-create" => $this->migrationCreate($value),
                "migration-run" => $this->runMigrations(),
                "migration-list" => $this->migrationList(),
                "migration-up" => $this->migration($value, true),
                "migration-down" => $this->migration($value, false),
                "migration-fresh" => $this->migrationFresh(),
                "seed-user" => $this->seedUser(),
                default => $this->tryCommand($opt, $value),
            };
        }
    }

    protected function tryCommand(string $option, string $value)
    {
        if (isset($this->commands[$option])) {
            $this->commands[$option]($value);
            return;
        }
        $this->displayUnknownOption($option);
    }

    protected function banner(): string
    {
        $banner = <<<EOT
  _   _      _           _
 | \ | | ___| |__  _   _| | __ _
 |  \| |/ _ \ '_ \| | | | |/ _` |
 | |\  |  __/ |_) | |_| | | (_| |
 |_| \_|\___|_.__/ \__,_|_|\__,_|
EOT;
        return $banner;
    }

    protected function help(): string
    {
        $help = <<<EOT
Usage:   nebula [options]

Basic options:
EOT;
        $help .= PHP_EOL;
        foreach ($this->opts as $type => $opts) {
            foreach ($opts as $opt => $desc) {
                $opt = str_replace(":", "", $opt);
                $opt = $type === "short" ? "-" . $opt : "--" . $opt;
                $spacer = round(strlen($opt) / 9);
                $offset = 4;
                $spacer = str_repeat("\t", $offset - $spacer);
                $help .= "  {$opt}{$spacer}{$desc}" . PHP_EOL;
            }
        }
        return "\n" . $help;
    }

    protected function fixPermissions()
    {
        $twig_cache = config("twig.cache_path");
        $latte_cache = config("latte.cache_path");
        $logs = config("paths.logs");
        foreach (array_unique([$twig_cache, $latte_cache, $logs]) as $path) {
            if (!file_exists($path)) {
                mkdir($path);
            }
            chown($path, "www-data");
            $this->write("Permission fixed: $path");
        }
    }

    protected function migrationCreate(
        string $table_name,
        string $prefix = ""
    ): void {
        $table_name = strtolower($table_name);
        $table_name = str_replace(" ", "_", $table_name);
        $table_name = str_replace("-", "_", $table_name);
        $table_name = str_replace(".", "_", $table_name);

        $table_up = <<<EOT
return Schema::create("%s", function (Blueprint \$table) {
        });
EOT;
        $table_down = <<<EOT
return Schema::drop("%s");
EOT;

        $template = <<<EOT
<?php

namespace Nebula\Migrations;

use Nebula\Interfaces\Database\Migration;
use Nebula\Database\Blueprint;
use Nebula\Database\Schema;

return new class implements Migration
{
    public function up(): string
    {
        %s
    }

    public function down(): string
    {
        %s
    }
};
EOT;
        if ($prefix === "_table") {
            $template = sprintf(
                $template,
                sprintf($table_up, $table_name),
                sprintf($table_down, $table_name)
            );
        } else {
            $template = sprintf($template, 'return "";', 'return "";');
        }

        $filename =
            $this->paths["migrations"] . time() . "{$prefix}_{$table_name}.php";
        if (file_put_contents($filename, $template)) {
            $this->write("Migration created: {$filename}");
        } else {
            $this->write("Failed to create migration!");
        }
    }

    protected function runTests(): void
    {
        $this->write("Running tests...");
        echo `./bin/test`;
        $this->terminate();
    }

    protected function startServer(): void
    {
        $this->write("Starting server...");
        `./bin/serve`;
        $this->terminate();
    }

    public function setup(): void
    {
        $this->response = app()->get(Response::class);
        $this->paths = config("paths");
    }

    public function handle(): Response
    {
        try {
            $this->run();
        } catch (\Exception $exception) {
            return $this->handleException($exception);
        }
        $this->response->setContent($this->output);
        return $this->response;
    }

    public function handleException(Throwable $exception): Response
    {
        $this->response->setContent(
            "Nebula console error!" .
                PHP_EOL .
                $exception->getMessage() .
                PHP_EOL
        );
        return $this->response;
    }

    public function terminate(): never
    {
        //logger('timeEnd', 'Nebula');
        exit();
    }

    protected function echo(string $content): void
    {
        echo $content . PHP_EOL;
    }

    protected function write(string $content): void
    {
        $this->output .= $content . PHP_EOL;
    }

    protected function input(string $prompt)
    {
        $input = readline($prompt);
        $input = trim($input);
        if ($input !== '') {
            return $input;
        }
        $this->input($prompt);
    }

    protected function displayUnknownOption(string $option): void
    {
        $this->write("\nUnknown option: " . $option);
    }

    protected function displayHelp(): void
    {
        $this->write($this->banner());
        $this->write($this->help());
    }
    protected function getMigrationClass(string $file): mixed
    {
        $this->checkMigrationTable();
        if (!file_exists($file)) {
            throw new \Exception("Migration file not found: {$file}");
        }
        return require $file;
    }

    protected function migrationList(): void
    {
        $this->checkMigrationTable();
        $migrations = db()
            ->query("SELECT migration_hash FROM migrations")
            ->fetchAll(\PDO::FETCH_COLUMN);
        $files = $this->getMigrationFiles();
        foreach ($files as $file) {
            $base = basename($file);
            $hash = $this->getFileHash($file);
            $run = in_array($hash, $migrations);
            $this->write(($run ? "[OK]" : "[PENDING]") . " {$base}");
        }
    }

    protected function migration(
        string $file,
        bool $up,
        bool $skip = false
    ): void {
        $base = basename($file);
        $filename = $this->paths["migrations"] . $base;
        $migration = $this->getMigrationClass($filename);
        $query = $up ? $migration->up() : $migration->down();
        $word = $up ? "up" : "down";

        if (!$skip) {
            $input = readline("Run $base migration [$word]? (y/n): ");
            if (strtolower($input) !== "y") {
                $this->write("Migration cancelled!");
                return;
            }
        }

        if ($up && $this->migrationExists($filename)) {
            $this->write("Migration already exists, skipping: {$file}");
            return;
        }

        $this->write("Running migration $word on {$base}");
        $result = db()->query($query);

        if ($result) {
            if ($up) {
                $this->recordMigration($filename);
            } else {
                $this->deleteMigration($filename);
            }
            $this->write("Migration $word successful!");
        } else {
            $this->write("Migration $word failed!");
        }
    }

    protected function checkMigrationTable(): void
    {
        $query = "CREATE TABLE IF NOT EXISTS migrations (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        migration_hash VARCHAR(32) NOT NULL,
        ts TIMESTAMP(0) NOT NULL DEFAULT NOW(),
        PRIMARY KEY (id),
        UNIQUE (migration_hash))";
        $result = db()->query($query);
        if (!$result) {
            throw new \Exception("Failed to create migrations table!");
        }
    }

    public function getFileHash(string $file): string
    {
        return md5($file);
    }

    protected function migrationFresh(): void
    {
        $input = readline(
            "Are you sure you want to create a new database and run all migrations? (y/n): "
        );
        if (strtolower($input) !== "y") {
            $this->write("Migration cancelled!");
            return;
        }
        $this->dropDatabase();
        $this->createDatabase();
        $this->checkMigrationTable();
        $this->runMigrations(true);
    }

    protected function getMigrationFiles(): array|bool
    {
        return glob($this->paths["migrations"] . "*.php");
    }

    protected function runMigrations(bool $skip = false): void
    {
        $this->write("Running migrations...");
        $files = $this->getMigrationFiles();
        foreach ($files as $file) {
            if (!$this->migrationExists($file)) {
                $this->migration($file, true, $skip);
            }
        }
        $this->write("Migrations complete!");
    }

    protected function dropDatabase(): void
    {
        $db_name = config("database.name");
        db()->query("DROP DATABASE IF EXISTS " . $db_name);
    }

    protected function createDatabase(): void
    {
        $db_name = config("database.name");
        db()->query("CREATE DATABASE IF NOT EXISTS " . $db_name);
        db()->query("USE " . $db_name);
        $this->write("Database created!");
    }

    protected function migrationExists(string $file): bool
    {
        $result = db()->select(
            "SELECT * FROM migrations WHERE migration_hash = ?",
            $this->getFileHash($file)
        );
        return !is_null($result) && $result !== false;
    }

    protected function recordMigration(string $file): void
    {
        db()->query(
            "INSERT IGNORE INTO migrations SET migration_hash = ?",
            $this->getFileHash($file)
        );
    }

    protected function deleteMigration(string $file): void
    {
        db()->query(
            "DELETE FROM migrations WHERE migration_hash = ?",
            $this->getFileHash($file)
        );
    }

    function generateSecurePassword()
    {
        // Define the character sets to use in the password
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '()!_@/\\';

        // Concatenate all character sets
        $allChars = $uppercase . $lowercase . $numbers . $specialChars;

        // Get the total length of the character set
        $charSetLength = strlen($allChars);

        // Initialize the password variable
        $password = '';

        // Generate a 12-character password
        for ($i = 0; $i < 12; $i++) {
            // Get a random index within the character set
            $randomIndex = mt_rand(0, $charSetLength - 1);

            // Append the randomly selected character to the password
            $password .= $allChars[$randomIndex];
        }

        return $password;
    }

    protected function seedUser()
    {
        ask_name:
        $name = $this->input("Enter name: ");
        if ($name == '') {
            goto ask_name;
        }

        ask_email:
        $email = $this->input("Enter email address: ");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->echo("Invalid email...");
            goto ask_email;
        }

        $user = User::search(["email", $email]);
        if ($user) {
            $this->echo("User already registered.");
            exit;
        }

        ask_type:
        $choices = [1,2,3];
        $type = $this->input("Enter user type [1 = super admin, 2 = admin, 3 = standard user]: ");
        if (!in_array($type, $choices)) {
            $this->echo("Invalid type...");
            goto ask_type;
        }

        $factory = app()->get(UserFactory::class);
        $password = $this->generateSecurePassword();
        $user = $factory->create($name, $email, $password, $type);
        if ($user) {
            $qr = Auth::urlQR($user);
            $this->echo("Success! Please use this password to sign in: " . $password);
            $this->echo("GoogleAuthenticator QR: " . $qr);
            exit;
        }

        $this->echo("Error! Something went wrong.");
        exit;
    }

    protected function adminPassword()
    {
        $this->write("wip");
    }
}
