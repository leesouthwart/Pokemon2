<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;

class AssignBatchesToUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-batches-to-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign all existing batches to a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = 'leesouthwart@gmail.com';
        
        // Find the user with the specified email
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email '{$email}' not found!");
            return 1;
        }
        
        $this->info("Found user: {$user->name} (ID: {$user->id})");
        
        // Count batches that need to be assigned
        $unassignedBatches = Batch::where('user_id', 0)->orWhereNull('user_id')->count();
        
        if ($unassignedBatches === 0) {
            $this->info("No unassigned batches found.");
            return 0;
        }
        
        $this->info("Found {$unassignedBatches} batches to assign.");
        
        // Assign all batches to the user
        $updated = Batch::where('user_id', 0)->orWhereNull('user_id')->update(['user_id' => $user->id]);
        
        $this->info("Successfully assigned {$updated} batches to user {$user->name}.");
        
        return 0;
    }
}
