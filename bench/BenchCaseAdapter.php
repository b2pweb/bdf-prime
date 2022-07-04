<?php

/**
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 * @BeforeClassMethods({"setUpBeforeClass"})
 * @AfterClassMethods({"tearDownAfterClass"})
 */
class BenchCaseAdapter
{
    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass()
    {
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
    }

    /**
     * This method is called after the last test of this test class is run.
     */
    public static function tearDownAfterClass()
    {
    }
}
