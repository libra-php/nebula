# Nebula PHP Framework - Migrations Documentation

Welcome to the Migrations documentation for the Nebula PHP Framework. This guide will walk you through the process of using migrations to manage your database schema changes. Migrations allow you to version control and apply changes to your database structure in a consistent and organized manner.

## Table of Contents

- [Introduction to Migrations](#introduction-to-migrations)
- [Creating Migrations](#creating-migrations)
- [Migration Methods](#migration-methods)
  - [Creating Tables](#creating-tables)
  - [Dropping Tables](#dropping-tables)
- [Running Migrations](#running-migrations)
- [Rolling Back Migrations](#rolling-back-migrations)
- [Conclusion](#conclusion)

## Introduction to Migrations

Migrations are a way to define and manage changes to your database schema over time. Instead of manually applying changes to the database, you can use migration files to define the structure of your database tables and relationships. Migrations provide an organized and versioned approach to managing your database schema changes.

## Creating Migrations

To create a migration, you need to implement the `Migration` interface, which includes the `up()` and `down()` methods. The `up()` method defines the changes you want to apply to the database when migrating up, while the `down()` method defines the actions to be taken when rolling back the migration.

Here's an example of a migration implementation:

```php
namespace Nebula\Migrations;

use Nebula\Interfaces\Database\Migration;
use Nebula\Database\Blueprint;
use Nebula\Database\Schema;

return new class implements Migration
{
    public function up(): string
    {
      return Schema::create("users", function (Blueprint $table) {
        // Define columns and constraints here
      });
    }

    public function down(): string
    {
      return Schema::drop("users");
    }
};
```

In the `up()` method, you use the `Schema` and `Blueprint` classes to define the changes you want to make to the database. The `down()` method should reverse these changes to roll back the migration.

## Migration Methods

### Creating Tables

To create a new table in the database, you can use the `Schema::create()` method within the `up()` method of your migration. The `Blueprint` instance allows you to define columns, indexes, and other table properties.

Here's an example of creating a "users" table:

```php
return Schema::create("users", function (Blueprint $table) {
    $table->id();
    $table->varchar("name");
    $table->varchar("email")->unique();
    // Add more columns and constraints as needed
});
```

⭐ There is a built-in command for creating a new table migration file:

```bash
./nebula --migration-table=<table_name>
```

If you want to create an empty migration file, then run:

```bash
./nebula --migration-create=<migration_name>
```

### Dropping Tables

To drop a table, you can use the `Schema::drop()` method within the `down()` method of your migration. Simply pass the table name as a parameter.

```php
return Schema::drop("users");
```

## List Migrations and Statuses

To show all migration files and their statuses, you can use the Nebula command-line interface (CLI). Run the following command:

```
./nebula migration-list
```

Example:
```bash
./nebula --migration-list
[PENDING] 1688141260_table_users.php
[PENDING] 1689820896_table_audit.php
```

## Running Migrations

To apply the defined migrations and update the database schema, you can use the Nebula command-line interface (CLI). Run the following command:

```
./nebula migration-up=<filename>.php
```

This command will run all pending migrations that haven't been applied yet.

## Rolling Back Migrations

If you need to undo a migration, you can use the rollback command:

```
./nebula migration-down=<filename>.php
```

This will undo a previous migration.

## Create Database and Run Migrations

To create a new database schema and run all migrations, you can use the Nebula command-line interface (CLI). Run the following command:

**Note**: This will drop the current database and run all migration files. Please be careful!

```
./nebula migration-fresh
```

## Conclusion

Migrations in the Nebula PHP Framework provide a structured and organized way to manage your database schema changes. By creating migration files, you can define the changes you want to make to your database tables and easily apply or roll back those changes using the provided CLI commands.

For more advanced usage and customization options, please refer to the <s>official Nebula documentation</s>.

If you have any questions or need further assistance, feel free to reach out!
