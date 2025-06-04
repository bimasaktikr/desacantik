<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TruncateAssignmentUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assignment-uploads:truncate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all assignment uploads and related data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->confirm('Are you sure you want to truncate all assignment uploads? This action cannot be undone.')) {
            try {
                $this->info('Starting truncate process...');

                // Disable foreign key checks
                Schema::disableForeignKeyConstraints();

                // Truncate the table
                DB::table('assignment_uploads')->truncate();

                // Re-enable foreign key checks
                Schema::enableForeignKeyConstraints();

                $this->info('Successfully truncated assignment uploads table.');
            } catch (\Exception $e) {
                $this->error('An error occurred while truncating the table: ' . $e->getMessage());
            }
        } else {
            $this->info('Operation cancelled.');
        }
    }
}
