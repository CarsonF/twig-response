<?php declare(strict_types=1);

namespace Gmo\Web\Logger\Processor;

use Bolt\Collection\Arr;
use Bolt\Common\Json;

/**
 * Converts JsonSerializable objects to their JSON representation (parsed back to array).
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class JsonSerializableProcessor
{
    public function __invoke(array $record): array
    {
        return Arr::mapRecursive($record, function ($value) {
            if ($value instanceof \JsonSerializable) {
                // Dump and parse to handle nested objects
                return Json::parse(Json::dump($value));
            }

            return $value;
        });
    }
}
