<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A checkout precondition the customer can act on: missing email, delivery
 * option gone, payment session out of date. The message is safe to display.
 */
final class CheckoutException extends RuntimeException {}
