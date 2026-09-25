<?php

use App\Support\ErpRoles;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ErpRoles::purgeRemovedRoles();
    }

    public function down(): void
    {
        // Retired roles are not restored automatically.
    }
};
