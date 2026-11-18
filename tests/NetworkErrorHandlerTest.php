<?php

use PHPUnit\Framework\TestCase;
use TorneLIB\Module\Exception\AddressException;
use TorneLIB\Module\Exception\DomainException;
use TorneLIB\Module\Network\Address;
use TorneLIB\Module\Network\Domain;

class NetworkErrorHandlerTest extends TestCase
{
    private function assertPreviousErrorHandlerIsPreserved(callable $operation)
    {
        $sentinel = function ($level, $message) {
            return false;
        };
        set_error_handler($sentinel);
        try {
            try {
                $operation();
                self::fail('Expected a library exception.');
            } catch (AddressException $e) {
                // The error handler must still be restored after a warning.
            } catch (DomainException $e) {
                // Validation errors must not replace unrelated warning handlers.
            }

            $observed = set_error_handler(function ($level, $message) {
                return false;
            });
            restore_error_handler();
            self::assertSame($sentinel, $observed);
        } finally {
            restore_error_handler();
        }
    }

    public function testDomainValidationRestoresPreviousErrorHandler()
    {
        $this->assertPreviousErrorHandlerIsPreserved(function () {
            (new Domain())->getUrlDomain('http://invalid host', true);
        });
    }

    public function testInvalidIpv6ArpaRestoresPreviousErrorHandler()
    {
        $this->assertPreviousErrorHandlerIsPreserved(function () {
            (new Address())->getIpv6FromOctets('not-an-arpa-address');
        });
    }
}
