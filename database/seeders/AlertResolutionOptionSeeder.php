<?php

namespace Database\Seeders;

use App\Enums\AlertResolutionOptionType;
use App\Models\AlertResolutionOption;
use Illuminate\Database\Seeder;

class AlertResolutionOptionSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            AlertResolutionOptionType::PossibleCause->value => [
                'Shortage of Maintenance Staff',
                'Ignorance of Triggered Alert',
                'Lack of Knowledge',
                'Sensor Failure',
                'Device Failure',
                'Power/Network Issue',
                'Environmental Factors',
            ],
            AlertResolutionOptionType::RootCause->value => [
                'Door Left Open',
                'Power Failure',
                'Air Conditioner Not Working',
                'Inward/Outward Movement',
                'Network Issue',
                'Power Adapter Issue',
                'Device Failure',
                'Sensor Failure',
            ],
            AlertResolutionOptionType::CorrectiveAction->value => [
                'Hire Skilled Personnel',
                'Prompt Action on Triggered Alerts',
                'Training/Knowledge Improvement',
                'Sensor/Device Replacement',
                'Power/Network Stabilization',
                'Preventive Maintenance',
            ],
        ];

        foreach ($options as $type => $labels) {
            foreach ($labels as $index => $label) {
                AlertResolutionOption::firstOrCreate(
                    ['type' => $type, 'label' => $label],
                    ['sort_order' => $index + 1],
                );
            }
        }
    }
}
