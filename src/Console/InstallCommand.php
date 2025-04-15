<?php

namespace Laravel\Wayfinder\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'wayfinder:install')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wayfinder:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Wayfinder resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Wayfinder Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'wayfinder-config']);
    }
}
