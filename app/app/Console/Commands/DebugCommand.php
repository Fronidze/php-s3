<?php

namespace App\Console\Commands;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Console\Command;

class DebugCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:run';

    protected ?Uri $uri = null;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug something code';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info(sprintf(
            "path: %s\nquery: %s\nencode: %s",
            $this->uri()->getPath(),
            $this->uri()->getQuery(),
            rawurlencode($this->uri()->getQuery())
        ));
    }

    protected function uri(): Uri
    {
        if ($this->uri instanceof Uri === false) {
            $this->uri = new Uri('/fronidze?acl&username=маркелов');
        }
        return $this->uri;
    }
}
