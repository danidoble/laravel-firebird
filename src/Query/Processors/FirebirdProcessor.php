<?php

declare(strict_types=1);

namespace Danidoble\Firebird\Query\Processors;

use Illuminate\Database\Query\Processors\Processor;

class FirebirdProcessor extends Processor
{
    /**
     * Process the results of a column listing query.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  array  $results
     */
    public function processColumnListing($results): array
    {
        return array_map(fn ($result) => ((object) $result)->column_name, $results);
    }
}
