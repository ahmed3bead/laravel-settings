<?php

namespace Ahmed3bead\Settings\Repositories;

use Ahmed3bead\Settings\Contracts\Repository as RepositoryContract;
use Ahmed3bead\Settings\EntryFilter;

abstract class Repository implements RepositoryContract
{
    /**
     * Settings filter instance.
     */
    protected EntryFilter $entryFilter;

    /**
     * Set settings filter.
     */
    public function withFilter(EntryFilter $filter): Repository
    {
        $this->entryFilter = $filter;

        return $this;
    }
}
