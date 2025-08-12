<?php

namespace App\Console\Commands;

use App\Http\Controllers\DeliveryController;
use Illuminate\Console\Command;

class PushDeliveryToSFA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:pushdeliverytosfa';

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
        \Log::info("Cron for pushing delivery to SFA is working fine!");
        DeliveryController::pushDeliveryToSfa();
        $this->info('PushDeliveryToSFA Command Ran successfully!');

        // return 0
    }
}
