<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class DeployCPanel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:cpanel 
                            {--skip-build : Skip npm build process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy Laravel application to cPanel (Build & Push only)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting deployment process...');
        $this->newLine();

        // Step 1: Check git status
        if (!$this->checkGitStatus()) {
            return Command::FAILURE;
        }

        // Step 2: Run npm build
        if (!$this->option('skip-build')) {
            if (!$this->runNpmBuild()) {
                return Command::FAILURE;
            }
        }

        // Step 3: Git add, commit, and push
        if (!$this->gitPush()) {
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('✅ Code pushed to repository successfully!');
        $this->newLine();
        $this->warn('📌 Next steps:');
        $this->line('   1. Open cPanel Terminal: https://cikapundung.iixcp.rumahweb.net:2083');
        $this->line('   2. Run: cd /home/bhij4149/encitycoffee && ./deploy.sh');
        $this->newLine();
        $this->info('🌐 Website: ' . config('app.url'));
        
        return Command::SUCCESS;
    }

    /**
     * Check git status
     */
    protected function checkGitStatus(): bool
    {
        $this->info('📋 Checking git status...');
        
        $result = Process::run('git status --porcelain');
        
        if ($result->failed()) {
            $this->error('❌ Failed to check git status');
            return false;
        }

        if (empty($result->output())) {
            $this->warn('⚠️  No changes detected. Nothing to deploy.');
            return $this->confirm('Do you want to continue anyway?');
        }

        $this->info('✓ Changes detected');
        return true;
    }

    /**
     * Run npm build
     */
    protected function runNpmBuild(): bool
    {
        $this->info('🔨 Running npm build...');
        
        $result = Process::timeout(300)->run('npm run build');
        
        if ($result->failed()) {
            $this->error('❌ npm build failed');
            $this->error($result->errorOutput());
            return false;
        }

        $this->info('✓ npm build completed');
        return true;
    }

    /**
     * Git add, commit, and push
     */
    protected function gitPush(): bool
    {
        $commitMessage = $this->ask('Enter commit message', 'Deploy: ' . date('Y-m-d H:i:s'));

        $this->info('📤 Pushing to git repository...');

        // Git add
        $result = Process::run('git add .');
        if ($result->failed()) {
            $this->error('❌ Git add failed');
            return false;
        }

        // Git commit
        $result = Process::run("git commit -m \"{$commitMessage}\"");
        if ($result->failed() && !str_contains($result->errorOutput(), 'nothing to commit')) {
            $this->error('❌ Git commit failed');
            $this->error($result->errorOutput());
            return false;
        }

        // Git push
        $gitBranch = config('deploy.git_branch', 'main');
        $result = Process::timeout(120)->run("git push origin {$gitBranch}");
        if ($result->failed()) {
            $this->error('❌ Git push failed');
            $this->error($result->errorOutput());
            return false;
        }

        $this->info('✓ Pushed to repository');
        return true;
    }
}