<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPdfPathToInvoicesTable extends Migration
{
    public function up()
    {
         Schema::table('invoices', function (Blueprint $table) {
              $table->string('pdf_path')->nullable()->after('sentdate');
         });
    }

    public function down()
    {
         Schema::table('invoices', function (Blueprint $table) {
              $table->dropColumn('pdf_path');
         });
    }
}
