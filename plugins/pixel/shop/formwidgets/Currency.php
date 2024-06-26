<?php namespace Pixel\Shop\FormWidgets;

use Flash;
use Config;
use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use Responsiv\Currency\Models\Currency as CurrencyModel;

class Currency extends FormWidgetBase
{
    /**
     * @var string the minimum/earliest date that can be selected.
     * eg: 2000-01-01
     */
    public $minPrice = null;

    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'shop-currency';

    /**
    * {@inheritDoc}
    */
    public function init()
    {
        parent::init();
    }

    /**
     * Render the form widget
     */
    public function render()
    {
        $this->prepareVars();
        $this->vars['name'] = $this->getFieldName();
        return $this->makePartial('widget', [
        	'field' => $this->formField,
        	'model' => $this->model
        ]);
    }

    /**
     * Load widget assets
     */
    public function loadAssets()
    {
        $this->addJs('js/jquery.maskMoney.js');
        $this->addCss('css/component.css');
    }

    /**
     * Prepare widget variables
     */
    public function prepareVars()
    {
        $this->vars['value'] = $this->getLoadValue();

        $this->customField = isset($this->config->field) ? $this->config->field : $this->fieldName.'-field';
        $this->vars['config']['customField'] = $this->customField;

        $this->vars['config']['onSale'] = property_exists($this->config, 'onSale');

        $this->vars['config']['placeholder'] = isset($this->config->placeholder)
            ? $this->config->placeholder
            : '0';

        $this->vars['config']['thousands'] = isset($this->config->thousands)
            ? $this->config->thousands
            : ',';

        $this->vars['config']['decimal'] = isset($this->config->decimal)
            ? $this->config->decimal
            : '.';

        $this->vars['config']['suffix'] = isset($this->config->suffix)
            ? $this->config->suffix
            : '';

        $this->vars['config']['prefix'] = isset($this->config->prefix)
            ? $this->config->prefix
            : '';

        $this->vars['config']['allowNegative'] = isset($this->config->allowNegative) && $this->config->allowNegative
            ? 'true'
            : 'false';

        // Allow plugins to override the configuration
        if ($config = Config::get('owl.formwidgets::money')) {
            if (is_array($config) && count($config) >= 1) {
                foreach ($config as $key => $value)
                    $this->vars['config'][$key] = $value;
            }
        }
    }

    /**
     * Return save value
     *
     * @return  float
     */
    public function getSaveValue($value)
    {
       if (!$input = input($this->getFieldName()))
            return 0;

        $input = preg_replace("/[^0-9]/", '', $input);
        $input = substr($input, 0, -2) . ',' . substr($input, -2);
        $input = floatval($input);

        return $input;
    }
}
