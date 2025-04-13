<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateUsersTable {
    public function up() {
        if (!Capsule::schema()->hasTable('users')) {
            Capsule::schema()->create('users', function ($table) {
                $table->id();
                $table->string('srcode')->unique();
                $table->string('email')->unique();
                $table->string('password');
                $table->enum('usertype', ['admin', 'user']);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
            echo "Users table created successfully!\n";
        } else {
            echo "Users table already exists. Skipping creation.\n";
        }
    }

    public function down() {
        Capsule::schema()->dropIfExists('users');
        echo "Users table dropped successfully!\n";
    }
} 