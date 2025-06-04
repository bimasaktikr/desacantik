<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $email = $row['email'] ?? null;
            $name = $row['name'] ?? $row['nama'] ?? null;
            $nim = $row['nim'] ?? null;
            $gender = $row['gender'] ?? null;

            if (!$email || !$name || !$nim || !$gender) {
                Log::warning('Skipped row: missing required fields', $row->toArray());
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($nim),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]
            );
            Log::info($user->name . 'User Created');

            if (!$user->hasRole('mahasiswa')) {
                $user->assignRole('mahasiswa');
                Log::info($user->name . ' assigned role mahasiswa');
            }

            Student::firstOrCreate(
                ['nim' => $nim],
                [
                    'name' => $name,
                    'gender' => $gender,
                    'user_id' => $user->id,
                ]
            );

            Log::info($user->name . ' mahasiswa created');

        }
    }
}

