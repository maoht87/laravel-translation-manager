<?php

namespace Omt\TranslationManager\Console;

use Omt\TranslationManager\Manager;
use Illuminate\Console\Command;

class FindCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'translations:find {tenant_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find translations in php/twig files';

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
        $counter = $this->manager->findTranslations($tenant_id, null);
        $this->info('Done importing, processed '.$counter.' items!');
    }
}
