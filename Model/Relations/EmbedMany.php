<?php namespace Nano7\Database\Model\Relations;

use Nano7\Database\Model\EmbedModel;

class EmbedMany extends EmbedRelation
{
    /**
     * Get the results of the relationship.
     *
     * @return \Illuminate\Support\Collection|mixed
     */
    public function getResults()
    {
        $list = (array) $this->parent->getAttributeFromArray($this->localKey);
        $result = [];

        foreach ($list as $index => $item) {
            $result[$index] = $model = $this->related->newInstance([], true);
            $model->setId($index);
        }

        return collect($result);
    }

    /**
     * @param array $attributes
     * @return EmbedModel
     */
    public function add($attributes = [])
    {
        $item = $this->related->newInstance($attributes, false);

        return $item;
    }
}