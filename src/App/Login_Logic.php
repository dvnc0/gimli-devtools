<?php

/**
 * Made with Gimli Devtools
 */

declare(strict_types=1);

namespace App\Logic;

use Gimli\Application;

class Login_Logic
{
	/**
	 * __construct
	 *
	 * @param Application $Application Application instance
	 */
	public function __construct(
		public Application $Application,
	) {
	}
}
