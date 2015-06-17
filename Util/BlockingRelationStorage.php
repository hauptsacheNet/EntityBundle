<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 17.06.15
 * Time: 10:25
 */
namespace Hn\EntityBundle\Util;

use Hn\EntityBundle\Service\DependencyService\BlockingRelationInterface;

class BlockingRelationStorage implements \IteratorAggregate
{
    /**
     * @var BlockingRelationInterface[]
     */
    private $relations = array();

    /**
     * @var int[]
     */
    private $relationPriorities = array();

    public function add(BlockingRelationInterface $relation)
    {
        $this->relations[] = $relation;
    }

    public function voteRelation(BlockingRelationInterface $relation, $amount)
    {
        $relationHash = spl_object_hash($relation);
        if (!isset($this->relationPriorities[$relationHash])) {
            $this->relationPriorities[$relationHash] = $amount;
        } else {
            $this->relationPriorities[$relationHash] += $amount;
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        $relations = $this->relations;

        usort($relations, function ($a, $b) {
            $aLikeliness = intval(@$this->relationPriorities[spl_object_hash($a)]);
            $bLikeliness = intval(@$this->relationPriorities[spl_object_hash($b)]);
            return ($bLikeliness - $aLikeliness); // DESC order
        });

        return new \ArrayIterator($relations);
    }
}