<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config\Debug;

use Magento\Framework\App\ResourceConnection;

class CronScheduleCollector implements CollectorInterface
{
    public const MAX_ROWS = 500;

    private const COLUMNS = [
        'schedule_id',
        'job_code',
        'status',
        'messages',
        'created_at',
        'scheduled_at',
        'executed_at',
        'finished_at',
    ];

    public function __construct(
        private readonly ResourceConnection $resource,
    ) {
    }

    /**
     * Renders the cron_schedule.csv body for Mollie-owned cron jobs.
     */
    public function collect(): array
    {
        return ['cron_schedule.csv' => $this->render()];
    }

    public function getReadmeDescription(): string
    {
        return "- cron_schedule.csv\n"
            . "  The most recent Mollie-related rows from the cron_schedule table (up to "
            . self::MAX_ROWS . "),\n"
            . "  ordered newest first. Useful for spotting cron jobs that are stuck,\n"
            . "  failing, or not running.";
    }

    public function render(): string
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('cron_schedule');

        $select = $connection->select()
            ->from($table, self::COLUMNS)
            ->where('job_code LIKE ?', 'mollie_%')
            ->order('scheduled_at DESC')
            ->limit(self::MAX_ROWS);

        $rows = $connection->fetchAll($select);

        $lines = [$this->formatCsvRow(self::COLUMNS)];
        foreach ($rows as $row) {
            $values = [];
            foreach (self::COLUMNS as $column) {
                $values[] = $row[$column] ?? '';
            }
            $lines[] = $this->formatCsvRow($values);
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Formats a single CSV row per RFC 4180 (CRLF separators handled by the caller).
     *
     * @param array $values Raw field values for the row
     */
    private function formatCsvRow(array $values): string
    {
        $escaped = [];
        foreach ($values as $value) {
            $escaped[] = $this->escapeCsvField((string)$value);
        }
        return implode(',', $escaped);
    }

    private function escapeCsvField(string $value): string
    {
        if (preg_match('/[",\r\n]/', $value) === 1) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
}
