<?php

namespace Drupal\Tests\openai\Unit\Utility;

use Drupal\openai\Utility\StringHelper;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\openai\Utility\StringHelper
 * @group openai
 */
class StringHelperTest extends UnitTestCase {

  /**
   * Test that a string of HTML comes back with no HTML.
   */
  public function testStringWithHtml(): void {
    $text = "<h1>Foo</h1>
             <p>Foo bar baz, test? Good!</p>";
    $text = StringHelper::prepareText($text);
    $this->assertSame('Foo Foo bar baz, test? Good!', $text);
  }

  /**
   * A string of HTML with restricted tags should be removed.
   */
  public function testStringWithRestrictedTags(): void {
    $text = "<h1>Foo</h1>
             <p>Foo test? Good!</p><pre>bar baz</pre>";
    $text = StringHelper::prepareText($text);
    $this->assertSame('Foo Foo test? Good!', $text);

    $text = "<h1>Foo</h1>
            <pre><code><script type='text/javascript'>alert('Hello');</script></code></pre>
             <p>Foo bar baz, test? Good!</p>";
    $text = StringHelper::prepareText($text);
    $this->assertSame('Foo Foo bar baz, test? Good!', $text);

    $text = "<h1>Foo</h1>
             <p>Foo test <drupal-media uuid='12345' /></p>";
    $text = StringHelper::prepareText($text);
    $this->assertSame('Foo Foo test', $text);

    $text = "<h1>Foo</h1>
             <p>Foo test <drupal-media uuid='12345'>some text</drupal-media></p>";
    $text = StringHelper::prepareText($text);
    $this->assertSame('Foo Foo test', $text);
  }

  /**
   * Test that a string of HTML has the element(s) removed.
   */
  public function testStringWithHtmlElementOption(): void {
    $text = "<h1>Foo</h1>
             <p>Foo test? Good!</p>";
    $text = StringHelper::prepareText($text, ['h1'], 1000);
    $this->assertSame('Foo test? Good!', $text);

    $text = "<h1>Foo</h1>
             <p>Foo test? Good!</p>";
    $text = StringHelper::prepareText($text, ['h1', 'p'], 1000);
    $this->assertSame('', $text);
  }

  /**
   * Test that the string returned does not exceed the set length.
   */
  public function testStringDoesNotExceedLength(): void {
    $text = "<p>Dinosaurs roamed the earth millions of years ago.</p>";
    $text = StringHelper::prepareText($text, [], 26);
    $this->assertSame('Dinosaurs roamed the earth', $text);

    // Test the word boundary + trim results in no spaces.
    $text = "<p>Dinosaurs roamed the earth millions of years ago.</p>";
    $text = StringHelper::prepareText($text, [], 10);
    $this->assertSame('Dinosaurs', $text);

    $text = "<p>Dinosaurs roamed the earth millions of years ago.</p>";
    $text = StringHelper::prepareText($text, [], 12);
    $this->assertSame('Dinosaurs', $text);
  }

  /**
   * Test that a string with Unicode characters does not have those characters removed.
   */
  public function testStringWithUnicodeCharacters(): void {
    $text = "<p>En ø er et landområde helt omgivet af vand ved normalvandstand, der er mindre end et kontinent og større end en sten eller et skær. En lille ø kaldes for en holm eller småø.</p>";
    $text = StringHelper::prepareText($text);
    $this->assertSame('En ø er et landområde helt omgivet af vand ved normalvandstand, der er mindre end et kontinent og større end en sten eller et skær. En lille ø kaldes for en holm eller småø.', $text);
  }

}
