<?php
use SLiMS\Table\Schema;
use SLiMS\Table\Blueprint;
use SLiMS\Migration\Migration;

class MembuatTabel extends Migration
{
    function up()
    {
        Schema::create('kuesioner', function (Blueprint $table) {
            $table->autoIncrement('id');
            $table->string('npm', 255)->notNull();
            $table->string('nama', 255)->notNull();
            $table->string('email', 255)->notNull();
            $table->string('judul', 255)->notNull();
            $table->text('pertanyaan')->notNull();
            $table->text('flyer')->nullable();
            $table->text('template_sertifikat')->nullable();
            $table->text('config_sertifikat')->nullable();
            $table->timestamps();
        });
    }

    function down()
    {
        Schema::drop('kuesioner');
    }
}