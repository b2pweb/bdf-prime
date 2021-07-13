<?php

namespace Bdf\Prime\Query\Expression;

@trigger_error("Since ".__NAMESPACE__." 1.2: Match class is now deprecated. Use FullTextMatch instead of.", E_USER_DEPRECATED);

class_alias(FullTextMatch::class, __NAMESPACE__.'\\Match');
