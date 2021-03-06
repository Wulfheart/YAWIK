<?php
/**
 * YAWIK
 *
 * @filesource
 * @license MIT
 * @copyright https://yawik.org/COPYRIGHT.php
 */

namespace CoreTest\Mail;

use PHPUnit\Framework\TestCase;
use Core\Mail\FileTransport;
use Laminas\Mail\Exception\RuntimeException;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\FileOptions;

/**
 * Class FileTransportTest
 *
 * @author Anthonius Munthi <me@itstoni.com>
 * @covers \Core\Mail\FileTransport
 * @package CoreTest\Mail
 * @since 0.30.1
 */
class FileTransportTest extends TestCase
{
    public function testSendThrowException()
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('toString')
            ->willReturn('mail content');

        $options = $this->createMock(FileOptions::class);
        $options->expects($this->once())
            ->method('getCallback')
            ->willReturn([$this,'getFileName'])
        ;
        $options->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('path/to')
        ;

        $transport = new FileTransport($options);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageRegExp('/Unable to write mail/i');
        @$transport->send($message);
    }

    public function getFileName()
    {
        return 'filename.eml';
    }

    public function testSend()
    {
        $testPath = sys_get_temp_dir().'/yawik/mails';
        if (!is_dir($testPath)) {
            mkdir($testPath, 0777, true);
        }

        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('toString')
            ->willReturn('mail content')
        ;

        $options = new FileOptions(['path' => $testPath]);
        $transport = new FileTransport($options);
        $transport->send($message);
    }
}
