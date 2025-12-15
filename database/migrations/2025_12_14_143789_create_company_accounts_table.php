<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('company_accounts', function (Blueprint $table) {
            $table->string('name')->unique();
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_accounts');
    }
}
