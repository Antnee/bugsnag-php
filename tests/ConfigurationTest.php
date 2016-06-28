<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Diagnostics;
use Bugsnag\Error;
use Bugsnag\Middleware\NotificationSkipper;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class ConfigurationTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Diagnostics */
    protected $diagnostics;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
        $this->diagnostics = new Diagnostics($this->config, new BasicResolver());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDoesNotAcceptBadApiKey()
    {
        new Configuration([]);
    }

    public function testDefaultReleaseStageShouldNotify()
    {
        $this->expectOutputString('NOTIFIED');

        $skipper = new NotificationSkipper($this->config);

        $skipper(Error::fromPHPThrowable($this->config, $this->diagnostics, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testCustomReleaseStageShouldNotify()
    {
        $this->config->appData['releaseStage'] = 'staging';

        $this->expectOutputString('NOTIFIED');

        $skipper = new NotificationSkipper($this->config);

        $skipper(Error::fromPHPThrowable($this->config, $this->diagnostics, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testCustomNotifyReleaseStagesShouldNotify()
    {
        $this->config->notifyReleaseStages = ['banana'];

        $this->expectOutputString('');

        $skipper = new NotificationSkipper($this->config);

        $skipper(Error::fromPHPThrowable($this->config, $this->diagnostics, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testBothCustomShouldNotify()
    {
        $this->config->appData['releaseStage'] = 'banana';
        $this->config->notifyReleaseStages = ['banana'];

        $this->expectOutputString('NOTIFIED');

        $skipper = new NotificationSkipper($this->config);

        $skipper(Error::fromPHPThrowable($this->config, $this->diagnostics, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testNotifier()
    {
        $this->assertSame($this->config->notifier['name'], 'Bugsnag PHP (Official)');
        $this->assertSame($this->config->notifier['url'], 'https://bugsnag.com');
    }

    public function testShouldIgnore()
    {
        $this->config->errorReportingLevel = E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED;

        $this->assertTrue($this->config->shouldIgnoreErrorCode(E_NOTICE));
    }

    public function testShouldNotIgnore()
    {
        $this->config->errorReportingLevel = E_ALL;

        $this->assertfalse($this->config->shouldIgnoreErrorCode(E_NOTICE));
    }

    public function testAppData()
    {
        $this->assertSame(['releaseStage' => 'production'], $this->config->getAppData());

        $this->config->appData['releaseStage'] = 'qa1';
        $this->config->appData['version'] = '1.2.3';
        $this->config->appData['type'] = 'laravel';

        $this->assertSame(['releaseStage' => 'qa1', 'version' => '1.2.3', 'type' => 'laravel'], $this->config->getAppData());

        $this->config->appData['type'] = null;

        $this->assertSame(['releaseStage' => 'qa1', 'version' => '1.2.3'], $this->config->getAppData());

        $this->config->appData['releaseStage'] = null;

        $this->assertSame(['releaseStage' => 'production', 'version' => '1.2.3'], $this->config->getAppData());
    }
}
