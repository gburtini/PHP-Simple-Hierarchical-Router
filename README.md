Hierarchical Router and Template Selector
============================

_Fallback friendly routing tool._

In general, the philosophy here is to use the filesystem as the core routing functionality. The most common architecture is broadly as follows:

* Using `mod_rewrite` or similar, route all traffic to a parser and initialization script.
* Parse out the intended "route" and variables. _VALIDATE THE ROUTE AGAINST A WHITELIST._
* `Route::map` to the appropriate file (usually a 'controller' file), passing in whitelisted variables as necessary as 'parameters'.
* Perform controller action.
* `Route::map` to the appropriate template file with whitelisted variables to output the final template.


Installation
------------

Everything you need is contained within the single file ``router.php``, but the most convenient way to install is with composer:

    composer require gburtini/router

Usage
-----

Set up your valid routing types with a call to `::types`. The key is an indicator for the type and the value is the path, without trailing slash. A path of "/" is not acceptable. A path of "" is acceptable, but not recommended as, at a minimum, you could get in a infinite loop (you also would have no control over which files get routed to).

    Route::types(['controller'=>'files/controllers', 'template'=>'files/templates']);

Set up valid extensions for your files. This is a trinket of security against loading bad files. By default, this is set to ["php"].
	
    Route::extensions(["tpl", "html"]);

When you wish to invoke a route, call `::map` or `::%type name%`. `::map` will take an array of types to route to in precedence order. A single route call will only ever route to one file.

    Route::map(['controller', 'template'], 'dashboard');

If you don't need the fallback functionality (controller then template if controller doesn't exist), you can use interpreter hooks / magic methods to directly use your established types:

    Route::template("dashboard");


Security
--------

tl;dr: When in doubt, you should assume all variables you pass in to the router are untained, secure, safe. Very little validation is performed.

`Route::types` does no validation. Paths are not verified to not be dangerous, empty or root. 
`Route::map` and related functions (magic methods, `Route::mapFile`) will not prevent you from including dangerous files. Specifically:
	* If the type selected has an empty path, the path will currently begin from "/"
	* If the second parameter to map (first parameter to magic methods) is empty, the router will cowardly refuse to try to load it. 
	* If the second parameter to map contains "..", it will _navigate up the path as expected_. If this is not your desired behavior, you should whitelist. If you do not want to whitelist, the code could be modified to use `realpath` to validate the prefix.
	* The third parameter to ::map takes in a map of parameters to expand in the included file. Currently, this uses `extract(., EXTR_SKIP)` to expand the map. EXTR_SKIP means that any of the variables used within the map function (including $path, $load) cannot be passed. This is not a security risk (in the router, it could be in your code!) but you should use a prefix (or modify the code to support a more general extraction mechanism) for all variables passed through.
	* There are more considerations. Most of them related to the path. You *should read the code before using it*. It is unlikely this code will be "ready to use" for all purposes currently.    
    

License
-------
*Copyright (C) 2015 Giuseppe Burtini*

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
