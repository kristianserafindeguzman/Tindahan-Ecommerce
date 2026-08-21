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
    protected $signature = 'ml:run-demand-forecast {--train : Train the model before predicting}';

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

        if ($this->option('train')) {
            $this->info('Training model...');
            $trainProcess = new Process([$pythonExecutable, $pythonScriptPath, '--mode=train', '--output=json']);
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
        }

        $this->info('Generating forecasts...');
        $predictProcess = new Process([$pythonExecutable, $pythonScriptPath, '--mode=predict', '--output=json']);
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
