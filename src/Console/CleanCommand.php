<?php

namespace Omt\TranslationManager\Console;

use Omt\TranslationManager\Manager;
use Illuminate\Console\Command;

class CleanCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'omt_translations:clean {tenant_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean empty translations';

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
        $this->manager->cleanTranslations($tenant_id);
        $this->info('Done cleaning translations');
    }
}
