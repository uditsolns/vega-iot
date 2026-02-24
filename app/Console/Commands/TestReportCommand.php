<?php

namespace App\Console\Commands;

use App\DTOs\ReportGenerationDTO;
use App\Enums\ReportFileType;
use App\Enums\ReportFormat;
use App\Models\Device;
use App\Models\DeviceSensor;
use App\Services\Report\ReportGeneratorService;
use App\Services\Report\ReportQueryService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Artisan command to test report generation end-to-end.
 */
class TestReportCommand extends Command
{
    protected $signature = 'report:test
        {--device=    : Device ID to use (auto-picks first with readings if omitted)}
        {--hours=24   : How many hours back to report on}
        {--interval=5 : Bucket interval in minutes}
        {--format=both : graphical|tabular|both}
        {--file-type=pdf : pdf|csv}
        {--out=       : Output file path (defaults to storage/app/test-report.{ext})}
        {--query-only : Skip PDF rendering; only run and display DB queries}
        {--sensors=   : Comma-separated device_sensor_ids (auto-picks if omitted)}
    ';

    protected $description = 'Test end-to-end report generation against seeded sensor_readings data';

    public function __construct(
        private readonly ReportGeneratorService $generator,
        private readonly ReportQueryService     $queryService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('');
        $this->info('══════════════════════════════════════════');
        $this->info('  Report Generation Test');
        $this->info('══════════════════════════════════════════');

        // ── 1. Resolve device ─────────────────────────────────────────────────
        $device = $this->resolveDevice();
        if (!$device) {
            return self::FAILURE;
        }

        $this->info("Device       : [{$device->id}] {$device->device_code} — {$device->device_name}");

        // ── 2. Resolve sensors ────────────────────────────────────────────────
        $sensors = $this->resolveSensors($device);
        if ($sensors->isEmpty()) {
            $this->error('No enabled sensors with numeric data found for this device.');
            return self::FAILURE;
        }

        $this->info("Sensors      : " . $sensors->map(fn($s) => "[{$s->id}] {$s->label} ({$s->sensorType->unit})")->implode(', '));

        // ── 3. Build DTO ──────────────────────────────────────────────────────
        $hours    = (int) $this->option('hours');
        $interval = (int) $this->option('interval');
        $format   = ReportFormat::from($this->option('format'));
        $fileType = ReportFileType::from($this->option('file-type'));
        $from     = Carbon::now()->subHours($hours);
        $to       = Carbon::now();

        $dto = new ReportGenerationDTO(
            deviceId:     $device->id,
            companyId:    $device->company_id,
            reportName:   "Test Report — {$device->device_code}",
            fileType:     $fileType,
            format:       $format,
            sensorIds:    $sensors->pluck('id')->toArray(),
            interval:     $interval,
            fromDatetime: $from,
            toDatetime:   $to,
        );

        $this->info("Period       : {$from->format('Y-m-d H:i')} → {$to->format('Y-m-d H:i')} ({$hours}h)");
        $this->info("Interval     : {$interval} min");
        $this->info("Format       : {$format->value}");
        $this->info("File type    : {$fileType->value}");
        $this->info('');

        // ── 4. Run DB queries ─────────────────────────────────────────────────
        $this->info('─── Step 1: Raw reading count ─────────────────────────────');
        $this->checkRawReadings($dto, $sensors);

        $this->info('');
        $this->info('─── Step 2: Statistics query (stats_agg) ─────────────────');
        $statsMap = $this->runStatsQuery($dto, $sensors);

        $this->info('');
        $this->info('─── Step 3: Bucketed readings (time_bucket_gapfill + locf) ');
        $bucketedRows = $this->runBucketQuery($dto, $sensors);

        if ($this->option('query-only')) {
            $this->info('');
            $this->info('--query-only flag set. Skipping PDF rendering.');
            return self::SUCCESS;
        }

        // ── 5. Generate report ────────────────────────────────────────────────
        $this->info('');
        $this->info('─── Step 4: Generating report ─────────────────────────────');
        $this->info("Rendering {$fileType->value} ({$format->value})...");

        $startMs = microtime(true);

        try {
            $content = $this->generator->generate($dto);
        } catch (\Throwable $e) {
            $this->error("Generation failed: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }

        $elapsed = round((microtime(true) - $startMs) * 1000);
        $size    = strlen($content);
        $this->info("✓ Generated in {$elapsed}ms — " . number_format($size) . " bytes");

        // ── 6. Save output ────────────────────────────────────────────────────
        $ext     = $fileType->value;
        $outPath = $this->option('out') ?: storage_path("app/test-report.{$ext}");

        $dir = dirname($outPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outPath, $content);
        $this->info("✓ Saved to: {$outPath}");

        $this->info('');
        $this->info('══════════════════════════════════════════');
        $this->info('  All steps passed ✓');
        $this->info('══════════════════════════════════════════');

        return self::SUCCESS;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveDevice(): ?Device
    {
        if ($deviceId = $this->option('device')) {
            $device = Device::with(['deviceModel'])->find($deviceId);
            if (!$device) {
                $this->error("Device ID {$deviceId} not found.");
                return null;
            }
            return $device;
        }

        // Auto-pick: first device that actually has sensor readings
        $deviceId = DB::table('sensor_readings')
            ->join('devices', 'sensor_readings.device_id', '=', 'devices.id')
            ->whereNotNull('devices.area_id')
            ->whereNotNull('devices.company_id')
            ->orderByDesc('sensor_readings.recorded_at')
            ->value('sensor_readings.device_id');

        if (!$deviceId) {
            $this->error('No sensor readings found in the database. Run: php artisan db:seed --class=SensorReadingSeeder');
            return null;
        }

        return Device::with(['deviceModel'])->find($deviceId);
    }

    private function resolveSensors(Device $device)
    {
        if ($ids = $this->option('sensors')) {
            $sensorIds = array_map('intval', explode(',', $ids));
            return DeviceSensor::with('sensorType')
                ->where('device_id', $device->id)
                ->where('is_enabled', true)
                ->whereIn('id', $sensorIds)
                ->whereHas('sensorType', fn($q) => $q->where('data_type', '!=', 'point'))
                ->orderBy('slot_number')
                ->get();
        }

        // Auto-pick: all enabled numeric sensors on this device
        return DeviceSensor::with('sensorType')
            ->where('device_id', $device->id)
            ->where('is_enabled', true)
            ->whereHas('sensorType', fn($q) => $q->where('data_type', '!=', 'point'))
            ->orderBy('slot_number')
            ->get();
    }

    private function checkRawReadings(ReportGenerationDTO $dto, $sensors): void
    {
        foreach ($sensors as $sensor) {
            $count = DB::table('sensor_readings')
                ->where('device_id', $dto->deviceId)
                ->where('device_sensor_id', $sensor->id)
                ->whereBetween('recorded_at', [$dto->fromDatetime, $dto->toDatetime])
                ->whereNotNull('value_numeric')
                ->count();

            $this->line("  sensor_id={$sensor->id} ({$sensor->label}): {$count} raw readings");

            if ($count === 0) {
                $this->warn("  ⚠ No readings found for sensor {$sensor->id} in this time range.");
                $this->warn("    Run php artisan db:seed --class=SensorReadingSeeder");
            }
        }
    }

    private function runStatsQuery(ReportGenerationDTO $dto, $sensors): \Illuminate\Support\Collection
    {
        $start = microtime(true);

        try {
            $statsMap = $this->queryService->getSensorStatistics($dto, $sensors->pluck('id')->toArray());
        } catch (\Throwable $e) {
            $this->error("Stats query failed: {$e->getMessage()}");
            $this->warn("  Hint: Ensure TimescaleDB Toolkit is installed:");
            $this->warn("  CREATE EXTENSION IF NOT EXISTS timescaledb_toolkit;");
            throw $e;
        }

        $elapsed = round((microtime(true) - $start) * 1000);
        $this->line("  Query time: {$elapsed}ms");

        foreach ($sensors as $sensor) {
            $stats = $statsMap->get($sensor->id);
            if (!$stats) {
                $this->warn("  sensor_id={$sensor->id}: no stats (no data in range)");
                continue;
            }
            $unit = $sensor->sensorType->unit;
            $this->line("  sensor_id={$sensor->id} ({$sensor->label}):");
            $this->line("    min={$stats->min_val}{$unit}  max={$stats->max_val}{$unit}  avg=" . round($stats->avg_val, 2) . "{$unit}  stddev=" . round($stats->stddev_val, 3) . "  count={$stats->count_val}");
            $this->line("    first={$stats->first_val}  last={$stats->last_val}");
        }

        return $statsMap;
    }

    private function runBucketQuery(ReportGenerationDTO $dto, $sensors): \Illuminate\Support\Collection
    {
        $start = microtime(true);

        try {
            $rows = $this->queryService->getBucketedReadings($dto, $sensors->pluck('id')->toArray());
        } catch (\Throwable $e) {
            $this->error("Bucketed query failed: {$e->getMessage()}");
            $this->warn("  Hint: time_bucket_gapfill requires the timescaledb extension.");
            $this->warn("  Check: SELECT * FROM pg_extension WHERE extname = 'timescaledb';");
            throw $e;
        }

        $elapsed     = round((microtime(true) - $start) * 1000);
        $totalBuckets = $rows->count();
        $filledCount  = $rows->filter(fn($r) => collect($sensors)->map(fn($s) => $r->{"sensor_{$s->id}"})->filter()->isNotEmpty())->count();
        $nullCount    = $totalBuckets - $filledCount;

        $this->line("  Query time   : {$elapsed}ms");
        $this->line("  Total buckets: {$totalBuckets}");
        $this->line("  Filled       : {$filledCount}  (locf applied)");
        $this->line("  Gap-filled   : {$nullCount}  (all-null before first reading)");

        // Show first 5 and last 5 rows as sample
        if ($totalBuckets > 0) {
            $this->line('');
            $this->line('  Sample rows (first 3):');
            $rows->take(3)->each(fn($r) => $this->printBucketRow($r, $sensors));
            if ($totalBuckets > 6) {
                $this->line('  ...');
            }
            $this->line('  Sample rows (last 3):');
            $rows->slice(-3)->each(fn($r) => $this->printBucketRow($r, $sensors));
        }

        return $rows;
    }

    private function printBucketRow($row, $sensors): void
    {
        $ts   = $row->bucket->format('Y-m-d H:i');
        $vals = collect($sensors)->map(function ($s) use ($row) {
            $key = "sensor_{$s->id}";
            $v   = $row->{$key};
            return "{$s->label}=" . ($v !== null ? round((float)$v, 2) : 'NULL');
        })->implode('  ');

        $this->line("    {$ts}  |  {$vals}");
    }
}
