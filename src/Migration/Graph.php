<?php
/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration;

use Eddmash\PowerOrm\Exception\NodeNotFoundError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Migration\State\ProjectState;

class Graph
{
    /**
     * keeps track of Migrations already taken care of in the graph.
     *
     * @var \Eddmash\PowerOrm\Migration\Migration[]
     */
    public $nodes;

    /**
     * contains a family tree for each node representing a migration.
     *
     * @var array
     */
    public $nodeFamilyTree = [];

    public function __construct()
    {
        $this->nodes = [];
        $this->nodeFamilyTree = [];
    }

    /**
     * @param string $node
     *
     * @return Node
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getNodeFamilyTree($node)
    {
        return $this->nodeFamilyTree[$node];
    }

    /**
     * @param $migrationName
     * @param $migrationObject
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addNode($migrationName, $migrationObject)
    {
        $node = new Node($migrationName);

        // add to node store
        $this->nodes[$migrationName] = $migrationObject;

        // create family tree
        $this->nodeFamilyTree[$migrationName] = $node;
    }

    /**
     * @param $child
     * @param $parent
     * @param Migration $migration
     *
     * @throws NodeNotFoundError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addDependency($child, $parent, Migration $migration)
    {
        // both parent and child need to be already in the graph
        if (!ArrayHelper::hasKey($this->nodes, $child)):
            throw new NodeNotFoundError(
                sprintf(
                    'Migration %s dependencies reference nonexistent child node %s',
                    $migration->getName(),
                    $child
                )
            );
        endif;
        if (!ArrayHelper::hasKey($this->nodes, $parent)):
            throw new NodeNotFoundError(
                sprintf(
                    'Migration %s dependencies reference nonexistent parent node %s',
                    $migration->getName(),
                    $parent
                )
            );
        endif;

        // add to the family tree of both the child and parent

        $this->getNodeFamilyTree($child)->addParent($this->nodeFamilyTree[$parent]);
        $this->getNodeFamilyTree($parent)->addChild($this->nodeFamilyTree[$child]);
    }

    /**
     * This is a list of all the migrations that are the latest, that is no other dependency depends on them.
     */
    public function getLeafNodes()
    {
        $leaves = [];
        foreach ($this->nodes as $name => $migration) :

            // get the nodes  children
            $children = $this->getNodeFamilyTree($name)->children;

            // if no children exist this must be the latest migration
            if (empty($children)):
                $leaves[$migration->getAppLabel()][] = $name;
            endif;

        endforeach;

        return $leaves;
    }

    /**
     * Given a node, returns a list of which previous nodes (dependencies) must
     * be applied, ending with the node itself.
     *
     * This is the list you would follow if applying the migrations to a database.
     *
     * starting with the oldest upto the node.
     *
     * This puts the oldest node as the first element on the returned array while the node becomes the last.
     *
     * @param $node
     *
     * @return mixed
     *
     * @throws NodeNotFoundError
     */
    public function getAncestryTree($node)
    {
        if (!ArrayHelper::hasKey($this->nodes, $node)):
            throw new NodeNotFoundError(
                sprintf('Migration with the name %s does not exist',
                    $node));
        endif;

        return $this->getNodeFamilyTree($node)->getAncestors();
    }

    /**
     * Given a node, returns a list of which dependent nodes (dependencies) must be unapplied,ending with the node itself.
     *
     * i.e All nodes that depend on the existence of this node.
     *
     * This is the list you would follow if removing the migrations from a database.
     *
     * This puts the last child as the first element on the returned array while the node becomes the last.
     *
     * @param $node
     *
     * @return mixed
     *
     * @throws NodeNotFoundError
     */
    public function getDecedentsTree($node)
    {
        if (!ArrayHelper::hasKey($this->nodes, $node)):
            throw new NodeNotFoundError(sprintf('Migration with the name %s does not exist', $node));
        endif;

        return $this->getNodeFamilyTree($node)->getDescendants();
    }

    /**
     * This is a list of all the migrations that were the first, i.e they don't depend on any other migrations.
     */
    public function getRootNodes()
    {
        $root = [];
        foreach ($this->nodes as $name => $migration) :
            // get the nodes  parent
            $parents = $this->getNodeFamilyTree($name)->parent;

            // if no parent exist this must be the first migration aka adam/eve which ever tickles your fancy
            if (empty($parents)):
                $root[] = $name;
            endif;

        endforeach;

        return $root;
    }

    /**
     * Create ProjectState based on migrations on disk.
     *
     * @return ProjectState
     *
     * @throws NodeNotFoundError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getState()
    {
        $leaves = $this->getLeafNodes();

        $state = ProjectState::createObject();
        if (empty($leaves)):
            return $state;
        endif;

        // from the leave go up its family tree though its parents and
        // ancestors until we get to the root_node.
        // this way we get the full lineage we need to follow to get to
        // this leaf from root_node to leaf_node
        // we use this lineage to apply migrations in database
        $lineage = [];
        foreach ($leaves as $appName=>$appLeaves) :

            // get lineage
            foreach ($appLeaves as $leaf) :
                $lineage_members = $this->getAncestryTree($leaf);

                foreach ($lineage_members as $i => $l_member) :

                    if (in_array($l_member, $lineage)):
                        continue;
                    endif;
                    $lineage[] = $l_member;
                endforeach;
            endforeach;

        endforeach;

        // use the lineage to update the project state based on the migrations.
        /* @var $migration Migration */
        foreach ($lineage as $member) :

            $migration = $this->nodes[$member];

            $state = $migration->updateState($state);

        endforeach;

        return $state;
    }

    /**
     * @param string $migrationName
     *
     * @return Migration
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getMigration($migrationName)
    {
        return $this->nodes[$migrationName];
    }
}
