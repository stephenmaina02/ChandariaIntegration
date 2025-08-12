<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ProductController;

class PushNewProductToSfa extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:pushnewproducttosfa';

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
        \Log::info("Cron for pushing product to SFA is working fine!");
        ProductController::pushProductToSFA();
        $this->info('PushNewProductToSfa Command Ran successfully!');
    }
}
