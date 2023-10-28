/* -----------------------------------------------------------------------------
	Ambient.Impact - Core - Framework global object utilities
----------------------------------------------------------------------------- */


(function() {
	'use strict';

	// Add empty methods that just return the framework instance if the browser
	// doesn't cut the mustard.
	if (!(
		AmbientImpact.mustard() &&
		'MutationObserver' in window
	)) {
		// An array of methods to create empty versions of that don't do
		// anything but return the framework instance (as expected).
		var emptyMethods = ['onGlobal', 'onGlobals'];

		for (var i = emptyMethods.length - 1; i >= 0; i--) {
			AmbientImpact.constructor.prototype[emptyMethods[i]] = function() {
				return this;
			};
		}

		return;
	}

	var	globals			= {},
		scriptObserver;

	/**
	 * Fire callbacks when a specified global object is available.
	 *
	 * @param {string} objectPath	- The path of the object as a string.
	 *
	 * @param {function} callback	- The function to call when the object
	 * 								  becomes available.
	 */
	AmbientImpact.constructor.prototype.onGlobal = function(
		objectPath, callback
	) {
		if (this.objectPathExists(objectPath)) {
			callback.call();
		} else {
			if (!globals.hasOwnProperty(objectPath)) {
				globals[objectPath] = {
					callbacks: []
				};
			}

			globals[objectPath].callbacks.push(callback);
		}

		// Return framework instance for chaining.
		return this;
	};

	/**
	 * Queue a callback to run when all specified global objects load.
	 *
	 * This is preferred over onGlobal(), which only accepts one object path.
	 *
	 * @param {array|string} objectPaths - Array of object paths, as strings, or
	 *                                     a single object path as a string.
	 *
	 * @param {function} callback	- The function to call when the specified
	 * 								  global objects are available.
	 *
	 * @see AmbientImpact.onGlobal().
	 */
	AmbientImpact.constructor.prototype.onGlobals = function(
		objectPaths, callback
	) {
		if (typeof objectPaths === 'string') {
			objectPaths = [objectPaths];
		}

		var	objectPromises	= [],
		instance			= this;

		for (var i = 0; i < objectPaths.length; i++) {
			objectPromises.push(new Promise(function(resolve, reject) {
				instance.onGlobal(objectPaths[i], resolve);
			}));
		}

		Promise.all(objectPromises).then(callback);

		// Return framework instance for chaining.
		return this;
	};

	/**
	 * Script element load handler.
	 *
	 * When triggered, this will loop through all global paths that have been
	 * queued via AmbientImpact.onGlobal()/AmbientImpact.onGlobals(). When it
	 * finds one that exists, it fires off all callbacks registered for that
	 * path and removes that path from globals.
	 *
	 * @param {Event} event
	 *
	 * @see globals
	 * @see AmbientImpact.objectPathExists()
	 * @see AmbientImpact.onGlobal()
	 * @see AmbientImpact.onGlobals()
	 */
	function scriptLoadHandler(event) {
		for (var objectPath in globals) {
			if (
				globals.hasOwnProperty(objectPath) &&
				AmbientImpact.objectPathExists(objectPath)
			) {
				var callbacks = globals[objectPath].callbacks;

				for (var i = 0; i < callbacks.length; i++) {
					callbacks[i].call();
				}

				delete globals[objectPath];
			}
		}
	}

  /**
   * All existing <script> elements currently in the DOM.
   *
   * @type {NodeList}
   */
  const existingScriptElements = document.querySelectorAll('script');

  // Attach the event handler to all existing <script> elements. This is
  // necessary for <script> elements with the 'defer' attribute because they
  // may not have loaded yet (so the global will not yet exist), but they also
  // won't be found by the MutationObserver as added nodes because they already
  // exist at the time the MutationObserver starts observing.
  for (let i = existingScriptElements.length - 1; i >= 0; i--) {
    existingScriptElements[i].addEventListener('load', scriptLoadHandler);
  }

	// MutationObserver that watches for any <script> elements that are added to
	// the document. When one appears, it attaches scriptLoadHandler() as a
	// 'load' handler.
	scriptObserver = new MutationObserver(function(mutations) {
		for (var i = mutations.length - 1; i >= 0; i--) {
			if (mutations[i].addedNodes) {
				var addedNodes = mutations[i].addedNodes;

				for (var j = addedNodes.length - 1; j >= 0; j--) {
					if (addedNodes[j].tagName === 'SCRIPT') {
						// <script> 'load' handler.
						addedNodes[j].addEventListener(
							'load', scriptLoadHandler
						);
					}
				}
			}
		}
	});

	// Start observing the document.
	scriptObserver.observe(document.documentElement, {
		childList:	true,
		subtree:	true
	});
})();
