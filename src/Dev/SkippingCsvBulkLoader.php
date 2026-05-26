<?php

namespace SilverStripe\RedirectedURLs\Dev;

use SilverStripe\Dev\CsvBulkLoader;

/**
 * CsvBulkLoader variant that skips rows whose duplicate-check matches an
 * existing record instead of updating them. Used so uploading additional CSVs
 * never overwrites existing redirects.
 *
 * Also normalises FromBase/FromQuerystring before the duplicate lookup so
 * trailing slashes / casing in the CSV don't defeat the match against records
 * stored in the canonical form produced by RedirectedURL::onBeforeWrite.
 */
class SkippingCsvBulkLoader extends CsvBulkLoader
{
    protected function processRecord($record, $columnMap, &$results, $preview = false)
    {
        $record = $this->normaliseFromColumns($record);

        if ($this->findExistingObject($record, $columnMap)) {
            return 0;
        }

        return parent::processRecord($record, $columnMap, $results, $preview);
    }

    private function normaliseFromColumns(array $record): array
    {
        if (isset($record['FromBase']) && $record['FromBase'] !== '') {
            $base = $record['FromBase'];
            if ($base[0] !== '/') {
                $base = '/' . $base;
            }
            if ($base !== '/') {
                $base = rtrim($base, '/');
            }
            $base = rtrim($base, '?');
            $record['FromBase'] = $base;
        }

        if (isset($record['FromQuerystring']) && $record['FromQuerystring'] !== '') {
            $record['FromQuerystring'] = strtolower(rtrim($record['FromQuerystring'], '?'));
        }

        return $record;
    }
}
