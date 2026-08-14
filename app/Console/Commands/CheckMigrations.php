<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CheckMigrations extends Command
{
    protected $signature = 'migrations:check';
    protected $description = 'Check migration files against existing database tables';

    public function handle()
    {
        $migrationPath = database_path('migrations');
        $files = File::files($migrationPath);

        $this->info(str_pad('Migration', 60) . str_pad('Table', 35) . 'Status');
        $this->line(str_repeat('-', 110));

        foreach ($files as $file) {
            $content = File::get($file);

            preg_match_all("/Schema::create\\(['\"]([^'\"]+)['\"]/", $content, $matches);

            if (empty($matches[1])) {
                continue;
            }

            foreach ($matches[1] as $table) {
                $exists = Schema::hasTable($table);

                $this->line(
                    str_pad($file->getFilename(), 60) .
                    str_pad($table, 35) .
                    ($exists ? '<info>EXISTS</info>' : '<error>MISSING</error>')
                );
            }
        }

        return Command::SUCCESS;
    }
}