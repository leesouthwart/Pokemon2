<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ResetPsaApiExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psa:reset-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the PSA API expired cache key';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Cache::put('psa_api_expired', false);
        $this->info('PSA API expired cache key has been reset.');
    }
} 