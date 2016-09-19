<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the filter_oembed.
 *
 * @package    filter_oembed
 * @author Sushant Gawali (sushant@introp.net)
 * @author Erich M. Wappis <erich.wappis@uni-graz.at>
 * @author Guy Thomas <brudinie@googlemail.com>
 * @author Mike Churchward <mike.churchward@poetgroup.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright Microsoft, Inc.
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/oembed/tests/testable_oembed.php');
require_once($CFG->dirroot . '/filter/oembed/filter.php');

/**
 * @group filter_oembed
 * @group office365
 */
class filter_oembed_testcase extends advanced_testcase {

    protected $filter;

    /**
     * Sets up the test cases.
     */
    protected function setUp() {
        parent::setUp();
        $this->filter = new filter_oembed(context_system::instance(), array());
    }

    /**
     * Performs unit tests for all services supported by the filter.
     *
     * Need to update this test to not contact external services.
     */
    public function test_filter() {
        return true;
        $souncloudlink = '<p><a href="https://soundcloud.com/el-silenzio-fatal/enrique-iglesias-el-perdedor">soundcloud</a></p>';
        $youtubelink = '<p><a href="https://www.youtube.com/watch?v=ns6gCZI-Nj8">Youtube</a></p>';
        $officemixlink = '<p><a href="https://mix.office.com/watch/50ujrxsjvp9c">mix</a></p>';
        $vimeolink = '<p><a href="http://vimeo.com/115538038">vimeo</a></p>';
        $tedlink = '<p><a href="https://www.ted.com/talks/aj_jacobs_how_healthy_living_nearly_killed_me">Ted</a></p>';
        $slidesharelink = '<p><a href="http://www.slideshare.net/timbrown/ideo-values-slideshare1">slideshare</a></p>';
        $issuulink = '<p><a href="http://issuu.com/hujawes/docs/dehorew">issuu</a></p>';
        $polleverywherelink = '<p><a href="https://www.polleverywhere.com/multiple_choice_polls/AyCp2jkJ2HqYKXc/web">';
        $polleverywherelink .= '$popolleverywhere</a></p>';

        $filterinput = $souncloudlink.$youtubelink.$officemixlink.$vimeolink.$tedlink.$slidesharelink.$issuulink;
        $filterinput .= $polleverywherelink;

        $filteroutput = $this->filter->filter($filterinput);

        $youtubeoutput = '<iframe width="480" height="270" src="http://www.youtube.com/embed/ns6gCZI-Nj8?feature=oembed"';
        $youtubeoutput .= ' frameborder="0" allowfullscreen></iframe>';
        $this->assertContains($youtubeoutput, $filteroutput, 'Youtube filter fails');

        $soundcloudoutput = '<iframe width="480" height="270" scrolling="no" frameborder="no"';
        $soundcloudoutput .= ' src="https://w.soundcloud.com/player/?visual=true&url=http%3A%2F%2Fapi.soundcloud.com%';
        $soundcloudoutput .= '2Ftracks%2F132183772&show_artwork=true&maxwidth=480&maxheight=270%27"></iframe>';
        $this->assertContains($soundcloudoutput, $filteroutput, 'Soundcloud filter fails');

        $officemixoutput = '<iframe width="480" height="320" src="https://mix.office.com/embed/50ujrxsjvp9c" frameborder="0"';
        $officemixoutput .= ' allowfullscreen></iframe>';
        $this->assertContains($officemixoutput, $filteroutput, 'Office mix filter fails');

        $vimeooutput = '<iframe src="//player.vimeo.com/video/115538038" width="480" height="270" frameborder="0"';
        $vimeooutput .= ' title="Snow Fun" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
        $this->assertContains($vimeooutput, $filteroutput, 'Vimeo filter fails');

        $tedoutput = '<iframe src="https://embed-ssl.ted.com/talks/aj_jacobs_how_healthy_living_nearly_killed_me.html" width="480"';
        $tedoutput .= ' height="270" frameborder="0" scrolling="no" webkitAllowFullScreen mozallowfullscreen allowFullScreen>';
        $tedoutput .= '</iframe>';
        $this->assertContains($tedoutput, $filteroutput, 'Ted filter fails');

        $issuuoutput = '<div data-url="http://issuu.com/hujawes/docs/dehorew" style="width: 525px; height: 322px;"';
        $issuuoutput .= ' class="issuuembed"></div><script type="text/javascript" src="//e.issuu.com/embed.js" async="true">';
        $issuuoutput .= '</script>';
        $this->assertContains($issuuoutput, $filteroutput, 'Issuu filter fails');

        $polleverywhereoutput = '<script src="http://www.polleverywhere.com/multiple_choice_polls/AyCp2jkJ2HqYKXc/web.js';
        $polleverywhereoutput .= '?results_count_format=percent"></script>';
        $this->assertContains($polleverywhereoutput, $filteroutput, 'Poll everywhare filter fails');

        $slideshareoutput = '<iframe src="http://www.slideshare.net/slideshow/embed_code/29331355" width="427" height="356"';
        $slideshareoutput .= ' frameborder="0" marginwidth="0" marginheight="0" scrolling="no" style="border:1px solid #CCC;';
        $slideshareoutput .= ' border-width:1px; margin-bottom:5px; max-width: 100%;" allowfullscreen> </iframe>';
        $this->assertContains($slideshareoutput, $filteroutput, 'Slidershare filter fails');
    }

