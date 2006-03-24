<?php

/**
 * Humdrum PHP MVC framework core.
 *
 * @package HUMDRUM_CORE
 * @copyright Copyright (C) 2006 Jesse Peterson.  All rights reserved.
 * @author Jesse Peterson <jpeterson275@comcast.net>
 */

/**
 * Controller component of Humdrum MVC framework.
 *
 * @see Model
 * @see View
 * @package HUMDRUM_CORE
 */
class Controller {
	/**
	 * @var array Associate array of views.
	 * @access private
	 */
	var $_views = array ();

	/**
	 * @var View
	 * @access private
	 */
	var $_defaultView;

	/**
	 * @var array
	 * @see ProcessCallback
	 * @access private
	 */
	var $_processCallbacks = array ();

	/**
	 * Add view to view list keyed by name.
	 *
	 * @param string View name.
	 * @param View View object.
	 */
	function addView ($view_key, &$view) {
		$this->_views[$view_key] =& $view;
	}

	/**
	 * @param View
	 */
	function setDefaultView (&$view) {
		$this->_defaultView =& $view;
	}

	/**
	 * Add process callback to list of processes to dispatch to during
	 * controller handling.
	 *
	 * @param ProcessCallback
	 * @see ProcessCallback
	 */
	function addProcess (&$processCallback) {
		$this->_processCallbacks[] =& $processCallback;
	}

	/**
	 * Dispatch process queue and render appropriate view.
	 *
	 * @see ProcessCallback
	 * @param Request Controller request parameters.
	 * @param Model Domain/business logic container.
	 */
	function handleRequest (&$request, &$context) {

		// cycle through our process callback queue
		foreach (array_keys ($this->_processCallbacks) as $proc_key) {

			// dispatch to and retrieve view from process callback
			$view =& $this->_processCallbacks[$proc_key]->callBack ($this, $request, $context);

			if ($this->viewExists ($view)) {

				/* dispatch to our named view and stop execution of this
				   controller */
				$this->renderView ($view, $request, $context);
				return;

			}
		}

		// if no view has been found yet attempt our default
		if ($this->defaultViewExists ())
			$this->renderDefaultView ($request, $context);
	}

	/**
	 * Test for existance of named view.
	 *
	 * @param string
	 * @return bool
	 * @access protected
	 */
	function viewExists ($view_name) {
		return isset ($this->_views[$view_name]);
	}

	/**
	 * Dispatch to and render named view.
	 *
	 * @param string
	 * @param Request Controller request parameters.
	 * @param Model Domain/business logic container.
	 * @access protected
	 */
	function renderView ($view_name, &$request, &$context) {
		$this->_views[$view_name]->display ($this, $request, $context);
	}

	/**
	 * Test for existance of default view.
	 *
	 * @return bool
	 * @access protected
	 */
	function defaultViewExists () {
		return isset ($this->_defaultView);
	}

	/**
	 * Dispatch to and render default view.
	 *
	 * @param Request Controller request parameters.
	 * @param Model Domain/business logic container.
	 * @access protected
	 */
	function renderDefaultView (&$request, &$context) {
		$this->_defaultView->display ($this, $request, $context);
	}
}

/**
 * PHP implementation of a call-back.
 *
 * A core concept in the framework is the notion of a 'Process': a
 * function or method that performs application logic possibly
 * interacting with the domain model.  This 'Process' is passed the
 * application context of the controller's notion of the model
 * or context and the controller request.  The 'Process' is expected
 * to return the name of a view to render.
 *
 * @package HUMDRUM_CORE
 */
class ProcessCallback {
	/**
	 * Callback to Process for... processing.
	 *
	 * @param Controller Source/calling controller.
	 * @param Request Controller request parameters.
	 * @param Model Domain/business logic container.
	 * @return string Name of view to dispatch to upon processing.
	 */
	function callBack (&$controller, &$request, &$context) {
	}
}

/**
 * Callback to an instantiated object method.
 *
 * @see ProcessCallback
 * @package HUMDRUM_CORE
 */
class ObjectProcessCallback extends ProcessCallback {
	/**
	 * Object to call into.  With method forms the callback.
	 * @access private
	 */
	var $_object;

	/**
	 * Method of object to call into.
	 *
	 * @access private
	 */
	var $_method;

	/**
	 * @param Object Instantiated object to call method of.
	 * @param string Name of method of object to call back.
	 */
	function ObjectProcessCallback (&$object, $method) {
		$this->_object =& $object;
		$this->_method = $method;
	}

	/**
	 * @see ProcessCallback::callBack
	 * @param Controller
	 * @param Request
	 * @param Model
	 * @return string
	 */
	function callBack (&$controller, &$request, &$context) {

		// hack to call object directly
		$method_name = $this->_method;
		return $this->_object->$method_name ($controller, $request, $context);

	}
}

/**
 * View component of Humdrum MVC framework.
 *
 * @see Model
 * @see Controller
 * @package HUMDRUM_CORE
 */
class View {
	/**
	 * Render or display output of view.
	 *
	 * @param Controller Source (calling) controller that requested this
	 *                   display.
	 * @param Request Controller request parameters.
	 * @param Model Domain/business logic container.
	 */
	function render (&$source, &$request, &$context) {
	}
}

/**
 * Controller forwarding psuedo-view.
 *
 * This view doesn't actually render any display.  Instead it forwards
 * to another (specified) controller with the request state and
 * context.
 *
 * @package HUMDRUM_CORE
 */
class ForwardView extends View {
	/**
	 * @var Controller Controller to forward request to.
	 * @access private
	 */
	var $_controller;

	/**
	 * @param Controller Controller to forward request to.
	 */
	function ForwardView (&$controller) {
		parent::View ();

		$this->_controller =& $controller;
	}

	/**
	 * Forward to instantiation-specified controller.
	 *
	 * @see View::render
	 */
	function render (&$source, &$request, &$context) {
		$this->_controller->handleRequest ($request, $context);
	}
}

/**
 * Request from which controller Processes take input from the running
 * application environment for processing.  Could be ultimately seen as
 * the "master" call client as all input comes from the request object.
 *
 * @package HUMDRUM_CORE
 */
class Request {
}

/**
 * Model component of Humdrum MVC framework.
 *
 * Typically supplies the context for most MVC operations.
 *
 * @see View
 * @see Controller
 * @package HUMDRUM_CORE
 */
class Model {
}

?>
