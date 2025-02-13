<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NewsService;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch';
    protected $description = 'Fetch news from different sources';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        app(NewsService::class)->fetchNews();
        $this->info('News fetched successfully.');
    }
}
