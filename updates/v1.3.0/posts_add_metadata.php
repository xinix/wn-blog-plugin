<?php namespace Xinix\Blog\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;
use Xinix\Blog\Models\Category as CategoryModel;

class PostsAddMetadata extends Migration
{

    public function up()
    {
        if (Schema::hasColumn('rainlab_blog_posts', 'metadata')) {
            return;
        }

        Schema::table('rainlab_blog_posts', function($table)
        {
            $table->mediumText('metadata')->nullable();
        });
    }

    public function down()
    {
        if (Schema::hasColumn('rainlab_blog_posts', 'metadata')) {
            Schema::table('rainlab_blog_posts', function ($table) {
                $table->dropColumn('metadata');
            });
        }
    }

}
