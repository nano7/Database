<?php namespace Nano7\Database\Model\Relations;

class EmbedOne extends EmbedRelation
{
    /**
     * Get the results of the relationship.
     *
     * @return mixed|\Nano7\Database\Model\EmbedModel
     */
    public function getResults()
    {
        return $this->related->newInstance([], true);
    }
}