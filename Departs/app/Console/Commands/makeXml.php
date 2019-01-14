<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class makeXml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xml:make';

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
     * @return mixed
     */
    public function handle()
    {
        $xml = simplexml_load_file('https://blogos.com/feed/tag/%E7%99%BE%E8%B2%A8%E5%BA%97/');
        echo "<pre>";print_r($xml);echo "</pre>";
    }
}
