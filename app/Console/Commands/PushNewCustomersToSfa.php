<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use App\Http\Controllers\CustomerController;

class PushNewCustomersToSfa extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:pushnewcustomerstosfa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \Log::info("Cron for pushing customer to SFA is working fine!");
        CustomerController::pushToSfa();
        $this->info('PushNewCustomersToSfa Command Ran successfully!');
    }
}
