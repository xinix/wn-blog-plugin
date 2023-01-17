<?php namespace Xinix\Blog\Updates;

use Db;
use Schema;
use Winter\Storm\Database\Updates\Migration;

class RenameTables extends Migration
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

            if (Schema::hasTable($from) && !Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }

        Db::table('system_files')->where('attachment_type', 'RainLab\Blog\Models\Post')->update(['attachment_type' => 'Xinix\Blog\Models\Post']);
        Db::table('system_settings')->where('item', 'rainlab_blog_settings')->update(['item' => 'xinix_blog_settings']);
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            $from = 'xinix_blog_' . $table;
            $to = 'rainlab_blog_' . $table;

            if (Schema::hasTable($from) && !Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }

        Db::table('system_files')->where('attachment_type', 'Xinix\Blog\Models\Post')->update(['attachment_type' => 'RainLab\Blog\Models\Post']);
        Db::table('system_settings')->where('item', 'xinix_blog_settings')->update(['item' => 'rainlab_blog_settings']);
    }
}
