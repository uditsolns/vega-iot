<?php

namespace App\Services\Alert;

use App\Enums\AlertSensorType;
use App\Enums\AlertSeverity;
use App\Models\DeviceConfiguration;
use App\Models\DeviceReading;

class ThresholdEvaluationService
{
    /**
     * Evaluate a reading against device configuration thresholds
     *
     * @param DeviceReading $reading
     * @param DeviceConfiguration $config
     * @return array Array of threshold violations
     */
    public function evaluate(DeviceReading $reading, DeviceConfiguration $config): array
    {
        $violations = [];

        // Check temperature threshold
        if ($reading->temperature !== null) {
            $violation = $this->evaluateTemperature($reading->temperature, $config);
            if ($violation) {
                $violations[] = $violation;
            }
        }

        // Check humidity threshold
        if ($reading->humidity !== null) {
            $violation = $this->evaluateHumidity($reading->humidity, $config);
            if ($violation) {
                $violations[] = $violation;
            }
        }

        // Check temperature probe threshold
        if ($reading->temp_probe !== null) {
            $violation = $this->evaluateTempProbe($reading->temp_probe, $config);
            if ($violation) {
                $violations[] = $violation;
            }
        }

        return $violations;
    }

    /**
     * Evaluate temperature against thresholds
     *
     * @param float $value
     * @param DeviceConfiguration $config
     * @return array|null Violation details or null if no violation
     */
    public function evaluateTemperature(float $value, DeviceConfiguration $config): ?array
    {
        // Check critical max
        if ($config->temp_max_critical !== null && $value > $config->temp_max_critical) {
            return [
                'type' => AlertSensorType::Temperature->value,
                'severity' => AlertSeverity::Critical->value,
                'value' => $value,
                'threshold_breached' => 'temp_max_critical',
                'reason' => $this->formatReason(
                    'Temperature',
                    $value,
                    $config->temp_max_critical,
                    'critical maximum'
                ),
            ];
        }

        // Check critical min
        if ($config->temp_min_critical !== null && $value < $config->temp_min_critical) {
            return [
                'type' => AlertSensorType::Temperature->value,
                'severity' => AlertSeverity::Critical->value,
                'value' => $value,
                'threshold_breached' => 'temp_min_critical',
                'reason' => $this->formatReason(
                    'Temperature',
                    $value,
                    $config->temp_min_critical,
                    'critical minimum'
                ),
            ];
        }

        // Check warning max
        if ($config->temp_max_warning !== null && $value > $config->temp_max_warning) {
            return [
                'type' => AlertSensorType::Temperature->value,
                'severity' => AlertSeverity::Warning->value,
                'value' => $value,
                'threshold_breached' => 'temp_max_warning',
                'reason' => $this->formatReason(
                    'Temperature',
                    $value,
                    $config->temp_max_warning,
                    'warning maximum'
                ),
            ];
        }

        // Check warning min
        if ($config->temp_min_warning !== null && $value < $config->temp_min_warning) {
            return [
                'type' => AlertSensorType::Temperature->value,
                'severity' => AlertSeverity::Warning->value,
                'value' => $value,
                'threshold_breached' => 'temp_min_warning',
                'reason' => $this->formatReason(
                    'Temperature',
                    $value,
                    $config->temp_min_warning,
                    'warning minimum'
                ),
            ];
        }

        return null;
    }

    /**
     * Evaluate humidity against thresholds
     *
     * @param float $value
     * @param DeviceConfiguration $config
     * @return array|null Violation details or null if no violation
     */
    public function evaluateHumidity(float $value, DeviceConfiguration $config): ?array
    {
        // Check critical max
        if ($config->humidity_max_critical !== null && $value > $config->humidity_max_critical) {
            return [
                'type' => AlertSensorType::Humidity->value,
                'severity' => AlertSeverity::Critical->value,
                'value' => $value,
                'threshold_breached' => 'humidity_max_critical',
                'reason' => $this->formatReason(
                    'Humidity',
                    $value,
                    $config->humidity_max_critical,
                    'critical maximum',
                    '%'
                ),
            ];
        }

        // Check critical min
        if ($config->humidity_min_critical !== null && $value < $config->humidity_min_critical) {
            return [
                'type' => AlertSensorType::Humidity->value,
                'severity' => AlertSeverity::Critical->value,
                'value' => $value,
                'threshold_breached' => 'humidity_min_critical',
                'reason' => $this->formatReason(
                    'Humidity',
                    $value,
                    $config->humidity_min_critical,
                    'critical minimum',
                    '%'
                ),
            ];
        }

        // Check warning max
        if ($config->humidity_max_warning !== null && $value > $config->humidity_max_warning) {
            return [
                'type' => AlertSensorType::Humidity->value,
                'severity' => AlertSeverity::Warning->value,
                'value' => $value,
                'threshold_breached' => 'humidity_max_warning',
                'reason' => $this->formatReason(
                    'Humidity',
                    $value,
                    $config->humidity_max_warning,
                    'warning maximum',
                    '%'
                ),
            ];
        }

        // Check warning min
        if ($config->humidity_min_warning !== null && $value < $config->humidity_min_warning) {
            return [
                'type' => AlertSensorType::Humidity->value,
                'severity' => AlertSeverity::Warning->value,
                'value' => $value,
                'threshold_breached' => 'humidity_min_warning',
                'reason' => $this->formatReason(
                    'Humidity',
                    $value,
                    $config->humidity_min_warning,
                    'warning minimum',
                    '%'
                ),
            ];
        }

        return null;
    }