    /**
     * Test instance.
     */
    public function test_instance() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $oembed = testable_oembed::get_instance();
        $this->assertNotEmpty($oembed);
    }

    /**
     * Make sure providers array is correct.
     * @param array $providers
     */
    public function assert_providers_ok($providers) {
        $this->assertNotEmpty($providers);
        $provider = reset($providers);
        if (is_object($provider)) {
            // Test the provider object.
            $this->assertNotEmpty($provider->providername);
            $this->assertNotEmpty($provider->providerurl);
            $this->assertNotEmpty($provider->endpoints);
        } else if (is_array($provider)) {
            // Test the provider decoded JSON array.
            $this->assertArrayHasKey('provider_name', $provider);
            $this->assertArrayHasKey('provider_url', $provider);
            $this->assertArrayHasKey('endpoints', $provider);
        } else {
            // Test failed.
            $this->assertTrue(false);
        }
    }

    /**
     * Test providers.
     */
    public function test_providers() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $oembed = testable_oembed::get_instance();
        $providers = $oembed->providers;
        $this->assert_providers_ok($providers);
    }

    /**
     * Test html.
     * TODO - have a local oembed service with test fixtures for performing test.
     */
    public function test_embed_html() {
        $this->resetAfterTest(true);
        set_config('lazyload', 0, 'filter_oembed');
        $this->setAdminUser();
        $oembed = testable_oembed::get_instance();
        $text = $oembed->html_output('https://www.youtube.com/watch?v=Dsws8T9_cEE');
        $expectedtext = '<div class="oembed-content"><iframe width="480" height="270"' .
            ' src="https://www.youtube.com/embed/Dsws8T9_cEE?feature=oembed"' .
            ' frameborder="0" allowfullscreen></iframe></div>';
        $this->assertEquals($expectedtext, $text);
    }

    /**
     * Test lazy load html.
     * TODO - have a local oembed service with test fixtures for performing test.
     */
    public function test_preloader_html() {
        $this->resetAfterTest(true);
        set_config('lazyload', 1, 'filter_oembed');
        $this->setAdminUser();
        $oembed = testable_oembed::get_instance();
        $text = $oembed->html_output('https://www.youtube.com/watch?v=Dsws8T9_cEE');
        $this->assertContains('<div class="oembed-card-container">', $text);
        $this->assertRegExp('/<div class="oembed-card" style="(?:.*)" data-embed="(?:.*)"(?:.*)' .
            'data-aspect-ratio = "(?:.*)"(?:.*)>/is', $text);
        $this->assertRegExp('/<div class="oembed-card-title">(?:.*)<\/div>/', $text);
        $this->assertContains('<button class="btn btn-link oembed-card-play" aria-label="Play"></button>', $text);

    }

    /**
     * Test download providers.
     */
    public function test_download_providers() {
        $providers = testable_oembed::protected_download_providers();
        $this->assert_providers_ok($providers);
    }

    /**
     * Test get local providers.
     */
    public function test_get_local_providers() {
        $providers = testable_oembed::protected_get_local_providers();
        $this->assert_providers_ok($providers);
    }
}
