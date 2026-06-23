<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ScanConfig;

class ValidationEngine
{
    /**
     * Validate scanned/actual values against the scan configuration and product expected data.
     *
     * @param ScanConfig $scanConfig
     * @param Product $product
     * @param array $actualValues
     * @return array
     */
    public function validate(ScanConfig $scanConfig, Product $product, array $actualValues): array
    {
        $fields = $scanConfig->config_json['fields'] ?? [];
        $results = [];
        $overallStatus = 'PASS'; // PASS, FAIL, WARNING
        $errors = [];

        foreach ($fields as $fieldConfig) {
            $fieldName = $fieldConfig['field'];
            $source = $fieldConfig['source'] ?? 'product';
            $required = $fieldConfig['required'] ?? false;
            $compare = $fieldConfig['compare'] ?? false;
            $tolerance = $fieldConfig['tolerance'] ?? null;

            $actualValue = $actualValues[$fieldName] ?? null;
            $expectedValue = null;
            $differenceValue = null;
            $fieldStatus = 'PASS';

            // 1. Determine Expected Value if source is product
            if ($source === 'product') {
                if (in_array($fieldName, ['code', 'barcode', 'qr_code', 'name', 'status'])) {
                    $expectedValue = $product->{$fieldName};
                } elseif ($fieldName === 'location_id') {
                    // For location comparison, we might compare location IDs or location codes
                    $expectedValue = $product->location_id;
                } elseif ($fieldName === 'category_id') {
                    $expectedValue = $product->category_id;
                } else {
                    // It is a dynamic attribute
                    $expectedValue = $product->getDynamicAttributeValue($fieldName);
                }
            }

            // Clean actual and expected value strings
            $actualStr = ($actualValue !== null && $actualValue !== '') ? trim((string)$actualValue) : null;
            $expectedStr = ($expectedValue !== null && $expectedValue !== '') ? trim((string)$expectedValue) : null;

            // 2. Check Required
            if ($required && ($actualStr === null || $actualStr === '')) {
                $fieldStatus = 'FAIL';
                $overallStatus = 'FAIL';
                $errors[$fieldName] = "The field {$fieldName} is required.";
            }

            // 3. Compare values if marked for comparison and not already failed
            if ($compare && $fieldStatus === 'PASS') {
                if ($expectedStr !== null && $actualStr !== null) {
                    if (is_numeric($expectedStr) && is_numeric($actualStr)) {
                        $expectedNum = (float)$expectedStr;
                        $actualNum = (float)$actualStr;
                        $diff = $actualNum - $expectedNum;

                        if ($tolerance !== null) {
                            $toleranceVal = (float)$tolerance;
                            if (abs($diff) > $toleranceVal) {
                                $fieldStatus = 'FAIL';
                                $overallStatus = 'FAIL';
                            }
                        } else {
                            if (abs($diff) > 0.00001) {
                                $fieldStatus = 'FAIL';
                                $overallStatus = 'FAIL';
                            }
                        }
                        $differenceValue = (string)round($diff, 4);
                    } else {
                        // String comparison
                        if (strcasecmp($actualStr, $expectedStr) !== 0) {
                            $fieldStatus = 'FAIL';
                            $overallStatus = 'FAIL';
                            $differenceValue = 'Mismatch';
                        }
                    }
                } elseif ($expectedStr !== $actualStr) {
                    // One is null and the other is not
                    $fieldStatus = 'FAIL';
                    $overallStatus = 'FAIL';
                    $differenceValue = 'Mismatch';
                }
            }

            $results[] = [
                'field_name' => $fieldName,
                'expected_value' => $expectedStr,
                'actual_value' => $actualStr,
                'difference_value' => $differenceValue,
                'status' => $fieldStatus,
                'tolerance' => $tolerance,
            ];
        }

        return [
            'result_status' => $overallStatus,
            'values' => $results,
            'errors' => $errors,
        ];
    }
}
