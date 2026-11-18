<?php /** @noinspection PhpDeprecationInspection Magento's legacy grid, the parent class, needs the backend helper. */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BackendWidget\Block\Grid;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\BackendWidget\Block\Grid;
use Infrangible\BackendWidget\Helper\Session;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Registry;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Eav\Model\Config;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\UniversalFactory;

abstract class Tab extends Grid
{
    /** @var string */
    protected string $parentObjectKey;

    /** @var string */
    protected string $parentObjectValue;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param Database $databaseHelper
     * @param Arrays $arrays
     * @param Variables $variables
     * @param Registry $registryHelper
     * @param \Infrangible\BackendWidget\Helper\Grid $gridHelper
     * @param Session $sessionHelper
     * @param UniversalFactory $universalFactory
     * @param Config $eavConfig
     * @param array $data
     *
     * @phpstan-param array<int|string, mixed> $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Database $databaseHelper,
        Arrays $arrays,
        Variables $variables,
        Registry $registryHelper,
        \Infrangible\BackendWidget\Helper\Grid $gridHelper,
        Session $sessionHelper,
        UniversalFactory $universalFactory,
        Config $eavConfig,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $backendHelper,
            $databaseHelper,
            $arrays,
            $variables,
            $registryHelper,
            $gridHelper,
            $sessionHelper,
            $universalFactory,
            $eavConfig,
            $data
        );

        $this->parentObjectKey = $this->stringArgument($data, 'parent_object_key');
        $this->parentObjectValue = $this->stringArgument($data, 'parent_object_value');
    }

    /**
     * Returns the hidden field names.
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    protected function getHiddenFieldNames(): array
    {
        return [];
    }

    /**
     * Hook to change the collection after it was prepared.
     *
     * @param AbstractDb $collection
     *
     * @return void
     * @throws LocalizedException
     * @noinspection PhpDocRedundantThrowsInspection Magento declares it on Collection::addFieldToFilter(), not again in
     *               AbstractDb
     */
    protected function followUpCollection(AbstractDb $collection): void
    {
        parent::followUpCollection($collection);

        $collection->addFieldToFilter(
            $this->parentObjectKey,
            ['eq' => $this->parentObjectValue]
        );
    }

    /**
     * Renders the block as HTML.
     *
     * @return string
     * @throws LocalizedException
     */
    public function toHtml(): string
    {
        $this->setRowClickCallback(
            "function(grid, event) {
                event.preventDefault();
                require(['jquery'], function ($) {
                    var tab = $('#tab_" . $this->objectName . "');
                    tab.notification({
                        templates: {
                            global: '<div data-role=\"messages\" id=\"messages\">' +
                                '<div class=\"messages\"><div class=\"message message-success success\">' +
                                '<div data-ui-id=\"messages-message-success\"><%- data.message %></div></div>' +
                                '</div></div>'
                        }
                    });
                    var grid = $('#" . $this->getGridId() . "');
                    var gridParent = grid.parent();
                    if (event.target.tagName === 'A') {
                        var url = $(event.target).attr('href');
                        $.ajax({
                            url: url,
                            type: 'get',
                            showLoader: true,
                            dataType: 'json',
                            success: function(response) {
                                if (! response.responseJSON) {
                                    response.responseJSON = response;
                                }
                                tab.notification('clear');
                                if (response.responseJSON.message) {
                                    tab.notification('add', {
                                        error: response.responseJSON.error ? response.responseJSON : false,
                                        message: response.responseJSON.message
                                    });
                                }
                            },
                            error: function(response) {
                                if (! response.responseJSON) {
                                    response.responseJSON = response;
                                }
                                tab.notification('clear');
                                if (response.status === 302) {
                                    if (response.responseJSON.message) {
                                        tab.notification('add', {
                                            error: response.responseJSON.error ? response.responseJSON : false,
                                            message: response.responseJSON.message
                                        });
                                    }
                                    $.ajax({
                                        url: response.responseJSON.location,
                                        method: 'get',
                                        dataType: 'html',
                                        showLoader: true,
                                        context: gridParent[0],
                                        success: function (data) {
                                            var targetNode = gridParent;
                                            targetNode.html(data);
                                            targetNode.trigger('contentUpdated');
                                        }
                                    });
                                } else {
                                    if (response.responseJSON.message) {
                                        tab.notification('add', {
                                            error: true,
                                            message: response.responseJSON.message
                                        });
                                    }
                                }
                                window.scrollTo({
                                    top: grid.find('[data-role=messages] :first').offset().top - 100,
                                    behavior: 'instant'
                                });
                            },
                            complete: function(response) {
                                if (! response.responseJSON) {
                                    response.responseJSON = response;
                                }
                                if (response.responseJSON.ajaxExpired === 1) {
                                    window.location.href = response.responseJSON.ajaxRedirect;
                                }
                            }
                        });
                    } else {
                        var url = $(event.target.parentNode).attr('title');
                        $.ajax({
                            url: url,
                            method: 'get',
                            showLoader: true,
                            dataType: 'html',
                            context: gridParent[0],
                            success: function (data) {
                                var targetNode = gridParent;
                                targetNode.html(data);
                                targetNode.trigger('contentUpdated');
                            }
                        });
                    }
                });
            }"
        );

        return parent::toHtml();
    }
}
