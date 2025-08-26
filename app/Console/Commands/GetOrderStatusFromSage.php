<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\OrderTrackingController;

class GetOrderStatusFromSage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:getorderstatusfromsage';

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
        Log::info("Cron for getting order status from sage is working fine!");
        $or= new OrderTrackingController();
		$or->getOrderTrackerFromSage();
        $this->info('GetOrderStatusFromSage Command Ran successfully!');
    }
}
