<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeeImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $name = $row['name'] ?? $row['nama'] ?? null;
            $nip = $row['nip'] ?? null;
            $position = $row['position'] ?? $row['jabatan'] ?? null;
            $email = $row['email'] ?? null;

            if (!$name || !$nip || !$position || !$email) {
                Log::warning('Skipped row: missing required fields', $row->toArray());
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($nip),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]
            );
            Log::info($user->name . ' User Created');

            if (!$user->hasRole('employee')) {
                $user->assignRole('employee');
                Log::info($user->name . ' assigned role employee');
            }

            Employee::firstOrCreate(
                ['nip' => $nip],
                [
                    'name' => $name,
                    'position' => $position,
                    'user_id' => $user->id,
                ]
            );

            Log::info($user->name . ' employee created');
        }
    }
}
