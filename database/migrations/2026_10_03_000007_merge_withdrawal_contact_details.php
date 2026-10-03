<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $decrypt = static function (?string $value): ?string {
            if ($value === null || $value === '') {
                return $value;
            }

            try {
                return Crypt::decryptString($value);
            } catch (DecryptException) {
                return $value;
            }
        };

        DB::table('withdrawal_requests')
            ->whereNotNull('legal_name')
            ->orWhereNotNull('phone_number')
            ->orderBy('id')
            ->chunkById(100, function ($requests) use ($decrypt) {
                foreach ($requests as $request) {
                    $details = array_filter([
                        $request->legal_name ? 'Legal name: ' . $decrypt($request->legal_name) : null,
                        $request->phone_number ? 'Phone number: ' . $decrypt($request->phone_number) : null,
                        $request->payout_details ? 'Payout destination: ' . $decrypt($request->payout_details) : null,
                    ]);

                    DB::table('withdrawal_requests')->where('id', $request->id)->update([
                        'payout_details' => Crypt::encryptString(implode("\n", $details)),
                    ]);
                }
            });

        Schema::table('withdrawal_requests', function ($table) {
            $table->dropColumn(['legal_name', 'phone_number']);
        });
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', function ($table) {
            $table->string('legal_name', 160)->nullable();
            $table->string('phone_number', 32)->nullable();
        });
    }
};
