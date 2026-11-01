<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function next(string $prefix): string
    {
        return DB::transaction(function () use ($prefix) {
            $year = (int) now()->year;

            $seq = DB::table('document_sequences')
                ->where('prefix', $prefix)->where('year', $year)
                ->lockForUpdate()->first();

            if (! $seq) {
                DB::table('document_sequences')->insert([
                    'prefix' => $prefix, 'year' => $year, 'last_number' => 1,
                ]);
                $n = 1;
            } else {
                $n = $seq->last_number + 1;
                DB::table('document_sequences')->where('id', $seq->id)->update(['last_number' => $n]);
            }

            return sprintf('%s-%d-%04d', $prefix, $year, $n);
        });
    }
}