<?php namespace Skripteria\Snowflake\Updates;

use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

class CreateElementsTable extends Migration
{
    public function up()
    {
        Schema::create('skripteria_snowflake_elements', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
            $table->integer('page_id')->nullable()->default(1);
            $table->integer('type_id')->nullable();
            $table->string('desc', 255)->nullable();
            $table->text('content')->nullable();
            $table->boolean('in_use')->default(1);
            $table->string('alt')->nullable();
            $table->string('cms_key')->nullable();
            $table->integer('order')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('skripteria_snowflake_elements');
    }
}
