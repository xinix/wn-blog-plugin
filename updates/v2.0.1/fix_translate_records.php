<?php namespace Xinix\Blog\Updates;

use Db;
use Schema;
use Winter\Storm\Database\Updates\Migration;

class FixTranslateRecords extends Migration
{
    const MODELS = [
        'Category',
        'Post',
    ];

    public function up()
    {
        $this->fix_records('Winter', 'Xinix');
    }

    public function down()
    {
        $this->fix_records('Xinix', 'Winter');
    }

    public function fix_records($from, $to)
    {
        $tables = ['indexes', 'attributes'];

        if (Schema::hasTable('rainlab_translate_indexes')) {
            $tables = preg_filter('/^/', 'rainlab_translate_', $tables);
        } else if (Schema::hasTable('rainlab_translate_indexes')) {
            $tables = preg_filter('/^/', 'rainlab_translate_', $tables);
        } else {
            return;
        }

        foreach ($tables as $table) {
            foreach (self::MODELS as $model) {
                $fromModel = $from . '\\Blog\\Models\\' . $model;
                $toModel = $to . '\\Blog\\Models\\' . $model;
                Db::table($table)
                    ->where('model_type', $fromModel)
                    ->update(['model_type' => $toModel]);
            }
        }
    }
}
