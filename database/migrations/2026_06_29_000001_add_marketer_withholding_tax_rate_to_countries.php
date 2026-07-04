<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            // Withholding tax on affiliate/marketer commission income — a different
            // tax concept from vat_rate (which is VAT on the sale itself).
            // Statutory rate varies per country for freelancer/affiliate income.
            // Default 0 means no withholding deducted until explicitly configured.
            $table->decimal('marketer_withholding_tax_rate', 5, 2)->default(0.00)->after('vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('marketer_withholding_tax_rate');
        });
    }
};
