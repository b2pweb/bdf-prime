<?php

@trigger_error('Bdf\Prime\Schema\NullResolver is deprecated since 2.0. Use Bdf\Prime\Schema\NullStructureUpgrader instead', E_USER_DEPRECATED);
class_alias(\Bdf\Prime\Schema\NullStructureUpgrader::class, 'Bdf\Prime\Schema\NullResolver');
