<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            'Alahly Bank',
            'EG Post Account',
            'Kashier',
        ];

        foreach ($accounts as $name) {
            Account::firstOrCreate(['name' => $name], ['balance' => 0]);
        }
    }
}
