<?php


namespace Bdf\Prime\Relations\Info;


interface RelationInfoInterface
{
    public function isLoaded($entity);

    public function clear($entity);

    public function markAsLoaded($entity);
}
