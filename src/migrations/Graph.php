<?php
/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 5/14/16
 * Time: 9:20 AM
 */

namespace eddmash\powerorm\migrations;

use eddmash\powerorm\exceptions\NodeNotFoundError;
use eddmash\powerorm\exceptions\NotFound;

/**
 * This class represents a migration and its family tree infomartion
 * i.e what migrations need to exist before this one can exist (parent)
 * and which migrations cannot exist if this one does not (children)
 *
 * @package eddmash\powerorm\migrations
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Node
{
    public $name;
    public $children;
    public $parent;

    public function __construct($name)
    {
        $this->name = $name;
        $this->children = [];
        $this->parent = [];
    }

    /**
     * @param Node $parent
     */
    public function add_parent($parent)
    {
        $this->parent[] = $parent;
    }

    /**
     * @param Node $child
     */
    public function add_child($child)
    {
        $this->children[] = $child;
    }

    /**
     * Get ancestors from the first ancestor aka adam and eve to upto this node.that is oldest at index '0'
     * @return array
     */
    public function ancestors()
    {
        $ancestors = [];

        $ancestors[] = $this->name;
        foreach ($this->parent as $parent) :
            $ancestors = array_merge($parent->ancestors(), $ancestors);
        endforeach;

        return $ancestors;
    }

    /**
     * Get all nodes the consider this node there first ancestor, stating with this one.
     * This method puts the current node as first in array index 0, and the newest being in the other end
     * @return array
     */
    public function descendants()
    {
        $descendants = [];

        $descendants[] = $this->name;
        foreach ($this->children as $child) :
            $descendants = array_merge($child->descendants(), $descendants);
        endforeach;

        return $descendants;
    }
}

/**
 * Creates a family tree for each migration with relation to the other migrations.
 * This will help us determine what needs to resolved before a migration is acted on.
 * @package eddmash\powerorm\migrations
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Graph
{

    /**
     * keeps track of Migrations already taken care of in the graph
     * @var array
     */
    public $nodes;

    /**
     * contains a family tree for each node representing a migration.
     * @var
     */
    public $node_family_tree;

    public function __construct()
    {
        $this->nodes = [];
        $this->node_family_tree = [];
    }

    /**
     * @param string $migration_name
     * @param Migration $migration_object
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function add_node($migration_name, $migration_object)
    {
        $node = new Node($migration_name);

        // add to node store
        $this->nodes[$migration_name] = $migration_object;

        // create family tree
        $this->node_family_tree[$migration_name] = $node;
    }

    /**
     * @param $child
     * @param $parent
     * @param Migration $migration
     * @throws NodeNotFoundError
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function add_dependency($child, $parent, $migration)
    {
        // both parent and child need to be already in the graph
        if (!array_key_exists($child, $this->nodes)):
            throw new NodeNotFoundError(
                sprintf('Migration %1$s dependencies reference nonexistent child node %2$s', $migration->name, $child));
        endif;
        if (!array_key_exists($parent, $this->nodes)):
            throw new NodeNotFoundError(
                sprintf('Migration %1$s dependencies reference nonexistent parent node %2$s', $migration->name, $parent));
        endif;

        // add to the family tree of both the child and parent
        $this->node_family_tree[$child]->add_parent($this->node_family_tree[$parent]);
        $this->node_family_tree[$parent]->add_child($this->node_family_tree[$child]);
    }

    /**
     * This is a list of all the migrations that are the latest, that is no other dependency depends on them
     */
    public function leaf_nodes()
    {
        $leaves = [];

        foreach ($this->nodes as $name => $migration) :

            // get the nodes  children
            $children = $this->node_family_tree[$name]->children;

            // if not children exist this must be the latest migration
            if (empty($children)):
                $leaves[] = $name;
        endif;

        endforeach;

        return $leaves;
    }

    /**
     * Returns the lineage of the node, starting with the oldest (root node) upto the node.
     * This method puts the current node as first in array index 0, and the older being in the other end
     * @param $node
     * @return mixed
     * @throws NotFound
     */
    public function before_lineage($node)
    {
        // todo check for cyclic
        if (!array_key_exists($node, $this->nodes)):
            throw new NotFound(sprintf("Migration with the name %s does not exist", $node));
        endif;

        return $this->node_family_tree[$node]->ancestors();
    }

    /**
     * Get All nodes that depend on the existence of this node.
     * This method puts the current node as first in array index 0, and the older being in the other end
     * @param $node
     * @return mixed
     * @throws NotFound
     */
    public function after_lineage($node)
    {
        if (!array_key_exists($node, $this->nodes)):
            throw new NotFound(sprintf("Migration with the name %s does not exist", $node));
        endif;

        return $this->node_family_tree[$node]->descendants();
    }

    /**
     * This is a list of all the migrations that were the first, i.e they don't depend on any other migrations
     */
    public function root_nodes()
    {
        $root = [];
        foreach ($this->nodes as $name => $migration) :
            // get the nodes  parent
            $parents = $this->node_family_tree[$name]->parent;

            // if no parent exist this must be the first migration aka adam/eve which ever tickles your fancy
            if (empty($parents)):
                $root[] = $name;
        endif;

        endforeach;
        return $root;
    }

    public function get_project_state()
    {
        $leaves = $this->leaf_nodes();

        $state = new ProjectState();
        if (empty($leaves)):
            return $state;
        endif;

        // from the leave go up its family tree though its parents and ancestors until we get to the root_node.
        // this way we get the full lineage we need to follow to get to this leaf from root_node to leaf_node
        // we use this lineage to apply migrations in database
        $lineage = [];
        foreach ($leaves as $leaf) :
            // get lineage
            $lineage_members = $this->before_lineage($leaf);


        foreach ($lineage_members as $i => $l_member) :

                if (in_array($l_member, $lineage)):
                    continue;
        endif;
        $lineage[] = $l_member;
        endforeach;

        endforeach;

        // use the lineage to get the project state based on the migrations.
        foreach ($lineage as $member) :
            $migration = $this->nodes[$member];

        $state = $migration->update_state($state);

        endforeach;

        return $state;
    }
}
