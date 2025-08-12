<?php

namespace App\Console\Commands;

use App\Http\Controllers\DeliveryController;
use Illuminate\Console\Command;

class GetDeliveryDetailsFromSage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:getdeliverydetailsfromsage';

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
        DeliveryController::selectDeliveryFromSage();
        $this->info('Get Tracker and Delivery Command Ran successfully!');
    }
}
