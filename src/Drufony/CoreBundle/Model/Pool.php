<?php

namespace Drufony\CoreBundle\Model;

/**
 * ItemAlreadyExists class implements the exception that will
 * be thrown when an attempt to push a repeated item occurs.
 */

class ItemAlreadyExists extends \Exception{
    /**
     * Constructs an ItemAlreadyExists exception
     *
     * @param string $message
     * @param int $code
     * @param Exception $previous
     * @return void
     */

    public function __construct($message, $code = -1, Exception $previous = null) {
        l('DEBUG', 'function: __construct');
        l('WARNING', 'Building ItemAlreadyExists exception.');
        parent::__construct($message, $code, $previous);
    }

    /**
     * Sets the conversion from ItemAlreadyExists to string.
     *
     * @return void
     */

    public function __toString() {
        l('DEBUG', 'function: __toString');
        return __CLASS__ . "[" . $this->code . "]: " . $this->message . "\n";
    }
}


/**
 * Pool class implements the set of relations that general elements can have
 * to other objects such as users, taxonomies, vocabulary or nodes.
 */

class Pool {

    /**
     * Array of the element to relate.
     * @var integer
     */

    protected $id;

    /**
     * Array of relations in the pool.
     * @var array
     */

    protected $items;

    /**
     * Type of the pool.
     * @var string
     */

    protected $type;

    /**
     * Number of elements in the pool.
     * @var int
     */

    protected $itemsCount;

    /**
     * Element of the pool where the pointer is.
     * @var int
     */

    protected $currentItemId;

    /**
     * Whether the pool is a stack.
     * @var boolean
     */

    protected $stack;

    /**
     * The average value of the relations in the pool.
     *
     * @var float
     */

    protected $avg;

    /**
     * The median value of the relations in the pool.
     *
     * @var int
     */


    protected $med;

    /**
     * The mode value of the relations in the pool.
     *
     * @var int
     */

    protected $mode;

    /**
     * the maximum value of the relations in the pool.
     *
     * @var int
     */

    protected $max;

    /**
     * The minimum value of the relations in the pool.
     *
     * @var int
     */

    protected $min;


    /**
     * Constructs a Pool object.
     * @param string $poolType; Defines the type of the pool according to the types in the application.
     * @param int $id; Indicates the identifier for the object that will be related to the nodes.
     */

    public function __construct($poolName, $id){
        l('DEBUG', 'function:__construct');
        $this->type = $poolName;
        $existent = $this->get($poolName, $id);
        $this->items = ($existent) ? $existent['items'] : array();
        $this->stack = false;
        $this->id = $id;
        $this->itemsCount = count($this->items);
        $this->currentItemId = ($this->itemsCount) ? 0 : null;
    }

    /**
     * Inserts an element in the pool.
     * @param int $id; The identifier of the element to add.
     */

    public function push($id, $value = 0, $status = 1) {
        l('DEBUG', 'function:push');
        foreach($this->items as $item) {
            if($item->id == $id) {
                l('ERROR', "Item $id already exists.");
                throw new ItemAlreadyExists("Item $id is already related to this pool ({$this->id}, {$this->type})");
                return false;
            }
        }
        $this->items[] = (object)array('id' => $id, 'value' => $value, 'status' => $status);
        $this->itemsCount = count($this->items);
        $this->currentItemId = (isset($this->items[$this->currentItemId])) ? $this->currentItemId : (($this->stack) ? count($this->items) - 1  : 0);
        $this->insertRelation($id, $value, $status);
        $this->_cleanStats();
    }

    /**
     * Removes the element which the cursor is on.
     *
     * @return void
     */

    public function remove() {
        l('DEBUG', 'function:remove');
        $item = null;

        if(isset($this->items[$this->currentItemId])) {
            $item = $this->items[$this->currentItemId];
            l('INFO', 'Relation between ' . $this->id . ' and ' . $item->id . ' deleted in pool' . $this->type);
            unset($this->items[$this->currentItemId]);
            $this->deleteRelation($item->id);
            $this->items = array_values($this->items);
            $this->itemsCount = count($this->items);
            if($this->stack) {
                l('DEBUG', 'The pool is a stack');
                if(isset($this->items[$this->currentItemId - 1])) {
                    l('DEBUG', 'next position is defined');
                    $this->currentItemId--;
                }
                else {
                    l('DEBUG', 'next position is not defined');
                    if(!empty($this->items)) {
                        l('DEBUG', 'There are elements in the pool');
                        $keys = array_keys($this->items);
                        $this->currentItemId = array_pop($keys);
                    }
                    else {
                        l('DEBUG', 'There are no elements in the pool');
                        $this->currentItemId = null;
                    }
                }
            }
            else {
                l('DEBUG', 'The pool is a queue');
                if(isset($this->items[$this->currentItemId + 1])) {
                    l('DEBUG', 'next position is defined');
                    $this->currentItemId++;
                }
                else {
                    l('DEBUG', 'next position is not defined');
                    if(!empty($this->items)) {
                        l('DEBUG', 'There are elements in the pool');
                        $keys = array_keys($this->items);
                        $this->currentItemId = array_shift($keys);
                    }
                    else {
                        l('DEBUG', 'There are no elements in the pool');
                        $this->currentItemId = null;
                    }
                }
            }
        }

        l('INFO', 'Stats has been cleant');
        $this->_cleanStats();
        return $item;
    }

