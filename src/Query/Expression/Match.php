<?php

namespace Bdf\Prime\Query\Expression;

trigger_deprecation(__NAMESPACE__, '1.2', 'Match class is now deprecated. Use FullTextMatch instead of.');

class_alias(FullTextMatch::class, __NAMESPACE__.'\\Match');
