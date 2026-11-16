<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Test\Unit\Helper;

use FeWeDev\Base\Arrays;
use Infrangible\BackendWidget\Helper\Session;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Tests the lists of hidden grid columns stored in the admin session.
 */
class SessionTest extends TestCase
{
    /**
     * Creates the helper with a session that returns the hidden field lists.
     *
     * @param mixed $hiddenFieldLists
     *
     * @return Session
     */
    private function createHelper(mixed $hiddenFieldLists): Session
    {
        $backendSession = $this->createStub(BackendSession::class);

        $backendSession->method('getData')->willReturn($hiddenFieldLists);

        return new Session(
            new Arrays(),
            $backendSession,
            $this->createStub(AuthSession::class)
        );
    }

    /**
     * Tests that the hidden fields of a grid are returned as list of strings.
     *
     * @return void
     * @throws NotFoundException
     */
    public function testGetHiddenFieldList(): void
    {
        $helper = $this->createHelper(['grid' => ['name', 3, ['nested']], 'other' => ['sku']]);

        $this->assertSame(['name', '3'], $helper->getHiddenFieldList('grid'));
        $this->assertSame([], $helper->getHiddenFieldList('unknown'));
    }

    /**
     * Tests that a missing list is reported, so that the grid uses its default hidden fields.
     *
     * @return void
     * @throws NotFoundException
     */
    public function testGetHiddenFieldListWithoutData(): void
    {
        $this->expectException(NotFoundException::class);

        $this->createHelper(null)->getHiddenFieldList('grid');
    }

    /**
     * Tests the conversion of request values to field names.
     *
     * @return void
     */
    public function testGetFieldNames(): void
    {
        $helper = $this->createHelper(null);

        $this->assertSame(['name', 'sku'], $helper->getFieldNames(['a' => 'name', 'b' => 'sku']));
        $this->assertSame(['name'], $helper->getFieldNames(['name', null, ['sku']]));
        $this->assertSame([], $helper->getFieldNames('name'));
    }
}
