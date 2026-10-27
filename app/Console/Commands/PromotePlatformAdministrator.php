<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromotePlatformAdministrator extends Command
{
    protected $signature = 'app:promote-platform-administrator {email}';

    protected $description = 'Nadaje istniejącemu kontu uprawnienia administratora platformy';

    public function handle(): int
    {
        $user = User::query()->where('email', strtolower($this->argument('email')))->first();
        if (! $user || $user->organization_id !== null) {
            $this->error('Nie znaleziono konta platformowego bez przypisanej organizacji.');

            return self::FAILURE;
        }
        $user->is_super_admin = true;
        $user->save();
        $this->info('Nadano uprawnienia administratora platformy.');

        return self::SUCCESS;
    }
}
