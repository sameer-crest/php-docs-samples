<?php

/**
 * Copyright 2023 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * For instructions on how to run the samples:
 *
 * @see https://github.com/GoogleCloudPlatform/php-docs-samples/tree/main/dlp/README.md
 */

namespace Google\Cloud\Samples\Dlp;

# [START dlp_deidentify_table_bucketing]
use Google\Cloud\Dlp\V2\DlpServiceClient;
use Google\Cloud\Dlp\V2\ContentItem;
use Google\Cloud\Dlp\V2\DeidentifyConfig;
use Google\Cloud\Dlp\V2\FieldId;
use Google\Cloud\Dlp\V2\FieldTransformation;
use Google\Cloud\Dlp\V2\FixedSizeBucketingConfig;
use Google\Cloud\Dlp\V2\PrimitiveTransformation;
use Google\Cloud\Dlp\V2\RecordTransformations;
use Google\Cloud\Dlp\V2\Table;
use Google\Cloud\Dlp\V2\Table\Row;
use Google\Cloud\Dlp\V2\Value;

/**
 * De-identify data using table bucketing
 * Transform a column without inspection. To transform a column in which the content is
 * already known, you can skip inspection and specify a transformation directly.
 *
 * @param string $callingProjectId      The Google Cloud project id to use as a parent resource.
 * @param string $inputCsvFile          The input file(csv) path  to deidentify
 * @param string $outputCsvFile         The oupt file path to save deidentify content
 *
 */
function deidentify_table_bucketing(
    // TODO(developer): Replace sample parameters before running the code.
    string $callingProjectId,
    string $inputCsvFile = './test/data/table2.csv',
    string $outputCsvFile = './test/data/deidentify_table_bucketing_output.csv'
): void {
    // Instantiate a client.
    $dlp = new DlpServiceClient();

    // Read a CSV file
    $csvLines = file($inputCsvFile, FILE_IGNORE_NEW_LINES);
    $csvHeaders = explode(',', $csvLines[0]);
    $csvRows = array_slice($csvLines, 1);

    // Convert CSV file into protobuf objects
    $tableHeaders = array_map(function ($csvHeader) {
        return (new FieldId)
            ->setName($csvHeader);
    }, $csvHeaders);

    $tableRows = array_map(function ($csvRow) {
        $rowValues = array_map(function ($csvValue) {
            return (new Value())
                ->setStringValue($csvValue);
        }, explode(',', $csvRow));
        return (new Row())
            ->setValues($rowValues);
    }, $csvRows);

    // Construct the table object
    $tableToDeIdentify = (new Table())
        ->setHeaders($tableHeaders)
        ->setRows($tableRows);

    // Specify what content you want the service to de-identify.
    $contentItem = (new ContentItem())
        ->setTable($tableToDeIdentify);

    // Specify how the content should be de-identified.
    $fixedSizeBucketingConfig = (new FixedSizeBucketingConfig())
        ->setBucketSize(10)
        ->setLowerBound((new Value())
            ->setIntegerValue(10))
        ->setUpperBound((new Value())
            ->setIntegerValue(100));

    $primitiveTransformation = (new PrimitiveTransformation())
        ->setFixedSizeBucketingConfig($fixedSizeBucketingConfig);

    // Specify field to be encrypted.
    $fieldId = (new FieldId())
        ->setName('HAPPINESS_SCORE');

    // Associate the encryption with the specified field.
    $fieldTransformation = (new FieldTransformation())
        ->setPrimitiveTransformation($primitiveTransformation)
        ->setFields([$fieldId]);

    $recordTransformations = (new RecordTransformations())
        ->setFieldTransformations([$fieldTransformation]);

    // Create the deidentification configuration object
    $deidentifyConfig = (new DeidentifyConfig())
        ->setRecordTransformations($recordTransformations);

    $parent = "projects/$callingProjectId/locations/global";

    // Run request
    $response = $dlp->deidentifyContent([
        'parent' => $parent,
        'deidentifyConfig' => $deidentifyConfig,
        'item' => $contentItem
    ]);

    // Print results
    $csvRef = fopen($outputCsvFile, 'w');
    fputcsv($csvRef, $csvHeaders);
    foreach ($response->getItem()->getTable()->getRows() as $tableRow) {
        $values = array_map(function ($tableValue) {
            return $tableValue->getStringValue();
        }, iterator_to_array($tableRow->getValues()));
        fputcsv($csvRef, $values);
    };
    printf($outputCsvFile);
}
# [END dlp_deidentify_table_bucketing]

// The following 2 lines are only needed to run the samples
require_once __DIR__ . '/../../testing/sample_helpers.php';
\Google\Cloud\Samples\execute_sample(__FILE__, __NAMESPACE__, $argv);
