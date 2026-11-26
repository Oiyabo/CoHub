<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SafeSchema
{
    private static array $tableCache = [];

    private static array $columnCache = [];

    public static function flush(): void
    {
        self::$tableCache = [];
        self::$columnCache = [];
    }

    public static function hasTable(string $table): bool
    {
        $key = self::connectionKey() . '|' . $table;

        if (! array_key_exists($key, self::$tableCache)) {
            try {
                self::$tableCache[$key] = Schema::hasTable($table);
            } catch (\Throwable) {
                self::$tableCache[$key] = false;
            }
        }

        return self::$tableCache[$key];
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = self::connectionKey() . '|' . $table . '.' . $column;

        if (! array_key_exists($key, self::$columnCache)) {
            try {
                self::$columnCache[$key] = self::hasTable($table) && Schema::hasColumn($table, $column);
            } catch (\Throwable) {
                self::$columnCache[$key] = false;
            }
        }

        return self::$columnCache[$key];
    }

    private static function connectionKey(): string
    {
        try {
            $appId = function_exists('app') ? spl_object_id(app()) : 0;
            $connection = DB::connection();

            return $appId . ':' . $connection->getName() . ':' . $connection->getDatabaseName();
        } catch (\Throwable) {
            return 'default';
        }
    }
}
