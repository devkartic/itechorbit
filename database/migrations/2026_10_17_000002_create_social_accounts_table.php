<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $legacy = DB::table('users')->whereNotNull('provider_id')->where('provider_id', '!=', '');
        if ((clone $legacy)->select('provider_id')->groupBy('provider_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate legacy Google identities must be resolved before migrating social accounts.');
        }

        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $identity = $table->string('provider_user_id');
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                $identity->collation('utf8mb4_bin');
            }
            $table->timestamps();
            $table->unique(['provider', 'provider_user_id']);
            $table->unique(['user_id', 'provider']);
        });

        $legacy->orderBy('id')->chunkById(500, function ($users) {
            DB::table('social_accounts')->insert($users->map(fn ($user) => [
                'user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => $user->provider_id,
                'created_at' => now(), 'updated_at' => now(),
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
