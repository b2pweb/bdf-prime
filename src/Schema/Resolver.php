<?php

@trigger_error('Bdf\Prime\Schema\Resolver is deprecated since 2.0. Use Bdf\Prime\Schema\RepositoryUpgrader instead', E_USER_DEPRECATED);
class_alias(\Bdf\Prime\Schema\RepositoryUpgrader::class, 'Bdf\Prime\Schema\Resolver');
