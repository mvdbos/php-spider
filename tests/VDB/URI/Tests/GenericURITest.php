<?php
namespace VDB\URI\Tests;

use VDB\URI\GenericURI;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 *
 *
 *    foo://example.com:8042/over/there?name=ferret#nose
 *    \_/   \______________/\_________/ \_________/ \__/
 *    |           |            |            |        |
 * scheme     authority       path        query   fragment
 *    |   _____________________|__
 *   / \ /                        \
 *   urn:example:animal:ferret:nose
 *
 *
 */
class GenericURITest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException VDB\URI\Exception\UriSyntaxException
     */
    public function testRelativeNoBase()
    {
        new GenericURI('/foo');
    }

    /**
     * @dataProvider normalizePercentEncodingProvider
     */
    public function testNormalizePercentEncoding($uri, $expected)
    {
        $uri = new GenericURI($uri);

        $this->assertEquals($expected, $uri->recompose());
    }

    /**
     * @dataProvider normalizeCaseProvider
     */
    public function testNormalizeCase($uri, $expected)
    {
        $uri = new GenericURI($uri);

        $this->assertEquals($expected, $uri->recompose());
    }

    /**
     * @return array
     */
    public function normalizeCaseProvider()
    {
        return array(
            array("HTTP://foo/bar", "http://foo/bar"),
            array("http://FOO/bar", "http://foo/bar"),
            array("http://foo/bar?BAZ", "http://foo/bar?BAZ"),
            array("http://foo/bar#BAZ", "http://foo/bar#BAZ"),
            array("http://USER@foo/bar", "http://USER@foo/bar"),
        );
    }

    /**
     * @return array
     */
    public function normalizePercentEncodingProvider()
    {
        return array(
            //paths
            array("http://foo/%20bar", "http://foo/%20bar"),
            array("http://foo/+bar", "http://foo/%20bar"),
            array("http://foo/ bar", "http://foo/%20bar"),
            array("http://foo/[bar", "http://foo/%5Bbar"),
            array("http://foo/%5bbar", "http://foo/%5Bbar"),

            //queries
            array("http://foo/?%20bar", "http://foo/?%20bar"),
            array("http://foo/?+bar", "http://foo/?%20bar"),
            array("http://foo/? bar", "http://foo/?%20bar"),
            array("http://foo/?[bar", "http://foo/?%5Bbar"),
            array("http://foo/?%5bbar", "http://foo/?%5Bbar"),
            
            //fragments
            array("http://foo/#%20bar", "http://foo/#%20bar"),
            array("http://foo/#+bar", "http://foo/#%20bar"),
            array("http://foo/# bar", "http://foo/#%20bar"),
            array("http://foo/#[bar", "http://foo/#%5Bbar"),
            array("http://foo/#%5bbar", "http://foo/#%5Bbar"),
        );
    }

    /**
     * @dataProvider relativeReferenceProvider
     */
    public function testRelativeReferenceNormal($relative, $base, $expected)
    {
        $uri = new GenericURI($relative, $base);

        $this->assertEquals($expected, $uri->recompose());
    }

    /**
     * @return array
     *
     * From RFC 3986 paragraph 5.4
     */
    public function relativeReferenceProvider()
    {
        return array(
            array("http://foo", 'http://a/b/c/d;p?q', "http://foo"), // base URI fragment should be ignored
            array("http://foo", 'http://a/b/c/d;p?q', "http://foo"), // if rel has scheme, base is effectiviy ignored
            array("g:h", 'http://a/b/c/d;p?q', "g:h"),
            array("g", 'http://a/b/c/d;p?q', "http://a/b/c/g"),
            array("g/", 'http://a/b/c/d;p?q', "http://a/b/c/g/"),
            array("/g", 'http://a/b/c/d;p?q', "http://a/g"),
            array("//g", 'http://a/b/c/d;p?q', "http://g"),
            array("?y", 'http://a/b/c/d;p?q', "http://a/b/c/d;p?y"),
            array("g?y", 'http://a/b/c/d;p?q', "http://a/b/c/g?y"),
            array("#s", 'http://a/b/c/d;p?q', "http://a/b/c/d;p?q#s"),
            array("g#s", 'http://a/b/c/d;p?q', "http://a/b/c/g#s"),
            array("g?y#s", 'http://a/b/c/d;p?q', "http://a/b/c/g?y#s"),
            array(";x", 'http://a/b/c/d;p?q', "http://a/b/c/;x"),
            array("g;x", 'http://a/b/c/d;p?q', "http://a/b/c/g;x"),
            array("g;x?y#s", 'http://a/b/c/d;p?q', "http://a/b/c/g;x?y#s"),
            array("", 'http://a/b/c/d;p?q', "http://a/b/c/d;p?q"),
        );
    }


    /**
     * @dataProvider relativeReferenceDotSegmentsProvider
     */
    public function testRelativeReferenceDotSegments($relative, $base, $expected)
    {
        $uri = new GenericURI($relative, $base);

        $this->assertEquals($expected, $uri->recompose());
    }

    /**
     * @return array
     *
     * From RFC 3986 paragraph 5.4
     */
    public function relativeReferenceDotSegmentsProvider()
    {
        return array(
            array("./g", 'http://a/b/c/d;p?q', "http://a/b/c/g"),
            array(".", 'http://a/b/c/d;p?q', "http://a/b/c/"),
            array("./", 'http://a/b/c/d;p?q', "http://a/b/c/"),
            array("..", 'http://a/b/c/d;p?q', "http://a/b/"),
            array("../", 'http://a/b/c/d;p?q', "http://a/b/"),
            array("../g", 'http://a/b/c/d;p?q', "http://a/b/g"),
            array("../..", 'http://a/b/c/d;p?q', "http://a/"),
            array("../../", 'http://a/b/c/d;p?q', "http://a/"),
            array("../../g", 'http://a/b/c/d;p?q', "http://a/g"),
        );
    }


    /**
     * @dataProvider abnormalRelativeReferenceDotSegmentsProvider
     */
    public function testAbnormalRelativeReferenceDotSegments($relative, $base, $expected)
    {
        $uri = new GenericURI($relative, $base);

        $this->assertEquals($expected, $uri->recompose());
    }

    /**
     * @return array
     *
     * From RFC 3986 paragraph 5.4.2
     */
    public function abnormalRelativeReferenceDotSegmentsProvider()
    {
        return array(
            array("../../../g", 'http://a/b/c/d;p?q', "http://a/g"),
            array("../../../../g", 'http://a/b/c/d;p?q', "http://a/g"),
            array("/../../../../g", 'http://a/b/c/d;p?q', "http://a/g"),
            array("/./g", 'http://a/b/c/d;p?q', "http://a/g"),
            array("/../g", 'http://a/b/c/d;p?q', "http://a/g"),
            array("g.", 'http://a/b/c/d;p?q', "http://a/b/c/g."),
            array(".g", 'http://a/b/c/d;p?q', "http://a/b/c/.g"),
            array("g..", 'http://a/b/c/d;p?q', "http://a/b/c/g.."),
            array("..g", 'http://a/b/c/d;p?q', "http://a/b/c/..g"),
            array("./../g", 'http://a/b/c/d;p?q', "http://a/b/g"),
            array("./g/.", 'http://a/b/c/d;p?q', "http://a/b/c/g/"),
            array("g/./h", 'http://a/b/c/d;p?q', "http://a/b/c/g/h"),
            array("g/../h", 'http://a/b/c/d;p?q', "http://a/b/c/h"),
            array("g;x=1/./y", 'http://a/b/c/d;p?q', "http://a/b/c/g;x=1/y"),
            array("g;x=1/../y", 'http://a/b/c/d;p?q', "http://a/b/c/y"),
            array("g?y/./x", 'http://a/b/c/d;p?q', "http://a/b/c/g?y/./x"),
            array("g?y/../x", 'http://a/b/c/d;p?q', "http://a/b/c/g?y/../x"),
            array("g#s/./x", 'http://a/b/c/d;p?q', "http://a/b/c/g#s/./x"),
            array("g#s/../x", 'http://a/b/c/d;p?q', "http://a/b/c/g#s/../x"),
        );
    }



    /**
     * @param $uri
     * @dataProvider hostURIProvider
     */
    public function testHost($uriString, $expected)
    {
        $uri = new GenericURI($uriString);

        $this->assertEquals($expected, $uri->getHost());
    }

    /**
     * @expectedException VDB\URI\Exception\UriSyntaxException
     * @dataProvider noIpSixURIProvider
     */
    public function testNoIpSixSupport($uriString)
    {
        new GenericURI($uriString);
    }

    /**
     * @return array
     * All taken rom RFC 3986
     */
    public function noIpSixURIProvider()
    {
        return array(
            array('ldap://user:pass@[2001:db8::7]/c=GB?objectClass?one'),
            array('ldap://[2001:db8::7]/c=GB?objectClass?one'),
        );
    }

    /**
     * @return array
     * All taken rom RFC 3986
     */
    public function hostURIProvider()
    {
        return array(
            array('ftp:', ''),
            array('ftp://ftp.is.co.za/rfc/rfc1808.txt', 'ftp.is.co.za'),
            array('mailto:John.Doe@example.com', ''),
            array('news:comp.infosystems.www.servers.unix', ''),
            array('tel:+1-816-555-1212', ''),
            array('telnet://192.0.2.16:80/', '192.0.2.16'),
            array('urn:oasis:names:specification:docbook:dtd:xml:4.1.2', ''),
            array('foo://example.com:8042/over/there?name=ferret#nose', 'example.com'),
        );
    }
}
