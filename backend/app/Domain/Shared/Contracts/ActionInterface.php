<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * Contract for all domain Action classes.
 *
 * Actions encapsulate a single business operation.
 * Each Action should do exactly one thing and do it well.
 */
interface ActionInterface
{
    /**
     * Execute the action with the given input data.
     *
     * @param  mixed  $data  Typically a DTO or set of parameters.
     * @return mixed         The result of the action.
     */
    public function execute(mixed $data): mixed;
}
