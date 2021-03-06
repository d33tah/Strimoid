<?php namespace Strimoid\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Strimoid\Console\Commands\AddModerator;
use Strimoid\Console\Commands\ChangePassword;
use Strimoid\Console\Commands\FacebookPost;
use Strimoid\Console\Commands\TwitterPost;
use Strimoid\Console\Commands\UpdateStats;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        AddModerator::class,
        ChangePassword::class,
        FacebookPost::class,
        TwitterPost::class,
        UpdateStats::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('lara:updatestats')->dailyAt('03:33');
        $schedule->command('lara:fbpost')->dailyAt('20:00');
        $schedule->command('lara:twitterpost')->dailyAt('20:05');
    }
}
