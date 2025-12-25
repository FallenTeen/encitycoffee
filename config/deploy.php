<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | SSH Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your cPanel SSH connection details
    |
    */
    
    'ssh_host' => env('DEPLOY_SSH_HOST', 'your-server.com'),
    'ssh_user' => env('DEPLOY_SSH_USER', 'bhij4149'),
    'ssh_port' => env('DEPLOY_SSH_PORT', 22),
    
    /*
    |--------------------------------------------------------------------------
    | cPanel Paths
    |--------------------------------------------------------------------------
    |
    | Define the paths on your cPanel server
    |
    */
    
    'app_path' => env('DEPLOY_APP_PATH', '/home/bhij4149/encitycoffee'),
    'public_path' => env('DEPLOY_PUBLIC_PATH', '/home/bhij4149/public_html/encity.bhinneka.space'),
    
    /*
    |--------------------------------------------------------------------------
    | Composer Path
    |--------------------------------------------------------------------------
    |
    | Path to composer executable on cPanel server
    |
    */
    
    'composer_path' => env('DEPLOY_COMPOSER_PATH', '~/bin/composer'),
    
    /*
    |--------------------------------------------------------------------------
    | Git Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your git repository details
    |
    */
    
    'git_branch' => env('DEPLOY_GIT_BRANCH', 'main'),
    
];