    /**
     * Gets and pops an element out of the pool. It will pop it from the front or the tail depending on the mode (queue, stack).
     * @return int; The popped element.
     */

    public function pop() {
        l('DEBUG', 'function:pop');
        $keys = array_keys($this->items);
        if($this->stack) {
            l('DEBUG', 'Pool is a stack. Item will be deleted from the head.');
            $this->currentItemId = array_pop($keys);
        }
        else {
            l('DEBUG', 'Pool is a queue. Item will be deleted from the tail.');
            $this->currentItemId = array_shift($keys);
        }

        return $this->remove();
    }

    /**
     * Sets a pool as a stack.
     * @return boolean; True if success.
     */

    public function setStack() {
        l('DEBUG', 'function:setStack');
        $keys = array_keys($this->items);
        $this->currentItemId = array_pop($keys);
        l('INFO', 'Pool set as stack.');
        return $this->stack = true;
    }

    /**
     * Moves the cursor to the initial position. This will be the last element if the pool is a stack, and the first element if not.
     * @return void
     */

    public function rewind() {
        l('DEBUG', 'function:rewind');
        if(empty($this->items)) {
            $this->currentItemId = null;
        }
        else {
            if($this->stack) {
                l('DEBUG', 'Pool is a stack. Cursor will be positioned on the head');
                $keys = array_keys($this->items);
                $this->currentItemId = array_pop($keys);
            }
            else {
                l('DEBUG', 'Pool is a queue. Cursor will be positioned on the tail.');
                $keys = array_keys($this->items);
                $this->currentItemId = array_shift($keys);
            }
        }
    }

    /**
     * Gets the next element in the pool. It will get it from the front or the tail depending on the mode (queue, stack).
     * @return int; The next element in the pool.
     */

    public function next() {
        l('DEBUG', 'function:next');
        if(!isset($this->items[$this->currentItemId]) or empty($this->items)) {
            l('DEBUG', 'Cursor set to null');
            $result = null;
            $this->currentItemId = null;
        }
        else if($this->stack) {
            l('DEBUG', 'Pool is a stack');
            if(isset($this->items[$this->currentItemId - 1])) {
                $result = $this->items[$this->currentItemId - 1];
                $this->currentItemId--;
            }
            else {
                l('DEBUG', 'Cursor set to null');
                $result = null;
                $this->currentItemId = null;
            }
        }
        else {
            l('DEBUG', 'Pool is a queue');
            if(isset($this->items[$this->currentItemId + 1])) {
                $result = $this->items[$this->currentItemId + 1];
                $this->currentItemId++;
            }
            else {
                l('DEBUG', 'Cursor set to null');
                $result = null;
                $this->currentItemId = null;
            }
        }

        return $result;
    }

    /**
     * Gets the element of the pool the pointer is on.
     * @return int; The element in the position where the pointer is.
     */

    public function current() {
        l('DEBUG', 'function:current');
        return (isset($this->items[$this->currentItemId])) ? $this->items[$this->currentItemId] : null;
    }

    /**
     * Seeks the given element in the items array, and moves the cursor to its position, or sets it to null if unexistent.
     *
     * @param mixed $id
     * @return void
     */

    public function seek($id) {
        l('DEBUG', 'function:seek');
        $items_ids = array();
        foreach($this->items as $key => $element) {
            if($element->id == $id) {
                l('DEBUG', 'Item found. Cursor set to its key.');
                $this->currentItemId = $key;
                return true;
            }
        }
        l('WARNING', 'Item not found. Cursor set to null');
        $this->currentItemId = null;
        return false;
    }

    /**
     * Updates the value of the referenced pool.
     *
     * @param mixed $value
     * @return bool
     */

