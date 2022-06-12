<?php

namespace ensostudio\yii2fenom;

use Fenom;
use Fenom\Provider;
use Yii;
use yii\base\ViewRenderer;
use yii\helpers\FileHelper;
use yii\helpers\Url;

/**
 * The view renderer based on [Fenom template engine](https://github.com/fenom-template/fenom).
 */
class FenomViewRenderer extends ViewRenderer
{
    /**
     * @var string the base directory of Fenom templates
     */
    public $viewDir = '@app/views/fenom';
    /**
     * @var string the base directory of compiled templates
     */
    public $compileDir = '@runtime/fenom';
    /**
     * @var bool[] the Fenom configuration options
     * @see https://github.com/fenom-template/fenom/blob/master/docs/en/configuration.md
     */
    public $options = [
        Fenom::AUTO_RELOAD => true,
        Fenom::AUTO_ESCAPE => true,
        Fenom::AUTO_STRIP => false,
        Fenom::FORCE_COMPILE => false,
        Fenom::FORCE_VERIFY => false,
        Fenom::FORCE_INCLUDE => true,
        Fenom::DISABLE_CACHE => YII_DEBUG,
    ];
    /**
     * @var callable[] an array of inline functions as name/callback pairs.
     * Callback syntax: `function (array $options): string`.
     * Configuration: `['url' => ['\yii\helpers\Url', 'to']]`, in template: `{url url=$params}` or `{url $params}`.
     * @see https://github.com/fenom-template/fenom/blob/master/docs/en/ext/extend.md#inline-function
     */
    public $inlineFunctions = [
        'url' => [Url::class, 'to'],
        'currentUrl' => [Url::class, 'current'],
        'alias' => [Yii::class, 'getAlias']
    ];
    /**
     * @var callable[] an array of block functions as name/callback pairs.
     * Callback syntax: `function (array $options, string $content): string`.
     * @see https://github.com/fenom-template/fenom/blob/master/docs/en/ext/extend.md#block-function
     */
    public $blockFunctions = [];
    /**
     * @var string[] an array of variables/properties as name/accessor pairs, accessor is inline code to get value,
     * configuration: `['application' => 'Yii::$app']`,  in template: `{set $urlManager = $.application->urlManager}`.
     */
    public $variableAccessors = [
        'application' => 'Yii::$app',
        'user' => 'Yii::$app->user',
        'cache' => 'Yii::$app->cache',
        'auth' => 'Yii::$app->authManager',
        'formatter' => 'Yii::$app->formatter',
        'i18n' => 'Yii::$app->i18n',
    ];
    /**
     * @var string[] an array of functions/methods as name/accessor pairs, accessor is inline code to call function,
     * configuration: `['component' => 'Yii::$app->get']`, in template: `{$.component('session')->sessionId}`
     */
    public $functionAccessors = [
        'component' => 'Yii::$app->get'
    ];

    /**
     * @var Fenom the instance of template engine
     */
    protected $fenom;

    /**
     * @inheritDoc
     * @throws yii\base\Exception
     */
    public function init()
    {
        parent::init();

        $this->fenom = new Fenom(new Provider(Yii::getAlias($this->viewDir)));
        $compileDir = Yii::getAlias($this->compileDir);
        FileHelper::createDirectory($compileDir);
        $this->fenom->setCompileDir($compileDir);
        $this->fenom->setOptions($this->options);
        foreach ($this->inlineFunctions as $name => $callback) {
            $this->fenom->addFunctionSmart($name, $callback);
        }
        foreach ($this->blockFunctions as $name => $callback) {
            $this->fenom->addBlockFunction($name, $callback);
        }
        foreach ($this->variableAccessors as $name => $accessor) {
            $this->fenom->addAccessorSmart($name, $accessor);
        }
        foreach ($this->functionAccessors as $name => $accessor) {
            $this->fenom->addAccessorSmart($name, $accessor, Fenom::ACCESSOR_CALL);
        }
        $this->fenom->addAccessorSmart('render', 'fetch', Fenom::ACCESSOR_METHOD);
    }

    /**
     * @inheritDoc
     */
    public function render($view, $file, $params)
    {
        return $this->fenom->fetch($file, ['view' => $view] + $params);
    }
}
