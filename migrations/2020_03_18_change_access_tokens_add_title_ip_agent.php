<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addColumns('access_tokens', [
    'title' => ['string', 'length' => 100, 'nullable' => true],
    'last_ip_address' => ['string', 'length' => 45, 'nullable' => true],
    'last_user_agent' => ['string', 'length' => 100, 'nullable' => true],
]);
