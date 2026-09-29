<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Models\DemandForecast;

class RunDemandForecast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ml:run-demand-forecast {--train : Train the model before predicting} {--store_id= : Train/predict for a specific store ID only} {--exclude_store_id= : Train/predict for all stores EXCEPT this ID} {--demo : Automatically resolve and run for the ML demo store}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the Python Random Forest demand model to generate forecasts.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pythonScriptPath = base_path('../ml/random_forest_demand.py');
        $pythonExecutable = 'python'; // Assumes python is in the PATH

        $baseArgs = [$pythonExecutable, $pythonScriptPath, '--output=json'];
        $isDemoRun = $this->option('demo');
        $resolvedStoreId = $this->option('store_id');

        if ($isDemoRun) {
            $demoStore = \App\Models\Store::where('slug', 'ml-demo-store')->first();
            if ($demoStore) {
                $resolvedStoreId = $demoStore->store_id;
                $this->info("Resolved ML Demo Store ID: {$resolvedStoreId}");
            } else {
                $this->error("Demo store 'ml-demo-store' not found. Have you run ml:demo-seed?");
                return 1;
            }
        }

        if ($resolvedStoreId) {
            $baseArgs[] = '--store_id=' . $resolvedStoreId;
        }

        if ($this->option('exclude_store_id')) {
            $baseArgs[] = '--exclude_store_id=' . $this->option('exclude_store_id');
        } elseif (!$resolvedStoreId) {
            // Auto-exclude demo store in production runs if not specifically targeting a store
            $demoStore = \App\Models\Store::where('slug', 'ml-demo-store')->first();
            if ($demoStore) {
                $baseArgs[] = '--exclude_store_id=' . $demoStore->store_id;
                $this->info("Auto-excluding ML demo store (ID: {$demoStore->store_id}) from production forecast.");
            }
        }

        if ($this->option('train')) {
            $this->info('Training model...');
            $trainArgs = array_merge($baseArgs, ['--mode=train']);
            $trainProcess = new Process($trainArgs);
            $trainProcess->setWorkingDirectory(base_path('../')); // Run from root Tindahan-Ecommerce dir
            $trainProcess->setTimeout(300);
            $trainProcess->run();

            if (!$trainProcess->isSuccessful()) {
                $this->error('Model training failed:');
                $this->error($trainProcess->getErrorOutput());
                return 1;
            }

            $trainOutput = json_decode($trainProcess->getOutput(), true);
            if (!$trainOutput || ($trainOutput['status'] ?? '') !== 'success') {
                $this->error('Failed to parse training output or training reported error.');
                $this->error($trainProcess->getOutput());
                return 1;
            }
            $this->info('Model trained successfully. Metrics: ' . json_encode($trainOutput['model_metrics'] ?? []));
            if (isset($trainOutput['data_sufficiency'])) {
                $rows = $trainOutput['training_rows'] ?? 0;
                $dates = $trainOutput['distinct_dates'] ?? 0;
                $this->info("Data sufficiency: {$trainOutput['data_sufficiency']} (Rows: {$rows}, Distinct Dates: {$dates})");
            }
        }

        $this->info('Generating forecasts...');
        $predictArgs = array_merge($baseArgs, ['--mode=predict']);
        $predictProcess = new Process($predictArgs);
        $predictProcess->setWorkingDirectory(base_path('../'));
        $predictProcess->setTimeout(120);
        $predictProcess->run();

        if (!$predictProcess->isSuccessful()) {
            $this->error('Prediction failed:');
            $this->error($predictProcess->getErrorOutput());
            return 1;
        }

        $predictOutput = json_decode($predictProcess->getOutput(), true);
        if (!$predictOutput || ($predictOutput['status'] ?? '') !== 'success') {
            $this->error('Failed to parse prediction output or prediction reported error.');
            $this->error($predictProcess->getOutput());
            return 1;
        }

        $forecasts = $predictOutput['forecasts'] ?? [];
        if (empty($forecasts)) {
            $this->warn('No forecasts were generated.');
            return 0;
        }

        $this->info('Saving ' . count($forecasts) . ' forecasts to database...');
        
        $upsertCount = 0;
        foreach ($forecasts as $forecast) {
            DemandForecast::updateOrCreate(
                [
                    'store_id' => $forecast['store_id'],
                    'inventory_id' => $forecast['inventory_id'],
                    'forecast_date' => $forecast['forecast_date'],
                ],
                [
                    'predicted_quantity' => $forecast['predicted_quantity'],
                    'generated_at' => now(),
                ]
            );
            $upsertCount++;
        }

        $this->info("Successfully saved {$upsertCount} forecasts.");
        return 0;
    }
}
