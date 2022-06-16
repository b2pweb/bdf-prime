<?php

namespace Bdf\Prime\Exception;

use RuntimeException;

/**
 * EntityNotFoundException
 *
 * throws when an entity has not be found by repository
 */
class EntityNotFoundException extends RuntimeException implements PrimeException
{
}
