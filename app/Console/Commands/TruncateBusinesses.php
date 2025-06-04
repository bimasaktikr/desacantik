<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use App\Models\Business;

class TruncateBusinesses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'businesses:truncate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all businesses and related data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->confirm('Are you sure you want to truncate all businesses? This will delete all business data!')) {
            $this->info('Truncating businesses...');

            // Disable foreign key checks
            Schema::disableForeignKeyConstraints();

            // Truncate the businesses table
            Business::truncate();

            // Re-enable foreign key checks
            Schema::enableForeignKeyConstraints();

            $this->info('Businesses table has been truncated successfully!');
        } else {
            $this->info('Operation cancelled.');
        }
    }
}
