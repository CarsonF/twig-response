<?php

namespace Gmo\Web\Tests\Logger\Processor;

use Gmo\Web\Logger\Processor\ConstantProcessor;
use PHPUnit\Framework\TestCase;

class ConstantProcessorTest extends TestCase
{
    public function testInvoke()
    {
        $processor = new ConstantProcessor('foo', 'bar');

        $record = $processor(['extra' => []]);
        $extra = $record['extra'];

        $this->assertArrayHasKey('foo', $extra);
        $this->assertSame('bar', $extra['foo']);
    }
}
