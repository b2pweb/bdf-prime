<?php

namespace Schema\Visitor;

use Bdf\Prime\Admin;
use Bdf\Prime\Commit;
use Bdf\Prime\Company;
use Bdf\Prime\Customer;
use Bdf\Prime\CustomerPack;
use Bdf\Prime\Developer;
use Bdf\Prime\Document;
use Bdf\Prime\Faction;
use Bdf\Prime\Integrator;
use Bdf\Prime\Location;
use Bdf\Prime\Pack;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Project;
use Bdf\Prime\ProjectIntegrator;
use Bdf\Prime\Schema\Transformer\Doctrine\TableTransformer;
use Bdf\Prime\Schema\Visitor\Graphviz;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;
use Bdf\Prime\User;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\TestCase;

class GraphvizTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->configurePrime();
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_legacy_visitor()
    {
        $visitor = new Graphviz();
        $this->schema()->visit($visitor);

        $output = $visitor->getOutput();
        $id = substr($output, 9, 40);

        $this->assertEquals(<<<DOT
digraph "$id" {
graph [fontname="helvetica", fontsize=12];
node [fontname="helvetica", fontsize=12];
edge [fontname="helvetica", fontsize=12];
test_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">test_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">foreign_key</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">date_insert</td><td border="0" align="left"><font point-size="10">datetime</font></td></tr></table>> shape=plaintext ]
test_:colforeign_key:se -> foreign_:colpk_id:se [dir=back arrowtail=dot arrowhead=normal ]
foreign_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">foreign_</td></tr><tr><td border="0" align="left"><b>pk_id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name_</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">city</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
foreign__seq [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">foreign__seq</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr></table>> shape=plaintext ]
document_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">document_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">customer_id</td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">uploader_type</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">uploader_id</td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">contact_name</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">contact_address</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">contact_city</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
document_:colcustomer_id:se -> customer_:colid_:se [dir=back arrowtail=dot arrowhead=normal ]
user_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">user_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">customer_id</td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">faction_id</td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">name_</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">roles_</td><td border="0" align="left"><font point-size="10">text</font></td></tr></table>> shape=plaintext ]
user_:colcustomer_id:se -> customer_:colid_:se [dir=back arrowtail=dot arrowhead=normal ]
user_:colfaction_id:se -> faction_:colid_:se [dir=back arrowtail=dot arrowhead=normal ]
adminuser_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">adminuser_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">faction_id</td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">name_</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">roles_</td><td border="0" align="left"><font point-size="10">text</font></td></tr></table>> shape=plaintext ]
adminuser_:colfaction_id:se -> faction_:colid_:se [dir=back arrowtail=dot arrowhead=normal ]
faction_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">faction_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">name_</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">enabled_</td><td border="0" align="left"><font point-size="10">boolean</font></td></tr><tr><td border="0" align="left">domain_</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
customer_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">customer_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">parent_id</td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">name_</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
customer_:colparent_id:se -> customer_:colid_:se [dir=back arrowtail=dot arrowhead=normal ]
customer_seq_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">customer_seq_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr></table>> shape=plaintext ]
location_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">location_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">address_</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">city_</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
location_:colid_:se -> customer_:colid_:se [dir=back arrowtail=dot arrowhead=normal ]
pack_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">pack_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name_</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
customer_pack_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">customer_pack_</td></tr><tr><td border="0" align="left"><b>customer_id</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left"><b>pack_id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr></table>> shape=plaintext ]
project_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">project_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
commit_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">commit_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">project_id</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">message</td><td border="0" align="left"><font point-size="10">text</font></td></tr><tr><td border="0" align="left">author_id</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">author_type</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
commit_:colproject_id:se -> project_:colid:se [dir=back arrowtail=dot arrowhead=normal ]
developer_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">developer_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">project_id</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">company_id</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">lead</td><td border="0" align="left"><font point-size="10">boolean</font></td></tr></table>> shape=plaintext ]
developer_:colproject_id:se -> project_:colid:se [dir=back arrowtail=dot arrowhead=normal ]
developer_:colcompany_id:se -> company_:colid:se [dir=back arrowtail=dot arrowhead=normal ]
integrator_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">integrator_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">company_id</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
integrator_:colcompany_id:se -> company_:colid:se [dir=back arrowtail=dot arrowhead=normal ]
project_integrator_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">project_integrator_</td></tr><tr><td border="0" align="left"><b>projectId</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left"><b>integratorId</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr></table>> shape=plaintext ]
company_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">company_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
}
DOT
            , $output
);
    }

    public function test_onSchema()
    {
        $visitor = new Graphviz();
        $visitor->onSchema($this->schema());

        $output = $visitor->getOutput();
        $id = substr($output, 9, 40);

        $this->assertEquals(<<<DOT
digraph "$id" {
graph [fontname="helvetica", fontsize=12];
node [fontname="helvetica", fontsize=12];
edge [fontname="helvetica", fontsize=12];
test_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">test_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">foreign_key</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">date_insert</td><td border="0" align="left"><font point-size="10">datetime</font></td></tr></table>> shape=plaintext ]
test_:colforeign_key:se -> foreign_:colpk_id:se [dir=back arrowtail=dot arrowhead=normal ]
foreign_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">foreign_</td></tr><tr><td border="0" align="left"><b>pk_id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name_</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">city</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
foreign__seq [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">foreign__seq</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr></table>> shape=plaintext ]
document_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">document_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">customer_id</td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">uploader_type</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">uploader_id</td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">contact_name</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">contact_address</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">contact_city</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
document_:colcustomer_id:se -> customer_:colid_:se [dir=back arrowtail=dot arrowhead=normal ]
user_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">user_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">customer_id</td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">faction_id</td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">name_</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">roles_</td><td border="0" align="left"><font point-size="10">text</font></td></tr></table>> shape=plaintext ]
user_:colcustomer_id:se -> customer_:colid_:se [dir=back arrowtail=dot arrowhead=normal ]
user_:colfaction_id:se -> faction_:colid_:se [dir=back arrowtail=dot arrowhead=normal ]
adminuser_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">adminuser_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">faction_id</td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">name_</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">roles_</td><td border="0" align="left"><font point-size="10">text</font></td></tr></table>> shape=plaintext ]
adminuser_:colfaction_id:se -> faction_:colid_:se [dir=back arrowtail=dot arrowhead=normal ]
faction_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">faction_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">name_</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">enabled_</td><td border="0" align="left"><font point-size="10">boolean</font></td></tr><tr><td border="0" align="left">domain_</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
customer_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">customer_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">parent_id</td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">name_</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
customer_:colparent_id:se -> customer_:colid_:se [dir=back arrowtail=dot arrowhead=normal ]
customer_seq_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">customer_seq_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr></table>> shape=plaintext ]
location_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">location_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left">address_</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">city_</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
location_:colid_:se -> customer_:colid_:se [dir=back arrowtail=dot arrowhead=normal ]
pack_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">pack_</td></tr><tr><td border="0" align="left"><b>id_</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name_</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
customer_pack_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">customer_pack_</td></tr><tr><td border="0" align="left"><b>customer_id</b></td><td border="0" align="left"><font point-size="10">bigint</font></td></tr><tr><td border="0" align="left"><b>pack_id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr></table>> shape=plaintext ]
project_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">project_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
commit_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">commit_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">project_id</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">message</td><td border="0" align="left"><font point-size="10">text</font></td></tr><tr><td border="0" align="left">author_id</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">author_type</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
commit_:colproject_id:se -> project_:colid:se [dir=back arrowtail=dot arrowhead=normal ]
developer_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">developer_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">project_id</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">company_id</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">lead</td><td border="0" align="left"><font point-size="10">boolean</font></td></tr></table>> shape=plaintext ]
developer_:colproject_id:se -> project_:colid:se [dir=back arrowtail=dot arrowhead=normal ]
developer_:colcompany_id:se -> company_:colid:se [dir=back arrowtail=dot arrowhead=normal ]
integrator_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">integrator_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">company_id</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
integrator_:colcompany_id:se -> company_:colid:se [dir=back arrowtail=dot arrowhead=normal ]
project_integrator_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">project_integrator_</td></tr><tr><td border="0" align="left"><b>projectId</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left"><b>integratorId</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr></table>> shape=plaintext ]
company_ [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">company_</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">name</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
}
DOT
            , $output
);
    }

    private function schema(): Schema
    {
        $tables = [];
        $entities = [
            TestEntity::class,
            TestEmbeddedEntity::class,
            Document::class,
            User::class,
            Admin::class,
            Faction::class,
            Customer::class,
            Location::class,
            Pack::class,
            CustomerPack::class,
            Project::class,
            Commit::class,
            Developer::class,
            Integrator::class,
            ProjectIntegrator::class,
            Company::class,
        ];

        foreach ($entities as $entity) {
            $schemaManager = $this->prime()->repository($entity)->schema(true);
            $platform = $this->prime()->repository($entity)->connection()->platform();

            $table = $schemaManager->table(true);
            $tables[$table->name()] = (new TableTransformer($table, $platform))->toDoctrine();

            if (($sequence = $schemaManager->sequence()) !== null) {
                $tables[$sequence->name()] = (new TableTransformer($sequence, $platform))->toDoctrine();
            }
        }

        return new Schema($tables);
    }
}