    public function updateValue($value) {
        l('DEBUG', 'function:updateValue');
        if(!is_null($this->currentItemId)) {
            l('INFO', 'Value updated.');
            $this->items[$this->currentItemId]->value = $value;
            return $this->updateItem($this->id, $this->type, $this->currentItemId);
        }
    }

    /**
     * Updates the status of the referenced pool.
     *
     * @param mixed $status
     * @return bool
     */

    public function updateStatus($status) {
        l('DEBUG', 'function:updateStatus');
        if(!is_null($this->currentItemId)) {
            l('INFO', 'Status updated.');
            $this->items[$this->currentItemId]->status = $status;
            return $this->updateItem($this->id, $this->type, $this->currentItemId);
        }
    }

    /**
     * Counts the elements in the pool.
     * @return int; Number of elements in the pool.
     */

    public function count() {
        l('DEBUG', 'function:count');
        return $this->itemsCount;
    }

        /**
         * Gets the type of the pool.
         * @return string; The type of the pool.
         */

    public function getType() {
        l('DEBUG', 'function:getType');
        return $this->type;
    }

    /**
     * Gets whether the pool is a stack or not.
     * @return boolean; True if the pool is a stack.
     */

    public function isStack() {
        l('DEBUG', 'function:isStack');
        return $this->stack;
    }

    /**
     * Gets whether the pool is a queue or not.
     * @return boolean; True if the pool is a queue.
     */

    public function isQueue() {
        l('DEBUG', 'function:isQueue');
        return !$this->stack;
    }

    /**
     * fetches a page from the items contained in the pool.
     *
     * @param mixed $page; The page to fetch.
     * @param mixed $size; The number of elements to fetch.
     * @param mixed $status; The status of the items.
     * @return void
     */

    public function getPage($page, $size, $status = null) {
        $initial = ($page - 1) * $size;
        $initial_items = $this->items;
        if(!is_null($status)) {
            $items = array_filter($initial_items, function($obj) use ($status) {
                if(isset($obj->status) and $obj->status == $status) {
                    return true;
                }
                return false;
            });
        }
        else {
            $items = $initial_items;
        }

        $pages_array = array_chunk($items, $size);
        $elements = (isset($pages_array[$page - 1])) ? $pages_array[$page - 1] : null;

        return $elements;
    }

    /**
     * Gets the mode value from the values of the items in the pool.
     *
     * @return int
     */

    public function getMode() {
        l('DEBUG', 'function:getMode');
        if($this->mode) {
            $result = $this->mode;
        }
        else if(!empty($this->items)){
            l('DEBUG', 'Calculating.');
            $values = array();

            foreach($this->items as $item) {
                $values[] = $item->value;
            }

            $count_values = array_count_values($values);
            $results = array_keys($count_values, max($count_values));
            $result = $results[0];
        }
        else {
            l('WARNING', 'There are no items in the pool. Impossible to calculate.');
            $result = null;
        }
        return $result;
    }

    /**
     * gets the median value from the values of the items in the pool.
     *
     * @return int
     */

    public function getMed() {
        l('DEBUG', 'function:getMed');
        if($this->med) {
            $result = $this->med;
        }
        else if(!empty($this->items)) {
            l('DEBUG', 'Calculating.');
            $values = array();
            foreach($this->items as $item) {
                $values[] = $item->value;
            }
            rsort($values);
            $middle = round(count($values) / 2);
            $result  = $values[$middle-1];
        }
        else {
            l('WARNING', 'There are no items in the pool. Impossible to calculate.');
            $result = null;
        }

        return $result;
    }

    /**
     * Gets the maximum value from the values of the items in the pool.
     *
     * @return int
     */

    public function getMax() {
        l('DEBUG', 'function:getMax');
        if(!$this->max) {
            l('DEBUG', 'Calculating.');
            $values = array();
            foreach($this->items as $item) {
                $values[] = $item->value;
            }

            $result = !empty($values) ? max($values) : null;
        }
        else {
            $result = $this->max;
        }

        return $result;
    }

    /**
     * Gets the minimum value from the values of the items in the pool.
     *
     * @return int
     */

    public function getMin() {
        l('DEBUG', 'function:getMin');
        if(!$this->min) {
            l('DEBUG', 'Calculating.');
            $values = array();
            foreach($this->items as $item) {
                $values[] = $item->value;
            }

            $result = !empty($values) ? min($values) : null;
        }
        else {
            $result = $this->min;
        }

        return $result;
    }

    /**
     * gets the average value from the values of the items in the pool.
     *
     * @return float
     */

