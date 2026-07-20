<?php

namespace App\Console\Commands;

use App\Models\Produk;
use App\Models\StokEtalase;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixLegacyProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'produk:fix-legacy 
                            {--dry-run : Show what would be done without making changes}
                            {--assign-to= : Assign all legacy products to a specific cabang_id}
                            {--by-stok : Assign legacy products based on their first stok_etalase}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix legacy products by assigning cabang_id based on stok_etalase or specified cabang';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting legacy products fix...');
        
        $dryRun = $this->option('dry-run');
        $assignTo = $this->option('assign-to');
        $byStok = $this->option('by-stok');
        
        // Get all products without cabang_id
        $legacyProducts = Produk::whereNull('cabang_id')->with(['stokEtalase.cabang'])->get();
        
        $this->info("Found {$legacyProducts->count()} legacy products without cabang_id.");
        
        if ($legacyProducts->isEmpty()) {
            $this->info('No legacy products to fix. All products already have cabang_id.');
            return Command::SUCCESS;
        }
        
        if ($assignTo) {
            // Assign all legacy products to specific cabang
            return $this->assignAllToCabang($assignTo, $legacyProducts, $dryRun);
        }
        
        if ($byStok) {
            // Assign based on first stok_etalase
            return $this->assignByStokEtalase($legacyProducts, $dryRun);
        }
        
        // Interactive mode
        return $this->interactiveMode($legacyProducts, $dryRun);
    }
    
    private function assignAllToCabang(string $cabangId, $products, bool $dryRun): int
    {
        if (!$dryRun) {
            $updated = Produk::whereNull('cabang_id')
                ->update(['cabang_id' => $cabangId]);
            
            $this->info("Updated {$updated} products with cabang_id = {$cabangId}");
        } else {
            $this->info("[DRY RUN] Would update {$products->count()} products with cabang_id = {$cabangId}");
        }
        
        return Command::SUCCESS;
    }
    
    private function assignByStokEtalase($products, bool $dryRun): int
    {
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();
        
        $assigned = 0;
        $unassigned = 0;
        
        foreach ($products as $produk) {
            $firstStok = $produk->stokEtalase->first();
            
            if ($firstStok) {
                if (!$dryRun) {
                    $produk->update(['cabang_id' => $firstStok->cabang_id]);
                    Log::info('Legacy product assigned to cabang', [
                        'produk_id' => $produk->id,
                        'produk_nama' => $produk->nama,
                        'assigned_cabang_id' => $firstStok->cabang_id,
                        'cabang_nama' => $firstStok->cabang?->nama,
                    ]);
                }
                $assigned++;
            } else {
                $unassigned++;
                $this->newLine();
                $this->warn("Product ID {$produk->id} ({$produk->nama}) has no stok_etalase - cannot auto-assign");
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Summary:");
        $this->info("  - Assigned: {$assigned} products");
        $this->info("  - Unassigned (no stok_etalase): {$unassigned} products");
        
        if ($dryRun) {
            $this->warn("[DRY RUN] No actual changes were made.");
        }
        
        return Command::SUCCESS;
    }
    
    private function interactiveMode($products, bool $dryRun): int
    {
        // List all cabang
        $cabangs = DB::table('cabang')->select('id', 'kode', 'nama')->get();
        
        $this->info("\nAvailable cabang:");
        foreach ($cabangs as $cabang) {
            $this->line("  [{$cabang->id}] {$cabang->nama} ({$cabang->kode})");
        }
        
        $this->newLine();
        $choice = $this->choice(
            'How would you like to assign legacy products?',
            [
                '1' => 'Assign by first stok_etalase (recommended)',
                '2' => 'Assign all to a specific cabang',
                '3' => 'List products without stok_etalase only',
                '4' => 'Exit',
            ],
            '1'
        );
        
        switch ($choice) {
            case '1':
                return $this->assignByStokEtalase($products, $dryRun);
            case '2':
                $cabangId = $this->ask('Enter cabang_id to assign all products to:');
                return $this->assignAllToCabang($cabangId, $products, $dryRun);
            case '3':
                $this->info("\nProducts without stok_etalase (cannot auto-assign):");
                foreach ($products as $produk) {
                    if ($produk->stokEtalase->isEmpty()) {
                        $this->line("  [{$produk->id}] {$produk->nama} (SKU: {$produk->sku})");
                    }
                }
                return Command::SUCCESS;
            default:
                $this->info('Exiting without changes.');
                return Command::SUCCESS;
        }
    }
}
