<?php

@trigger_error('Bdf\Prime\Schema\ResolverInterface is deprecated since 2.0. Use Bdf\Prime\Schema\StructureUpgraderInterface instead', E_USER_DEPRECATED);
class_alias(\Bdf\Prime\Schema\StructureUpgraderInterface::class, 'Bdf\Prime\Schema\ResolverInterface');