    public function getAvg() {
        l('DEBUG', 'function:getAvg');
        if($this->avg) {
            l('DEBUG', 'Calculating.');
            $result = $this->avg;
        }
        else if(!empty($this->items)) {
            $values = array();
            foreach($this->items as $item) {
                $values[] = $item->value;
            }

            $result = array_sum($values)/count($values);
        }
        else {
            l('WARNING', 'There are no items in the pool. Impossible to calculate.');
            $result = null;
        }

        return $result;
    }

    /**
     * Sets all the stats attributes to null.
     *
     * @return void
     */

    private function _cleanStats() {
        l('DEBUG', 'function:__cleanStats');
        l('DEBUG', 'All stats set to null');
        $this->avg = null;
        $this->med = null;
        $this->mode = null;
        $this->min = null;
        $this->max = null;
    }

    /**
     * getPool
     *
     * Gets all elements from a pool type
     *
     * @param string $poolType allowed values { nodePool, sessionPool, termPool, userPool }
     * @param string $type pool type
     * @param int $oid object id
     * @param int $status if results must be filtered
     * @param int $page if results will be paged
     * @param int $itemsPerPage number of elements by page
     * @return array with ids
     */
    static public function getPool($poolType, $type, $oid, $status = NULL, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $results = array();
        $pool = self::_getPoolObject($poolType, $type, $oid);
        $pool->rewind();
        if ($page) {
            $items = $pool->getPage($page, $itemsPerPage, $status);
            $results = array_map(function($item) { return $item->id; }, $items);
        }
        else {
            do {
                if (!is_null($pool->current()) && (is_null($status) || $pool->current()->status == $status)) {
                    $results[] = $pool->current()->id;
                }
            } while($pool->next());
        }

        return $results;
    }

    /**
     * getPoolItem
     *
     * Gets a single item pool given an id
     *
     * @param string $poolType allowed values { nodePool, sessionPool, termPool, userPool }
     * @param string $type pool type
     * @param int $oid object id
     * @param mixed $id id of pool item
     * @return stdClass object
     */
    static public function getPoolItem($poolType, $type, $oid, $id) {
        $pool = self::_getPoolObject($poolType, $type, $oid);
        $result = array();
        if ($pool->seek($id)) {
            $result = $pool->current();
        }

        return $result;
    }

    /**
     * removeFromPool
     *
     * Removes an item from pool
     *
     * @param string $poolType allowed values { nodePool, sessionPool, termPool, userPool }
     * @param string $type pool type
     * @param int $oid object id
     * @param int $id element to remove
     * @return void
     */
    static public function removeFromPool($poolType, $type, $oid, $id) {
        $pool = self::_getPoolObject($poolType, $type, $oid);
        if ($pool->seek($id)) {
            $pool->remove();
        }
    }

    /**
     * updateStatusFromPool
     *
     * Updates status to an item from pool
     *
     * @param string $poolType allowed values { nodePool, sessionPool, termPool, userPool }
     * @param string $type pool type
     * @param int $oid object id
     * @param int $id element to remove
     * @param int $status status to update
     * @return void
     */
    static public function updateStatusFromPool($poolType, $type, $oid, $id, $status) {
        $pool = self::_getPoolObject($poolType, $type, $oid);

        if ($pool->seek($id)) {
            $pool->updateStatus($status);
        }
    }

    /**
     * addToPool
     *
     * Adds an element or update an existent item to pool
     *
     * @param string $poolType allowed values { nodePool, sessionPool, termPool, userPool }
     * @param string $type pool type
     * @param int $oid object id
     * @param int $id element to remove
     * @param int $value value to add
     * @param int $status status to add
     * @return void
     */
    static public function addToPool($poolType, $type, $oid, $id, $value = 0, $status = 1) {
        $pool = self::_getPoolObject($poolType, $type, $oid);
        if ($pool->seek($id)) {
            $pool->updateValue($value);
        }
        else {
            $pool->push($id, $value, $status);
        }
    }

    /**
     * _getPoolObject
     *
     * Gets pool object depending of pool type given
     *
     * @param string $poolType allowed values { nodePool, sessionPool, termPool, userPool }
     * @param string $type pool type
     * @param int $oid object id
     * @return Pool object
     */
    static private function _getPoolObject($poolType, $type, $oid) {
        switch(strtolower($poolType)) {
            case 'nodepool':
                $pool = new NodePool($type, $oid);
                break;
            case 'sessionpool':
                $pool = new SessionPool($type, $oid);
                break;
            case 'termpool':
                $pool = new TermPool($type, $oid);
                break;
            case 'userpool':
                $pool = new UserPool($type, $oid);
                break;
            default:
                $pool = null;
        }

        return $pool;
    }
}
