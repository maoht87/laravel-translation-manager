<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOmtTranslationsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('omt_translations', function (Blueprint $table) {
            $table->collation = 'utf8mb4_bin';
            $table->bigIncrements('id');
            $table->integer('tenant_id')->default(0);
            $table->integer('status')->default(0);
            $table->string('locale');
            $table->string('group');
            $table->string('key');
            $table->longText('value')->nullable();
            $table->bigInteger('created_by')->default(-10);
            $table->bigInteger('updated_by')->default(-10);
            $table->timestamps();

            $table->index(['tenant_id', 'locale', 'group', 'key'], 'index_data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('omt_translations');
    }

}
