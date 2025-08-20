<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\PushNewProductToSfa;
use App\Console\Commands\PushNewCustomersToSfa;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\PushNewCustomersToSfa::class,
        Commands\PushNewProductToSfa::class,
        Commands\GetDeliveryDetailsFromSage::class,
        Commands\PushDeliveryToSFA::class,
        Commands\PushOrderStatusToSFA::class,
        Commands\GetProductsFromSage::class,
        Commands\GetCustomerFromSage::class,
        Commands\FailedOrderSageResync::class,
        Commands\PushPromotion::class,
        Commands\GetPricelists::class,
        Commands\TruncatePricelist::class,

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('command:pushnewcustomerstosfa')->everyThreeMinutes()->withoutOverlapping();
        $schedule->command('command:pushnewproducttosfa')->everyFiveMinutes()->withoutOverlapping();
       //$schedule->command('command:pushdeliverytosfa')->everyTwoMinutes()->withoutOverlapping();
        $schedule->command('command:pushorderstatustosfa')->everyThreeMinutes()->withoutOverlapping();
        //$schedule->command('command:getdeliverydetailsfromsage')->everyTwoMinutes()->withoutOverlapping();
        $schedule->command('command:getproductsfromsage')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('command:getcustomersfromsage')->everyTenminutes()->withoutOverlapping();
        // $schedule->command('command:failedordersageresync')->hourly()->withoutOverlapping();
        $schedule->command('command:pushpromotiontosfa')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('command:getpromotiontosfa')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('command:pushorderresponsetosfa')->everyTwoMinutes()->withoutOverlapping();
        $schedule->command('command:getpricelistsfromsage')->hourly()->withoutOverlapping();
        $schedule->command('command:truncate_pricelist')->dailyAt('00:17')->withoutOverlapping();

    }
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
