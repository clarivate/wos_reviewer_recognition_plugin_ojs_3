<?php

namespace APP\plugins\generic\webOfScience\classes;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WOSMigration extends Migration {

    /**
     * Run migrations to create the legacy publons_reviews and publons_reviews_settings tables
     *
     * @return void
     */
    public function up() {
        Schema::create('publons_reviews', function (Blueprint $table) {
            $table->bigInteger('publons_reviews_id')->autoIncrement();
            $table->bigInteger('journal_id');
            $table->bigInteger('submission_id');
            $table->bigInteger('reviewer_id');
            $table->bigInteger('review_id');
            $table->string('title_en', 255)->nullable();
            $table->datetime('date_added');
        });

        Schema::create('publons_reviews_settings', function (Blueprint $table) {
            $table->bigInteger('publons_reviews_id');
            $table->string('locale', 5)->default('');
            $table->string('setting_name', 255);
            $table->text('setting_value')->nullable();
            $table->string('setting_type', 6);
            $table->index(['publons_reviews_id'], 'publons_reviews_settings_publons_reviews_id');
            $table->unique(['publons_reviews_id', 'locale', 'setting_name'], 'publons_reviews_settings_pkey');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void {
        Schema::drop('publons_reviews');
        Schema::drop('publons_reviews_settings');
    }

}
