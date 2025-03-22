<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\BackendWidget\Block\Form;

use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use Infrangible\BackendWidget\Block\Form;
use Infrangible\BackendWidget\Data\Form\Element\Back;
use Infrangible\BackendWidget\Data\Form\Element\Submit;
use Infrangible\Core\Helper\Block;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Tab extends Form
{
    /** @var Json */
    protected $json;

    /** @var Block */
    protected $blockHelper;

    /** @var string */
    protected $indexUrlRoute;

    /** @var array */
    protected $indexUrlParams;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Arrays $arrays,
        \Infrangible\Core\Helper\Registry $registryHelper,
        \Infrangible\BackendWidget\Helper\Form $formHelper,
        Json $json,
        Block $blockHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $arrays,
            $registryHelper,
            $formHelper,
            $data
        );

        $this->json = $json;
        $this->blockHelper = $blockHelper;

        $this->indexUrlRoute = $arrays->getValue(
            $data,
            'index_url_route'
        );
        $this->indexUrlParams = $arrays->getValue(
            $data,
            'index_url_params',
            []
        );
    }

    protected function followUpFields(\Magento\Framework\Data\Form $form)
    {
        $fieldSet = $form->addFieldset(
            'actions',
            ['legend' => __('Actions'), 'class' => 'actions']
        );

        $fieldSet->addField(
            $this->parentObjectKey,
            'hidden',
            [
                'name'  => $this->parentObjectKey,
                'value' => $this->parentObjectValue
            ]
        );

        $fieldSet->addField(
            'save',
            Submit::class,
            [
                'title'          => __('Save'),
                'class'          => 'action-columns action-primary',
                'data-mage-init' => htmlspecialchars(
                    $this->json->encode(
                        [
                            'button' => [
                                'event'  => 'save',
                                'target' => sprintf(
                                    '#%s',
                                    $form->getDataUsingMethod('id')
                                )
                            ]
                        ]
                    )
                )
            ]
        );

        $fieldSet->addField(
            'back',
            Back::class,
            [
                'title'     => __('Back'),
                'class'     => 'action-columns action-secondary',
                'buttonUrl' => $this->getUrl(
                    $this->gridUrlRoute,
                    $this->gridUrlParams
                )
            ]
        );
    }

    /**
     * @throws LocalizedException
     */
    public function toHtml(): string
    {
        $formHtml = parent::toHtml();

        /** @var Tab\Form $containerBlock */
        $containerBlock = $this->blockHelper->createLayoutBlock(
            $this->getLayout(),
            Tab\Form::class
        );

        $containerBlock->setObjectName($this->objectName);
        $containerBlock->setFormId($this->getForm()->getDataUsingMethod('id'));
        $containerBlock->setFormHtml($formHtml);

        return $containerBlock->toHtml();
    }
}
