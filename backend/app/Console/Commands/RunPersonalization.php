<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Models\ConsumerPersonalization;
use App\Models\LocalizedPopularSearch;

class RunPersonalization extends Command
{
    protected $signature = 'ml:run-personalization {--train : Whether to train the model first}';
    protected $description = 'Runs the Random Forest Personalization Python script and imports predictions';

    public function handle()
    {
        $pythonScript = base_path('../ml/random_forest_personalization.py');
        
        if ($this->option('train')) {
            $this->info('Training personalization model...');
            $trainProcess = new Process(['python', $pythonScript, '--mode=train']);
            $trainProcess->setTimeout(120);
            
            try {
                $trainProcess->mustRun();
                $trainOutput = json_decode($trainProcess->getOutput(), true);
                if (!$trainOutput || ($trainOutput['status'] ?? '') !== 'success') {
                    $this->error('Model training failed or returned invalid JSON.');
                    if (isset($trainOutput['message'])) {
                        $this->error($trainOutput['message']);
                    }
                    return 1;
                }
                $this->info('Model trained successfully.');
            } catch (ProcessFailedException $e) {
                $this->error('Python training process failed:');
                $this->error($e->getMessage());
                return 1;
            }
        }
        
        $this->info('Generating personalizations...');
        $predictProcess = new Process(['python', $pythonScript, '--mode=predict']);
        $predictProcess->setTimeout(120);
        
        try {
            $predictProcess->mustRun();
            $output = $predictProcess->getOutput();
            $predictOutput = json_decode($output, true);
            
            if (!$predictOutput || ($predictOutput['status'] ?? '') !== 'success') {
                $this->error('Model prediction failed or returned invalid JSON.');
                if (isset($predictOutput['message'])) {
                    $this->error($predictOutput['message']);
                }
                return 1;
            }
            
            // Path A: Personalizations
            $pathA = $predictOutput['path_a'] ?? [];
            if (count($pathA) > 0) {
                $this->info('Saving ' . count($pathA) . ' personalized records...');
                foreach ($pathA as $rec) {
                    ConsumerPersonalization::updateOrCreate(
                        [
                            'consumer_id' => $rec['consumer_id'],
                            'category_id' => $rec['category_id']
                        ],
                        [
                            'predicted_future_searches' => $rec['predicted_score'],
                            'generated_at' => now()
                        ]
                    );
                }
            } else {
                $this->info('No Path A records returned (maybe new model/insufficient data).');
            }

            // Path B: Localized Popular Searches
            $pathB = $predictOutput['path_b'] ?? [];
            if (count($pathB) > 0) {
                $this->info('Saving ' . count($pathB) . ' localized popular search records...');
                foreach ($pathB as $rec) {
                    LocalizedPopularSearch::updateOrCreate(
                        [
                            'lat_grid' => $rec['lat_grid'],
                            'lng_grid' => $rec['lng_grid'],
                            'search_query' => $rec['search_query']
                        ],
                        [
                            'category_id' => $rec['category_id'],
                            'search_count' => $rec['search_count'],
                            'generated_at' => now()
                        ]
                    );
                }
            } else {
                $this->info('No Path B records returned.');
            }

            $this->info('Successfully generated and saved personalizations.');
            return 0;

        } catch (ProcessFailedException $e) {
            $this->error('Python prediction process failed:');
            $this->error($e->getMessage());
            return 1;
        }
    }
}
