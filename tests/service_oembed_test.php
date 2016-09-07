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
 * Filter for component 'filter_oembed'
 *
 * @package   filter_oembed
 * @copyright Erich M. Wappis / Guy Thomas 2016
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use filter_oembed\service\oembed;

defined('MOODLE_INTERNAL') || die();

/**
 * Class testable_oembed.
 *
 * @package   filter_oembed
 * @copyright Erich M. Wappis / Guy Thomas 2016
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_oembed extends oembed {

    /**
     * Singleton.
     *
     * @return oembed
     */
    public static function get_instance($providers = 'enabled') {
        /** @var $instance oembed */
        static $instance;
        if ($instance) {
            return $instance;
        } else {
            return new testable_oembed();
        }
    }

    /**
     * Calls the protected download_providers function.
     */
    public static function protected_download_providers() {
        return self::download_providers();
    }
}
/**
 * Tests for course_service.php
 *
 * @package   filter_oembed
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Guy Thomas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_oembed_service_oembed_testcase extends advanced_testcase {

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
        $this->assertNotEmpty($provider->providername);
        $this->assertNotEmpty($provider->providerurl);
        $this->assertNotEmpty($provider->endpoints);
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
}
