<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

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
use Magento\Framework\Validator\UniversalFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Tab extends Grid
{
    /** @var string */
    protected $parentObjectKey;

    /** @var string */
    protected $parentObjectValue;

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

        $this->parentObjectKey = $arrays->getValue(
            $data,
            'parent_object_key'
        );
        $this->parentObjectValue = $arrays->getValue(
            $data,
            'parent_object_value'
        );
    }

    protected function getHiddenFieldNames(): array
    {
        return [];
    }

    protected function followUpCollection(AbstractDb $collection): void
    {
        parent::followUpCollection($collection);

        $collection->addFieldToFilter(
            $this->parentObjectKey,
            ['eq' => $this->parentObjectValue]
        );
    }

    public function toHtml(): string
    {
        $this->setRowClickCallback(
            "function(grid, event) {
                console.debug(event);
                event.preventDefault();
                require(['jquery'], function ($) {
                    console.debug(event.target);
                    var tab = $('#tab_" . $this->objectName . "');
                    console.debug(tab);
                    tab.notification({
                        templates: {
                            global: '<div data-role=\"messages\" id=\"messages\">' +
                                '<div class=\"messages\"><div class=\"message message-success success\">' +
                                '<div data-ui-id=\"messages-message-success\"><%- data.message %></div></div>' +
                                '</div></div>'
                        }
                    });
                    var grid = $('#" . $this->getGridId() . "');
                    console.debug(grid);
                    var gridParent = grid.parent();
                    if (event.target.tagName === 'A') {
                        var url = $(event.target).attr('href');
                        $.ajax({
                            url: url,
                            type: 'get',
                            showLoader: true,
                            dataType: 'json',
                            success: function(response) {
                                console.debug(response);
                                if (! response.responseJSON) {
                                    response.responseJSON = response;
                                }
                                console.debug(response.responseJSON);
                                tab.notification('clear');
                                if (response.responseJSON.message) {
                                    tab.notification('add', {
                                        error: response.responseJSON.error ? response.responseJSON : false,
                                        message: response.responseJSON.message
                                    });
                                }
                            },
                            error: function(response) {
                                console.debug(response);
                                if (! response.responseJSON) {
                                    response.responseJSON = response;
                                }
                                console.debug(response.responseJSON);
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
                                console.debug(response);
                                if (! response.responseJSON) {
                                    response.responseJSON = response;
                                }
                                console.debug(response.responseJSON);
                                if (response.responseJSON.ajaxExpired === 1) {
                                    window.location.href = response.responseJSON.ajaxRedirect;
                                }
                            }
                        });
                    } else {
                        var url = $(event.target.parentNode).attr('title');
                        console.debug(url);
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
