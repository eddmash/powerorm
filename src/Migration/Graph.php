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
     * @var Node[][]
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
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getNodeFamilyTree($appName, $node)
    {
        return $this->nodeFamilyTree[$appName][$node];
    }

    /**
     * @param           $migrationName
     * @param Migration $migrationObject
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addNode($migrationName, $migrationObject)
    {
        $appName = $migrationObject->getAppLabel();
        $node = new Node($appName, $migrationName);

        // add to node store
        $this->nodes[$appName][$migrationName] = $migrationObject;

        // create family tree
        $this->nodeFamilyTree[$appName][$migrationName] = $node;
    }

    /**
     * @param           $child
     * @param           $parent
     * @param Migration $migration
     *
     * @throws NodeNotFoundError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addDependency($child, $parent, Migration $migration)
    {
        $appName = $migration->getAppLabel();
        $parentAppName = key($parent);
        $parent = $parent[$parentAppName];
        // both parent and child need to be already in the graph
        if (empty($this->nodes[$appName][$child])):
            throw new NodeNotFoundError(
                sprintf(
                    'Migration %s dependencies reference '.
                    'nonexistent child node %s',
                    $migration->getName(),
                    $child
                )
            );
        endif;

        if (empty($this->nodes[$parentAppName][$parent])):
            throw new NodeNotFoundError(
                sprintf(
                    'Migration %s dependencies reference nonexistent'.
                    ' parent node %s',
                    $migration->getName(),
                    $parent
                )
            );
        endif;

        // add to the family tree of both the child and parent

        $this->getNodeFamilyTree($appName, $child)
             ->addParent($this->nodeFamilyTree[$parentAppName][$parent]);

        $this->getNodeFamilyTree($parentAppName, $parent)
             ->addChild($this->nodeFamilyTree[$appName][$child]);
    }

    /**
     * This is a list of all the migrations that are the latest, that is no
     * other dependency depends on them.
     */
    public function getLeafNodes($app = null)
    {
        $leaves = [];

        foreach ($this->nodes as $appName => $nodes) :

            if (!is_null($app) && $app != $appName):
                continue;
            endif;
            foreach ($nodes as $name => $migration) :

                // get the nodes  children
                $children = $this->getNodeFamilyTree(
                    $appName,
                    $name
                )->children;

                // if no children exist this must be the latest migration
                // or if it has children and none of them is for app we are
                // checking then this is the latest migrations
                $isLatest = true;
                foreach ($children as $child) :
                    if ($child->appName == $appName):
                        $isLatest = false;
                    endif;
                endforeach;

                if ($isLatest):
                    if (!is_null($app)):
                        $leaves[] = $name;
                    else:
                        $leaves[$appName][] = $name;
                    endif;
                endif;
            endforeach;
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
     * This puts the oldest node as the first element on the returned array
     * while the node becomes the last.
     *
     * @param $node
     *
     * @return mixed
     *
     * @throws NodeNotFoundError
     */
    public function getAncestryTree($appName, $node)
    {
        if (empty($this->nodes[$appName][$node])):
            throw new NodeNotFoundError(
                sprintf(
                    'Migration with the name %s does not exist',
                    $node
                )
            );
        endif;

        return $this->getNodeFamilyTree(
            $appName,
            $node
        )->getAncestors();
    }

    /**
     * Given a node, returns a list of which dependent nodes (dependencies)
     * must be unapplied,ending with the node
     * itself.
     *
     * i.e All nodes that depend on the existence of this node.
     *
     * This is the list you would follow if removing the migrations from a database.
     *
     * This puts the last child as the first element on the returned array
     * while the node becomes the last.
     *
     * @param $node
     *
     * @return mixed
     *
     * @throws NodeNotFoundError
     */
    public function getDecedentsTree($appName, $node)
    {
        if (empty($this->nodes[$appName][$node])):
            throw new NodeNotFoundError(
                sprintf(
                    'Migration with the name %s does not exist',
                    $node
                )
            );
        endif;

        return $this->getNodeFamilyTree($appName, $node)->getDescendants();
    }

    /**
     * This is a list of all the migrations that were the first, i.e they don't depend on any other migrations.
     */
    public function getRootNodes()
    {
        $root = [];

        foreach ($this->nodes as $appName => $nodes) :
            foreach ($nodes as $name => $migration) :
                // get the nodes  parent
                $parents = $this->getNodeFamilyTree($appName, $name)->parent;

                // if no parent exist this must be the first migration aka
                // adam/eve which ever tickles your fancy
                if (empty($parents)):
                    $root[$appName] = $name;
                endif;

            endforeach;
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
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getState($leaves = null, $atEnd = true)
    {
        if (is_null($leaves)):
            $leaves = $this->getLeafNodes();
        endif;

        $state = ProjectState::createObject();
        $state->fromDisk(true);
        if (empty($leaves)):
            return $state;
        endif;

        // from the leave go up its family tree though its parents and
        // ancestors until we get to the root_node.
        // this way we get the full lineage we need to follow to get to
        // this leaf from root_node to leaf_node
        // we use this lineage to apply migrations in database
        $lineage = [];
        foreach ($leaves as $appName => $appLeaves) :

            // get lineage
            foreach ($appLeaves as $leaf) :
                $lineage_members = $this->getAncestryTree($appName, $leaf);

                foreach ($lineage_members as $l_member => $l_app) :

                    if (empty($lineage[$l_member][$l_app])):
                        if (!$atEnd && in_array($l_member, $appLeaves)):
                            continue;
                        endif;

                        $lineage[$l_member] = $l_app;
                    endif;

                endforeach;
            endforeach;

        endforeach;

        // use the lineage to update the project state based on the migrations.
        /* @var $migration Migration */
        foreach ($lineage as $member => $lAppName) :
            $migration = $this->nodes[$lAppName][$member];

            $state = $migration->updateState($state);

        endforeach;

        return $state;
    }

    /**
     * @param string $migrationName
     *
     * @return Migration
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getMigration($appName, $migrationName)
    {
        return $this->nodes[$appName][$migrationName];
    }
}
