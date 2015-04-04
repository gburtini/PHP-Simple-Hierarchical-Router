<?php
	namespace gburtini;
	class Route {
		protected static $files = array();
		protected static $extensions = array("php");
		/**
	 	 * types(map $types) - takes in an array with key as the type key and value as the path to the routing directory
		 */
		public static function types($types) {
			self::$files = $types;
		}
		/**
		 * extensions(map $extensions) - takes in an array with a list of valid autopredicted extensions for mapping, in priority order.
		 */
		public static function extensions($extensions) {
			self::$extensions = $extensions;
		}

		/*
		 * Forward the calls to the mapFile method which will fail if inappropriate. This lets you use the router as Route::template("abc");
		 * if you have defined a route type called "template".
		 */
		public static function __callStatic($name, $arguments) {
			$arguments = array_shift($name, $arguments);
			return forward_static_call_array("mapFile", $arguments);
		}

		/**
		 * mapFile(string $type, string or array $page, $parameters = array(), $action = "require") - decides what to do
		 *
		 * $page can be an array indicating a hierarchy of pages to load if each fails (doesn't exist).
		 *
		 * It is important to note that there is -no- attempt at securing any of the parameters in this function. If you 
		 * allow a page to be passed in that is ../../../../../../etc/passwd it will go ahead and attempt to load that. 
		 * Treat all variables passed in here as privileged.
		 *
		 * It is also important to note that $parameters is an associative array that gets extracted with EXTR_SKIP at the moment.
		 * Depending on your use, you may need to edit this behavior. If you make an improvement, let me know and push it
		 * back to the repository.
		 */	
		public static function mapFile($type, $page, $parameters=array(), $action="require") {
			// wrap $page in array if it isn't.
			if(!is_array($page)) 
				$page = array($page);

			if(!isset(self::$files[$type]))
				throw new Exception("Invalid type. Please specify types with a call to ::types() first.");
			
			$base = rtrim(self::$files[$type], "/");
			foreach($page as $p) {
				$p = trim($p);
				if($p == "")	// cowardly refuse to try to load empty pages.
					continue;

				foreach(self::$extensions as $ext) {
					$path = $base . "/" . $p . "." . $ext;
					$path = rtrim($path, ". ");
					if(file_exists($path))
					{
						$load = $path;
						break;
					}
				}
			}

			if(!isset($load))
				return false;

			extract($parameters, EXTR_SKIP);	// NOTE: EXTR_SKIP means that if you try to set things like $load or $path, it will fail. This could be handled by prefixing all variables in either this code or using EXTR_PREFIX_ALL.
			switch($action) {
				case "require": require $load; break;
				case "require_once": require_once $load; break;
				case "include": include $load; break;
				case "include_once": include_once $load; break;
				default: 
					if(is_callable($action))
						$action($load, $parameters); 
					else
						throw new Exception("Action isn't callable.");
				break;
			}

			return true;
		}

		/**
		 * Identical to mapFile except $types can be an array that will be searched in order. This is useful 
		 * if you want to implement a route->template system where if the route doesn't exist you skip straight
		 * to the template. For example:
		 *
		 *	Route::types(['route'=>'files/routes', 'template'=>'files/templates']);
		 *	Route::map(['route', 'template'], 'dashboard');
		 *
		 * Will load the dashboard route if it exists and then fallback on the template if it doesn't.
		 */
		public static function map($types, $pages, $parameters=array(), $action="require") {
			if(!is_array($types)) $types = array($types);

			foreach($types as $type) {
				if(false !== self::mapFile($type, $pages, $parameters, $action))
					break;
			}

			return true;
		}
	}
?>
