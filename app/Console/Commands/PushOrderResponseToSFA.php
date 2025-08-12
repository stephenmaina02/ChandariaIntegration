<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\OrderTrackingController;

class PushOrderResponseToSFA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:pushorderresponsetosfa';

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
        //Log::info("Cron for pushing order response to SFA is working fine!");
        $or= new OrderTrackingController();
		$or->pushResponseStatus();
        $this->info('PushOrderResponseToSFA Command Ran successfully!');
    }
}
