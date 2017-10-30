<?php


require_once("vendor/autoload.php");
require_once("src/IJobSearchNotifier.php");
require_once("src/JobSearchNotifier.php");


class JobSearchNotifierTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {

    }

    public function tearDown()
    {

    }

    public function testMinimumViableTest()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertTrue(true, "true didn't end up being false!");
    }

    public function testJobSearchNotifier()
    {
        $jsn = new \kdaviesnz\jobsearchnotifier\JobSearchNotifier(array("php", "javascript", "wordpress"));

        $client = new \GuzzleHttp\Client();
        $fetch = $jsn->fetch("php", $client);

        $jobs = $jsn->getJobs();
        var_dump($jobs);

        $jsn->sendMail("me@example.com");

    }

}

