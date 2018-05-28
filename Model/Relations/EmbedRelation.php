<?php namespace Nano7\Database\Model\Relations;

abstract class EmbedRelation extends Relation
{
    /**
     * The local key of the parent model.
     *
     * @var string
     */
    protected $localKey;

    /**
     * @param $parent
     * @param $related
     */
    public function __construct($parent, $related, $localKey)
    {
        parent::__construct($parent, $related);

        $this->localKey = $localKey;
    }
}