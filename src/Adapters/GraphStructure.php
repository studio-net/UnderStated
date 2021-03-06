<?php

namespace OverStated\Adapters;

use Fhaculty\Graph\Graph;
use OverStated\Contracts\MachineDriven;
use OverStated\Contracts\StructureInterface;
use OverStated\Machine;
use OverStated\States\State;
use OverStated\Transitions\Transition;
use OverStated\Exceptions\TransitionException;

/**
 * Class GraphStructure
 * @package OverStated\Adapters
 */
class GraphStructure implements StructureInterface, MachineDriven {

	const VERTEX_ATTRIBUTE = 'state';
	const EDGE_ATTRIBUTE = 'transition';

	/**
	 * @var Machine
	 */
	protected $machine;

	/**
	 * @var Graph
	 */
	protected $graph;

	/**
	 * @var null|int
	 */
	protected $initial;

	/**
	 * @var array
	 */
	protected $transitions = [];

	/**
	 * @param Graph $graph
	 */
	public function __construct(Graph $graph) {
		$this->graph = $graph;
	}

	/**
	 * @param $id
	 * @param State $state
	 * @param int $location
	 */
	public function addState(State $state, $location = 0) {
		$id = $state->getId();

		if ($this->graph->getVertices()->isEmpty() || $location === 1) {
			$this->initial = $id;
		}

		$vertex = $this->graph->createVertex($id);

		$vertex->setAttribute(self::VERTEX_ATTRIBUTE, $state);

		$state->setVertex($vertex);

		$state->setMachine($this->machine);
	}

	/**
	 * @param string $id
	 * @return State
	 */
	public function getState($id) {
		return $this->getVertex($id)->getAttribute('state');
	}


	/**
	 * @param string $id
	 * @return Transition
	 */
	public function getTransition($id) {
		if (!isset($this->transitions[$id])) {
			throw new TransitionException("Transition $id not found");
		}
		return $this->transitions[$id];
	}

	/**
	 * @param $id
	 * @return \Fhaculty\Graph\Vertex
	 */
	protected function getVertex($id) {
		return $this->graph->getVertex($id);
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @return bool
	 */
	public function canTransitionFrom($from, $to) {
		return $this->getVertex($from)
				  ->hasEdgeTo($this->getVertex($to));
	}

	/**
	 * @param string $state
	 * @return string[]
	 */
	public function getTransitionsFrom($state) {
		$transitions = [];
		$edges = $this->getVertex($state)
					 ->getEdgesOut();
		foreach ($edges as $edge) {
			$transitions[] = $edge->getAttribute(SELF::EDGE_ATTRIBUTE);
		}
		return $transitions;
	}

	/**
	 * @param Transition $transition
	 * @return mixed
	 */
	public function addTransition($transition) : \Fhaculty\Graph\Set\Edges {
		$to = $this->getVertex($transition->getTo());

		$this->transitions[$transition->getSlug()] = $transition;

		foreach ($transition->getFrom() as $origin) {
			$from = $this->getVertex($origin);

			if ($transition->isUndirected()) {
				$edge = $from->createEdge($to);
			} else {
				$edge = $from->createEdgeTo($to);
			}

			$edge->setAttribute(SELF::EDGE_ATTRIBUTE, $transition);
		}
		return $this->getVertex($transition->getTo())->getEdgesIn();
	}

	/**
	 * Set the machine instance to the state
	 *
	 * @param Machine $machine
	 */
	public function setMachine(Machine $machine) {
		$this->machine = $machine;
	}

	/**
	 * Get the initial state
	 *
	 * @param null $override
	 * @return mixed
	 */
	public function getInitialState($override = null) {
		return $this->getState($override ? : $this->initial);
	}

}
