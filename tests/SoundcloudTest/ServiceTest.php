<?php

namespace SoundcloudTest;

use Soundcloud\Service;

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Service
     */
    protected $object;

    /**
     * Allows access to a protective method
     *
     * @param string $name
     * @return \ReflectionMethod
     */
    protected static function getProtectedMethod($name)
    {
        $class = new \ReflectionClass('Soundcloud\Service');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * Provides the data for test_parseHttpHeaders
     *
     * @return array
     */
    public static function dataProviderHttpHeaders()
    {
        $rawHeaders = <<<HEADERS
HTTP/1.1 200 OK
Date: Wed, 17 Nov 2010 15:39:52 GMT
Cache-Control: public
Content-Type: text/html; charset=utf-8
Content-Encoding: gzip
Server: foobar
Content-Length: 1337
HEADERS;
        $expectedHeaders = array(
            'date' => 'Wed, 17 Nov 2010 15:39:52 GMT',
            'cache_control' => 'public',
            'content_type' => 'text/html; charset=utf-8',
            'content_encoding' => 'gzip',
            'server' => 'foobar',
            'content_length' => '1337'
        );

        return array(array($rawHeaders, $expectedHeaders));
    }

    /**
     * Provides the data for testSoundcloudInvalidHttpResponseCode
     *
     * @return array
     */
    public static function dataProviderSoundcloudInvalidHttpResponseCode()
    {
        $expectedHeaders = array(
            'server' => 'nginx',
            'content_type' => 'application/json; charset=utf-8',
            'connection' => 'keep-alive',
            'cache_control' => 'no-cache',
            'content_length' => '30'
        );

        return array(array($expectedHeaders));
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Service(
                '1337', '1337', 'http://soundcloud.local/callback'
        );
        $this->object->setAccessToken('1337');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $this->object = null;
    }

    /**
     * @covers Soundcloud\Service::getAuthorizeUrl
     */
    public function testGetAuthorizeUrl()
    {
        $this->assertEquals(
                'https://soundcloud.com/connect?client_id=1337&redirect_uri=http%3A%2F%2Fsoundcloud.local%2Fcallback&response_type=code',
                $this->object->getAuthorizeUrl()
        );
    }

    /**
     * @covers Soundcloud\Service::getAuthorizeUrl
     */
    function testGetAuthorizeUrlWithCustomQueryParameters()
    {
        $this->assertEquals(
                'https://soundcloud.com/connect?client_id=1337&redirect_uri=http%3A%2F%2Fsoundcloud.local%2Fcallback&response_type=code&foo=bar',
                $this->object->getAuthorizeUrl(array('foo' => 'bar'))
        );
        $this->assertEquals(
                'https://soundcloud.com/connect?client_id=1337&redirect_uri=http%3A%2F%2Fsoundcloud.local%2Fcallback&response_type=code&foo=bar&bar=foo',
                $this->object->getAuthorizeUrl(array('foo' => 'bar', 'bar' => 'foo'))
        );
    }

    /**
     * @covers Soundcloud\Service::getAccessTokenUrl
     */
    public function testGetAccessTokenUrl()
    {
        $this->assertEquals(
                'https://api.soundcloud.com/oauth2/token',
                $this->object->getAccessTokenUrl()
        );
    }

    /**
     * @covers Soundcloud\Service::setAccessToken
     */
    public function testSetAccessToken()
    {
        $this->object->setAccessToken('1337');
        $this->assertEquals('1337', $this->object->getAccessToken());
    }

    /**
     * @covers Soundcloud\Service::setCurlOptions
     */
    public function testSetCurlOptions()
    {
        $this->object->setCurlOptions(CURLOPT_SSL_VERIFYHOST, 0);
        $this->assertEquals(
                0, $this->object->getCurlOptions(CURLOPT_SSL_VERIFYHOST)
        );
    }

    /**
     * @covers Soundcloud\Service::setCurlOptions
     */
    public function testSetCurlOptionsArray()
    {
        $options = array(
            CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0
        );
        $this->object->setCurlOptions($options);

        foreach ($options as $key => $val) {
            $this->assertEquals(
                    $val, $this->object->getCurlOptions($key)
            );
        }
    }

    /**
     * @covers Soundcloud\Service::setRedirectUri
     */
    public function testSetRedirectUri()
    {
        $this->object->setRedirectUri('http://soundcloud.local/callback');
        $this->assertEquals(
                'http://soundcloud.local/callback',
                $this->object->getRedirectUri()
        );
    }

    /**
     * @covers Soundcloud\Service::getResponseFormat
     */
    function testDefaultResponseFormat()
    {
        $this->assertEquals(
                'application/json', $this->object->getResponseFormat()
        );
    }

    /**
     * @covers Soundcloud\Service::setResponseFormat
     */
    public function testSetResponseFormatAll()
    {
        $this->object->setResponseFormat('*');
        $this->assertEquals(
                '*/*', $this->object->getResponseFormat()
        );
    }

    /**
     * @covers Soundcloud\Service::setResponseFormat
     */
    public function testSetResponseFormatHtml()
    {
        $this->setExpectedException('Soundcloud\Exception\UnsupportedResponseFormatException');
        $this->object->setResponseFormat('html');
    }

    /**
     * @covers Soundcloud\Service::setResponseFormat
     */
    public function testSetResponseFormatJson()
    {
        $this->object->setResponseFormat('json');
        $this->assertEquals(
                'application/json', $this->object->getResponseFormat()
        );
    }

    /**
     * @covers Soundcloud\Service::setResponseFormat
     */
    public function testSetResponseFormatXml()
    {
        $this->object->setResponseFormat('xml');
        $this->assertEquals(
                'application/xml', $this->object->getResponseFormat()
        );
    }

    /**
     * @covers Soundcloud\Service::setDevelopment
     */
    public function testSetDevelopment()
    {
        $this->object->setDevelopment(true);
        $this->assertTrue($this->object->getDevelopment());
    }

    /**
     * @covers Soundcloud\Service::__construct
     */
    public function testSoundcloudMissingConsumerKeyException()
    {
        $this->setExpectedException('Soundcloud\Exception\MissingClientIdException');
        new Service('', '');
    }

    /**
     * @covers Soundcloud\Service::get
     */
    public function testSoundcloudInvalidHttpResponseCodeException()
    {
        $this->setExpectedException('Soundcloud\Exception\InvalidHttpResponseCodeException');
        $this->object->get('me');
    }

    /**
     * @covers Soundcloud\Service::_validResponseCode
     */
    public function test_validResponseCodeSuccess()
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $method = self::getProtectedMethod('_validResponseCode');
            $this->assertTrue($method->invoke($this->object, 200));
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

    /**
     * @covers Soundcloud\Service::_validResponseCode
     */
    public function test_validResponseCodeRedirect()
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $method = self::getProtectedMethod('_validResponseCode');
            $this->assertFalse($method->invoke($this->object, 301));
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

    /**
     * @covers Soundcloud\Service::_validResponseCode
     */
    public function test_validResponseCodeClientError()
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $method = self::getProtectedMethod('_validResponseCode');
            $this->assertFalse($method->invoke($this->object, 400));
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

    /**
     * @covers Soundcloud\Service::_validResponseCode
     */
    public function test_validResponseCodeServerError()
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $method = self::getProtectedMethod('_validResponseCode');
            $this->assertFalse($method->invoke($this->object, 500));
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

    /**
     * @covers Soundcloud\Service::_buildDefaultHeaders
     */
    public function test_buildDefaultHeaders()
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $this->object->setAccessToken(null);
            $method = self::getProtectedMethod('_buildDefaultHeaders');
            $this->assertEquals(
                    array('Accept: application/json'),
                    $method->invoke($this->object)
            );
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

    /**
     * @covers Soundcloud\Service::_buildDefaultHeaders
     */
    public function test_buildDefaultHeadersWithAccessToken()
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $this->object->setAccessToken('1337');
            $method = self::getProtectedMethod('_buildDefaultHeaders');
            $this->assertEquals(
                    array('Accept: application/json', 'Authorization: OAuth 1337'),
                    $method->invoke($this->object)
            );
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

    /**
     * @covers Soundcloud\Service::_buildUrl
     */
    public function test_buildUrl()
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $method = self::getProtectedMethod('_buildUrl');
            $this->assertEquals(
                    'https://api.soundcloud.com/v1/me',
                    $method->invoke($this->object, 'me')
            );
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

    /**
     * @covers Soundcloud\Service::_buildUrl
     */
    public function test_buildUrlWithQueryParameters()
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $method = self::getProtectedMethod('_buildUrl');
            $this->assertEquals(
                    'https://api.soundcloud.com/v1/tracks?q=rofl+dubstep',
                    $method->invoke(
                            $this->object, 'tracks', array('q' => 'rofl dubstep')
                    )
            );
            $this->assertEquals(
                    'https://api.soundcloud.com/v1/tracks?q=rofl+dubstep&filter=public',
                    $method->invoke(
                            $this->object,
                            'tracks',
                            array('q' => 'rofl dubstep', 'filter' => 'public')
                    )
            );
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

    /**
     * @covers Soundcloud\Service::_buildUrl
     * @covers Soundcloud\Service::setDevelopment
     */
    public function test_buildUrlWithDevelopmentDomain()
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $method = self::getProtectedMethod('_buildUrl');
            $this->object->setDevelopment(true);
            $this->assertEquals(
                    'https://api.sandbox-soundcloud.com/v1/me',
                    $method->invoke($this->object, 'me')
            );
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

    /**
     * @covers Soundcloud\Service::_buildUrl
     * @covers Soundcloud\Service::setDevelopment
     */
    public function test_buildUrlWithoutApiVersion()
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $method = self::getProtectedMethod('_buildUrl');
            $this->assertEquals(
                    'https://api.soundcloud.com/me',
                    $method->invoke($this->object, 'me', null, false)
            );
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

    /**
     * @covers Soundcloud\Service::_buildUrl
     */
    public function test_buildUrlWithAbsoluteUrl()
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $method = self::getProtectedMethod('_buildUrl');
            $this->assertEquals(
                    'https://api.soundcloud.com/me',
                    $method->invoke($this->object, 'https://api.soundcloud.com/me')
            );
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

    /**
     * @covers Soundcloud\Service::_buildUrl
     * @covers Soundcloud\Service::setAccessToken
     */
    public function test_buildUrlWithoutAccessToken()
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $method = self::getProtectedMethod('_buildUrl');
            $this->object->setAccessToken(null);
            $this->assertEquals(
                    'https://api.soundcloud.com/v1/tracks?consumer_key=1337',
                    $method->invoke($this->object, 'tracks')
            );
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

    /**
     * @covers Soundcloud\Service::_parseHttpHeaders
     * @dataProvider dataProviderHttpHeaders
     */
    function test_parseHttpHeaders($rawHeaders, $expectedHeaders)
    {
        if (-1 < version_compare(phpversion(), '5.3.2')) {
            $method        = self::getProtectedMethod('_parseHttpHeaders');
            $parsedHeaders = $method->invoke($this->object, $rawHeaders);

            foreach ($parsedHeaders as $key => $value) {
                $this->assertEquals($value, $expectedHeaders[$key]);
            }
        } else {
            $this->markTestSkipped('Requires PHP >=5.3.2');
        }
    }

}
