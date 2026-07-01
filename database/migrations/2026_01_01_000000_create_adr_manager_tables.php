<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adr_records', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedInteger('sequence_number')->index();
            $table->string('title');
            $table->string('status')->index();
            $table->string('author')->nullable();
            $table->json('metadata');
            $table->text('content_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('adr_relations', function (Blueprint $table): void {
            $table->id();
            $table->string('parent_id');
            $table->string('child_id');
            $table->string('relation_type');
            $table->timestamps();

            $table->unique(['parent_id', 'child_id', 'relation_type']);
            $table->index('child_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adr_relations');
        Schema::dropIfExists('adr_records');
    }
};
