<?php namespace Xinix\Blog\Updates;

use Db;
use Schema;
use Winter\Storm\Database\Updates\Migration;

class RenameIndexes extends Migration
{
    const TABLES = [
        'categories',
        'posts',
        'posts_categories'
    ];

    public function up()
    {
        foreach (self::TABLES as $table) {
            $from = 'rainlab_blog_' . $table;
            $to = 'xinix_blog_' . $table;

            $this->updateIndexNames($from, $to, $to);
        }
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            $from = 'xinix_blog_' . $table;
            $to = 'rainlab_blog_' . $table;

            $this->updateIndexNames($from, $to, $from);
        }
    }

    public function updateIndexNames($from, $to, $table)
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();

        $table = $sm->listTableDetails($table);

        foreach ($table->getIndexes() as $index) {
            if ($index->isPrimary()) {
                continue;
            }

            $old = $index->getName();
            $new = str_replace($from, $to, $old);

            $table->renameIndex($old, $new);
        }
    }
}
