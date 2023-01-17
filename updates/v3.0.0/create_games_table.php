<?php namespace Xinix\Blog\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class CreateGamesTable extends Migration
{

    public function up()
    {
        Schema::create('xinix_blog_games', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->index();
            $table->integer('bgg_id')->nullable();
            $table->text('image')->nullable();
            $table->timestamps();
        });

        Schema::create('xinix_blog_posts_games', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('post_id')->unsigned();
            $table->integer('game_id')->unsigned();
            $table->primary(['post_id', 'game_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('xinix_blog_games');
        Schema::dropIfExists('xinix_blog_posts_games');
    }

}
