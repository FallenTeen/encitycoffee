<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Http;

class DeployCPanel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:cpanel 
                            {--skip-build : Skip npm build process}
                            {--manual : Skip webhook, show manual instructions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy Laravel application to cPanel (Fully Automated)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting deployment process...');
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

        // Step 4: Trigger deployment on server via webhook
        if (!$this->option('manual')) {
            if (!$this->triggerWebhook()) {
                $this->newLine();
                $this->warn('Webhook failed. Please deploy manually:');
                $this->showManualInstructions();
                return Command::FAILURE;
            }
        } else {
            $this->showManualInstructions();
        }

        $this->newLine();
        $this->info('Deployment completed successfully!');
        $this->info('Visit: ' . config('app.url'));
        
        return Command::SUCCESS;
    }

    /**
     * Check git status
     */
    protected function checkGitStatus(): bool
    {
        $this->info('Checking git status...');
        
        $result = Process::run('git status --porcelain');
        
        if ($result->failed()) {
            $this->error('Failed to check git status');
            return false;
        }

        if (empty($result->output())) {
            $this->warn('No changes detected. Nothing to deploy.');
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
        $this->info('Running npm build...');
        $this->warn('Make sure to close VSCode, browser dev server, and all terminals first!');
        $this->newLine();
        
        // Kill any running dev servers
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            Process::run('taskkill /F /IM node.exe 2>nul');
        }
        
        $result = Process::timeout(300)->run('npm run build');
        
        if ($result->failed()) {
            $this->error(' npm build failed');
            $this->error($result->errorOutput());
            $this->newLine();
            $this->warn('  Tips to fix:');
            $this->line('   1. Close VSCode completely');
            $this->line('   2. Close all browser tabs with localhost:5173');
            $this->line('   3. Close all terminals/cmd windows');
            $this->line('   4. Try: npm run build --force');
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
        $this->info('📤 Pushing to git repository...');

        // Git add
        $result = Process::run('git add .');
        if ($result->failed()) {
            $this->error('❌ Git add failed');
            return false;
        }

        // Check if there are changes to commit
        $result = Process::run('git diff --cached --quiet');
        $hasChanges = $result->failed(); // exit code 1 means there are changes

        if ($hasChanges) {
            $commitMessage = $this->ask('Enter commit message', 'Deploy: ' . date('Y-m-d H:i:s'));
            
            // Git commit
            $result = Process::run("git commit -m \"{$commitMessage}\"");
            if ($result->failed()) {
                $this->error('❌ Git commit failed');
                $this->error($result->errorOutput());
                return false;
            }
            
            $this->info('✓ Committed changes');
        } else {
            $this->warn('⚠️  No changes to commit, pushing existing commits...');
        }

        // Git push
        $gitBranch = config('deploy.git_branch', 'main');
        $result = Process::timeout(120)->run("git push origin {$gitBranch}");
        if ($result->failed()) {
            // Check if it's just "already up to date"
            if (str_contains($result->errorOutput(), 'Everything up-to-date')) {
                $this->info('✓ Repository already up to date');
            } else {
                $this->error('❌ Git push failed');
                $this->error($result->errorOutput());
                return false;
            }
        } else {
            $this->info('✓ Pushed to repository');
        }

        return true;
    }

    /**
     * Trigger deployment webhook on server
     */
    protected function triggerWebhook(): bool
    {
        $webhookUrl = config('deploy.webhook_url');
        $webhookSecret = config('deploy.webhook_secret');

        if (!$webhookUrl || !$webhookSecret) {
            $this->warn('⚠️  Webhook not configured. Please add DEPLOY_WEBHOOK_URL and DEPLOY_WEBHOOK_SECRET to .env');
            return false;
        }

        $this->info('🌐 Triggering deployment on server...');

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'X-Deploy-Secret' => $webhookSecret,
                ])
                ->post($webhookUrl);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['output'])) {
                    $this->newLine();
                    $this->line($data['output']);
                }
                
                $this->info('✓ Server deployment completed');
                return true;
            } else {
                $this->error('❌ Webhook request failed with status: ' . $response->status());
                return false;
            }
        } catch (\Exception $e) {
            $this->error('❌ Webhook error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Show manual deployment instructions
     */
    protected function showManualInstructions(): void
    {
        $this->newLine();
        $this->warn('📌 Manual deployment steps:');
        $this->line('   1. Open cPanel Terminal: https://cikapundung.iixcp.rumahweb.net:2083');
        $this->line('   2. Run: cd /home/bhij4149/encitycoffee && ./deploy.sh');
        $this->newLine();
    }
}