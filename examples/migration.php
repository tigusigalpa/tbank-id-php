<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tbank_id')->unique()->nullable()->after('id');
            $table->string('phone')->nullable()->after('email');
            $table->index('tbank_id');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tbank_id']);
            $table->dropColumn(['tbank_id', 'phone']);
        });
    }
};
