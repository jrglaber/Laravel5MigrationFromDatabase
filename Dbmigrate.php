<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use DB;

class Dbmigrate extends Model
{
    public static function generate()
    {
        $migrate = new SqlMigrations;
        //$migrate->ignore(['some_table_name', 'another_table_name']);
        $migrate->convert('database');
        $migrate->write();
    }
}

class SqlMigrations
{

    private static $ignore = array('migrations');
    private static $database = "";
    private static $migrations = false;
    private static $schema = array();
    private static $selects = array('column_name as Field', 'column_type as Type', 'is_nullable as Null', 'column_key as Key', 'column_default as Default', 'extra as Extra', 'data_type as Data_Type');
    private static $instance;
    private static $up = "";
    private static $down = "";

    private static function getTables()
    {
        return DB::select('SELECT table_name, create_time FROM information_schema.tables WHERE table_schema="' . self::$database . '" ORDER BY create_time ');
    }

    private static function getTableDescribes($table)
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', '=', self::$database)
            ->where('table_name', '=', $table)
            ->get(self::$selects);
    }

    private static function getForeignTables()
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', '=', self::$database)
            ->where('REFERENCED_TABLE_SCHEMA', '=', self::$database)
            ->select('TABLE_NAME')->distinct()
            ->get();
    }

    private static function getForeigns($table)
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', '=', self::$database)
            ->where('REFERENCED_TABLE_SCHEMA', '=', self::$database)
            ->where('TABLE_NAME', '=', $table)
            ->select('COLUMN_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME')
            ->get();
    }

    private static function compileSchema()
    {
        $upSchema = "";
        $downSchema = "";
        $schema = "";
        $newSchema = "";
        $count = 1;
        foreach (self::$schema as $name => $values) {
            if (in_array($name, self::$ignore)) {
                continue;
            }
            $upSchema .= "
//
// NOTE -- {$name}
// --------------------------------------------------

{$values['up']}";
            $downSchema .= "
{$values['down']}";
//        }

            $schema = "<?php

//
// NOTE Migration Created: " . date("Y-m-d H:i:s") . "
// --------------------------------------------------

use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Database\\Migrations\\Migration;

class Create" . str_replace('_', '', Str::title($name)) . "Table extends Migration {
//
// NOTE - Make changes to the database.
// --------------------------------------------------

public function up()
{
" . $upSchema . "
" . self::$up . "
}

//
// NOTE - Revert the changes to the database.
// --------------------------------------------------

public function down()
{
" . $downSchema . "
" . self::$down . "
}
}";

            $date = new \DateTime($values['create']);
            //$filename = date('Y_m_d_His') . "_create_" . $name . "_table.php";
            $filename = $date->format('Y_m_d_His') . $count . "_create_" . $name . "_table.php";

            file_put_contents(__DIR__ . "/../database/migrations/{$filename}", $schema);
            $schema = "";
            $upSchema = "";
            $downSchema = "";
            $count++;
        }
        return $schema;
    }

    public function up($up)
    {
        self::$up = $up;
        return self::$instance;
    }

    public function down($down)
    {
        self::$down = $down;
        return self::$instance;
    }

    public function ignore($tables)
    {
        self::$ignore = array_merge($tables, self::$ignore);
        return self::$instance;
    }

    public function migrations()
    {
        self::$migrations = true;
        return self::$instance;
    }

    public function write()
    {
        $schema = self::compileSchema();
//        $filename = date('Y_m_d_His') . "_create_" . self::$database . "_database.php";

//        file_put_contents(__DIR__ . "/../database/migrations/{$filename}", $schema);
    }

    public function get()
    {
        return self::compileSchema();
    }

    public function convert($database)
    {
        self::$instance = new self();
        self::$database = $database;
        $table_headers = array('Field', 'Type', 'Null', 'Key', 'Default', 'Extra');
        $tables = self::getTables();
        foreach ($tables as $key => $value) {
            if (in_array($value->table_name, self::$ignore)) {
                continue;
            }

            $down = "Schema::drop('{$value->table_name}');";
            $up = "Schema::create('{$value->table_name}', function(Blueprint $" . "table) {\n";
            $tableDescribes = self::getTableDescribes($value->table_name);
            foreach ($tableDescribes as $values) {
                $method = "";
                $para = strpos($values->Type, '(');
                $type = $para > -1 ? substr($values->Type, 0, $para) : $values->Type;
                $numbers = "";
                $nullable = $values->Null == "NO" ? "" : "->nullable()";
                $default = ($values->Default == "" || is_null($values->Default)) ? "" : "->default(\"{$values->Default}\")";
                $unsigned = strpos($values->Type, "unsigned") === false ? '' : '->unsigned()';
                $unique = $values->Key == 'UNI' ? "->unique()" : "";
                switch ($type) {
                    case 'int' :
                    case 'year' :
                        $method = 'unsignedInteger';
                        break;
                    case 'char' :
                    case 'varchar' :
                        $para = strpos($values->Type, '(');
                        $numbers = ", " . substr($values->Type, $para + 1, -1);
                        $method = 'string';
                        break;
                    case 'float' :
                        $method = 'float';
                        break;
                    case 'decimal' :
                        $para = strpos($values->Type, '(');
                        $numbers = ", " . substr($values->Type, $para + 1, -1);
                        $method = 'decimal';
                        $default = "->default(\"{$values->Default}\")";
                        break;
                    case 'tinyint' :
                    case 'smallint' :
                        $method = 'boolean';
                        break;
                    case 'date':
                        $method = 'date';
                        break;
                    case 'time':
                        $method = 'time';
                        break;
                    case 'timestamp' :
                        $method = 'timestamp';
                        break;
                    case 'datetime' :
                        $method = 'dateTime';
                        break;
                    case 'mediumtext' :
                        $method = 'mediumtext';
                        break;
                    case 'text' :
                    case 'longtext' :
                        $method = 'text';
                        break;
                }
                if ($values->Key == 'PRI') {
                    $method = 'increments';
                }
                if($method == 'timestamp'){
                    $up .= " $" . "table->{$method}('{$values->Field}'{$numbers});\n";
                }
                else{
                    $up .= " $" . "table->{$method}('{$values->Field}'{$numbers}){$nullable}{$default}{$unsigned}{$unique};\n";
                }

            }

            $foreign = self::getForeigns($value->table_name);
            foreach ($foreign as $k => $v) {
                $up .= " $" . "table->foreign('{$v->COLUMN_NAME}')->references('{$v->REFERENCED_COLUMN_NAME}')->on('{$v->REFERENCED_TABLE_NAME}');\n";
            }

            $up .= " });\n\n";
            self::$schema[$value->table_name] = array(
                'up' => $up,
                'down' => $down,
                'create' => $value->create_time,
            );
        }

        return self::$instance;
    }

}

