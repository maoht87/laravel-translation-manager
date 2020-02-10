<?php

namespace Omt\TranslationManager\Console;

use Illuminate\Console\Command;
use Omt\TranslationManager\Manager;

class ResetCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'translations:reset {tenant_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all translations from the database';

    /** @var \Barryvdh\TranslationManager\Manager */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenant_id = $this->argument('tenant_id');
        $this->manager->truncateTranslations($tenant_id);
        $this->info('All translations are deleted');
    }
}
