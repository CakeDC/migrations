<?php
/**
 * This class is a adjacency matrix representation of a graph
 *
 * @see http://en.wikipedia.org/wiki/Depth-first_search
 * @see http://en.wikipedia.org/wiki/Topological_sorting
 */
class MigrationDependencyGraph {
/**
 * Stores the adjacency matrix
 *
 * @see http://en.wikipedia.org/wiki/Modified_adjacency_matrix
 * @var array
 * @access private
 */
	private $__adjMatrix = null;
/**
 * Stores the number of vertices in the graph
 *
 * @var integer
 * @access private
 */
	private $__vertexCount = 0;
/**
 * Stores the number of edges in the graph
 *
 * @var integer
 * @access private
 */
	private $__edgeCount = 0;
/**
 * Vertex List (the array attached to it is for the dfs algorithm)
 *
 * @var array
 * @access private
 */
	private $__vertexList = array();
/**
 * Defaults for the dfs data stored in the vertexList
 *
 * @var array
 * @access private
 */
	private $__dfsDefaults = array('color' => 0, 'discovery' => null, 'finish' => null);
/**
 * Result from topo sort (uses DFS)
 *
 * @var array
 * @access private
 */
	private $__topologicalOrder = array();
/**
 * Depth First Search time counter
 *
 * @var integer
 * @access private
 */
	private $__time = 0;
/**
 * Default Constructor
 *
 * @param array $keys
 * @access public
 */
	public function __construct($keys = array()) {
		if (count($keys) > 0) {
			$this->__vertexCount = count($keys);
			foreach ($keys as $key) {
				$this->__adjMatrix[$key] = array();
				$this->__vertexList[$key] = $this->__dfsDefaults;
			}
		}
	}
/**
 * Depth First Search
 *
 * @return array Topological Order
 * @access public
 */
	public function dfs() {
		$vertices = array_keys($this->__vertexList);
		foreach ($vertices as $key) {
			$this->__vertexList[$key]['color'] = 0;
		}
		$this->__time = 0;
		foreach ($this->__vertexList as $key => $dfsData) {
			if ($dfsData['color'] == 0) {
				$this->__dfsVisit($key);
			}
		}
		return $this->__topologicalOrder;
	}
/**
 * Private depth first search helper function
 *
 * @param string $vertex key of the vertex
 * @access private
 */
	private function __dfsVisit($vertex) {
		$colorAtStart = $this->__vertexList[$vertex]['color'];
		$this->__vertexList[$vertex]['color'] = 1;
		$this->__time++;
		$this->__vertexList[$vertex]['discovery'] = $this->__time;
		$outgoingEdges = $this->getOutgoingEdges($vertex);
		foreach ($outgoingEdges as $key) {
			if ($this->__vertexList[$key]['color'] == 0) {
				$this->__dfsVisit($key);
			}
		}
		$this->__vertexList[$vertex]['color'] = 3;
		$this->__vertexList[$vertex]['finish'] = ++$this->__time;
		if ($colorAtStart == 0) {
			array_unshift($this->__topologicalOrder, $vertex);
		}
	}
/**
 * Returns array of outgoing edges (the names of the vertex at the other end)
 *
 * @param string $vertex the key of the vertex
 * @return array
 * @access public
 */
	public function getOutgoingEdges($vertex) {
		if (!$this->vertexExists($vertex)) {
			throw new Exception('DependencyGraph::getOutgoingEdges - Invalid Vertex');
		}
		return array_keys($this->__adjMatrix[$vertex]);
	}
/**
 * Returns array of incoming edges (this is expensive
 * and should not be used if possible)
 *
 * @param string $vertex the key of the vertex
 * @return array
 * @access public
 */
	public function getIncomingEdges($vertex) {
		if (!$this->vertexExists($vertex)) {
			throw new Exception('DependencyGraph::getIncomingEdges - Invalid Vertex');
		}
		$incomingEdges = array();
		foreach ($this->__adjMatrix as $key => $edges) {
			if (array_key_exists($vertex, $edges)) {
				 $incomingEdges[] = $key;
			}
		}
		return $incomingEdges;
	}
/**
 * Adds a new vertex to the graph
 *
 * @param string $key Name of the vertex
 * @return integer the vertex count
 * @access public
 */
	public function addVertex($key) {
		$this->__adjMatrix[$key] = array();
		$this->__vertexList[$key] = $this->__dfsDefaults;
		return ++$this->__vertexCount;
	}
/**
 * Adds an edge to the graph
 *
 * @param string $v1 the key for the first vertex
 * @param string $v2 the key for the second vertex
 * @param string $value the value or weight of the edge
 * @return integer the number of edges
 * @access public
 */
	public function addEdge($v1, $v2, $value = true) {
		if (!$this->vertexExists($v1) || !$this->vertexExists($v2)) {
			throw new Exception('DependencyGraph::addEdge - Invalid Vertex');
		}
		$this->__adjMatrix[$v1][$v2] = $value;
		return ++$this->__edgeCount;
	}
/**
 * Removes an edge from the graph
 *
 * @param string $v1 the key for the first vertex
 * @param string $v2 the key for the second vertex
 * @return integer the number of edges
 * @access public
 */
	public function removeEdge($v1, $v2) {
		if (!$this->vertexExists($v1) || !$this->vertexExists($v2)) {
			throw new Exception('DependencyGraph::removeEdge - Invalid Vertex');
		}
		unset($this->__adjMatrix[$v1][$v2]);
		return --$this->__edgeCount;
	}
/**
 * Returns true if there is an edge between the
 * two vertices
 *
 * @param string $v1 the key for the first vertex
 * @param string $v2 the key for the second vertex
 * @return boolean true if the edge exists
 * @access public
 */
	public function hasEdge($v1, $v2) {
		if (!$this->vertexExists($v1) || !$this->vertexExists($v2)) {
			throw new Exception('DependencyGraph::hasEdge - Invalid Vertex');
		}
		return isset($this->__adjMatrix[$v1][$v2]);
	}
/**
 * Returns the number of edges in the graph
 *
 * @return integer number of edges
 * @access public
 */
	public function getEdgeCount() {
		return $this->__edgeCount;
	}
/**
 * Returns the number of vertices in the graph
 *
 * @return integer number of vertices
 * @access public
 */
	public function getVertexCount() {
		return $this->__vertexCount;
	}
/**
 * Returns true if the vertex exists in the graph
 *
 * @param string $vertex the key of the vertex to test
 * @return boolean true if the vertex exists
 * @access public
 */
	public function vertexExists($vertex) {
		return in_array($vertex, $this->getVertexList());
	}
/**
 * Returns list of vertex keys
 *
 * @return array list of vertices
 * @access public
 */
	public function getVertexList() {
		return array_keys($this->__vertexList);
	}
}
?>