    /**
     * Evaluate temperature probe against thresholds
     *
     * @param float $value
     * @param DeviceConfiguration $config
     * @return array|null Violation details or null if no violation
     */
    public function evaluateTempProbe(float $value, DeviceConfiguration $config): ?array
    {
        // Check critical max
        if ($config->temp_probe_max_critical !== null && $value > $config->temp_probe_max_critical) {
            return [
                'type' => AlertSensorType::TempProbe->value,
                'severity' => AlertSeverity::Critical->value,
                'value' => $value,
                'threshold_breached' => 'temp_probe_max_critical',
                'reason' => $this->formatReason(
                    'Temperature Probe',
                    $value,
                    $config->temp_probe_max_critical,
                    'critical maximum'
                ),
            ];
        }

        // Check critical min
        if ($config->temp_probe_min_critical !== null && $value < $config->temp_probe_min_critical) {
            return [
                'type' => AlertSensorType::TempProbe->value,
                'severity' => AlertSeverity::Critical->value,
                'value' => $value,
                'threshold_breached' => 'temp_probe_min_critical',
                'reason' => $this->formatReason(
                    'Temperature Probe',
                    $value,
                    $config->temp_probe_min_critical,
                    'critical minimum'
                ),
            ];
        }

        // Check warning max
        if ($config->temp_probe_max_warning !== null && $value > $config->temp_probe_max_warning) {
            return [
                'type' => AlertSensorType::TempProbe->value,
                'severity' => AlertSeverity::Warning->value,
                'value' => $value,
                'threshold_breached' => 'temp_probe_max_warning',
                'reason' => $this->formatReason(
                    'Temperature Probe',
                    $value,
                    $config->temp_probe_max_warning,
                    'warning maximum'
                ),
            ];
        }

        // Check warning min
        if ($config->temp_probe_min_warning !== null && $value < $config->temp_probe_min_warning) {
            return [
                'type' => AlertSensorType::TempProbe->value,
                'severity' => AlertSeverity::Warning->value,
                'value' => $value,
                'threshold_breached' => 'temp_probe_min_warning',
                'reason' => $this->formatReason(
                    'Temperature Probe',
                    $value,
                    $config->temp_probe_min_warning,
                    'warning minimum'
                ),
            ];
        }

        return null;
    }

    /**
     * Determine severity based on value and thresholds
     *
     * @param float $value
     * @param DeviceConfiguration $config
     * @param string $sensorType
     * @return string 'warning' or 'critical'
     */
    public function determineSeverity(float $value, DeviceConfiguration $config, string $sensorType): string
    {
        switch ($sensorType) {
            case 'temperature':
                if (($config->temp_max_critical !== null && $value > $config->temp_max_critical) ||
                    ($config->temp_min_critical !== null && $value < $config->temp_min_critical)) {
                    return AlertSeverity::Critical->value;
                }
                return AlertSeverity::Warning->value;

            case 'humidity':
                if (($config->humidity_max_critical !== null && $value > $config->humidity_max_critical) ||
                    ($config->humidity_min_critical !== null && $value < $config->humidity_min_critical)) {
                    return AlertSeverity::Critical->value;
                }
                return AlertSeverity::Warning->value;

            case 'temp_probe':
                if (($config->temp_probe_max_critical !== null && $value > $config->temp_probe_max_critical) ||
                    ($config->temp_probe_min_critical !== null && $value < $config->temp_probe_min_critical)) {
                    return AlertSeverity::Critical->value;
                }
                return AlertSeverity::Warning->value;

            default:
                return AlertSeverity::Warning->value;
        }
    }

    /**
     * Get threshold field name that was breached
     *
     * @param float $value
     * @param DeviceConfiguration $config
     * @param string $sensorType
     * @param string $severity
     * @return string
     */
    public function getThresholdBreached(float $value, DeviceConfiguration $config, string $sensorType, string $severity): string
    {
        $prefix = $sensorType === 'temp_probe' ? 'temp_probe' : $sensorType;
        $suffix = $severity === AlertSeverity::Critical->value ? 'critical' : 'warning';

        // Determine if it's max or min breach
        $maxField = "{$prefix}_max_{$suffix}";
        $minField = "{$prefix}_min_{$suffix}";

        // Check max threshold first
        if ($config->$maxField !== null && $value > $config->$maxField) {
            return $maxField;
        }

        // Check min threshold
        if ($config->$minField !== null && $value < $config->$minField) {
            return $minField;
        }

        return $maxField; // Default
    }

    /**
     * Format human-readable reason for threshold violation
     *
     * @param string $sensorName
     * @param float $value
     * @param float $threshold
     * @param string $thresholdType
     * @param string $unit
     * @return string
     */
    public function formatReason(string $sensorName, float $value, float $threshold, string $thresholdType, string $unit = '°C'): string
    {
        $comparison = str_contains($thresholdType, 'maximum') ? 'exceeded' : 'below';
        return sprintf(
            '%s %s%s %s %s of %s%s',
            $sensorName,
            number_format($value, 1),
            $unit,
            $comparison,
            $thresholdType,
            number_format($threshold, 1),
            $unit
        );
    }
}
