<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('slug')->nullable();
            $table->integer('tenant_id')->nullable();
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
        });

        Schema::create('posts_with_slug', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
        });

        Schema::create('slug_history', function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->morphs('sluggable');
            $table->timestamp('created_at')->nullable();

            $table->unique(['slug', 'sluggable_type']);
        });

        Schema::create('posts_translatable', function (Blueprint $table): void {
            $table->id();
            $table->json('title')->nullable();
            $table->json('slug')->nullable();
        });
    }
}
