<?php

namespace Yii2JavaScriptI18N;

use Yii;

use yii\base\Component;
use yii\web\View;

/**
 * To use the Module, you should configure it in the application configuration like the following:
 *
 * ```php
 * [
 *     'module' => [
 *         'collector' => [
 *             'class' => 'Yii2JavaScriptI18N\Module',
 *             'jsDir' => [
 *
 *             ],
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ],
 * ```
 * @property array $jsDir
 * @property string $jsFilename
 * @property string $jsFilenameOnServer
 */



class Module extends \yii\base\Module
{
    public $jsDir = [];
    public $jsFilename = 'js/i18n.js';
    public $jsFilenameOnServer = '';

    public function init()
    {
        parent::init();
        $this->jsFilenameOnServer =
            Yii::getAlias('@app') . '/' . $this->jsFilename;
        $dirname = dirname($this->jsFilenameOnServer);
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }
        if (Yii::$app instanceof \yii\web\Application) {
            Yii::$app->view->registerJsFile(
                'collector/translations/i18n'
            );
            $this->registerJsScript();
        }
    }

    private function registerJsScript()
    {
        $sourceLanguage = strtolower(Yii::$app->sourceLanguage);
        $js = <<<JS
;(function () {
  if (!('yii' in window)) {
    window.yii = {};
  }
  if (!('t' in window.yii)) {
    if (!document.documentElement.lang) {
      throw new Error(
        'You must specify the "lang" attribute for the <html> element'
      );
    }
    yii.t = function (category, message, params, language) {
      language = language || document.documentElement.lang;
      var translatedMessage;
      if (
        language === "{$sourceLanguage}" ||
        !YII_I18N_JS ||
        !YII_I18N_JS[language] ||
        !YII_I18N_JS[language][category] ||
        !YII_I18N_JS[language][category][message]
      ) {
        translatedMessage = message;
      } else {
        translatedMessage = YII_I18N_JS[language][category][message];
      }
      if (params) {
        Object.keys(params).map(function (key) {
          var escapedParam =
            // https://stackoverflow.com/a/6969486/4223982
            key.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
          var regExp = new RegExp('\\\{' + escapedParam + '\\\}', 'g');
          translatedMessage = translatedMessage.replace(regExp, params[key]);
        });
      }
      return translatedMessage;
    };
  }
})();
JS;
        Yii::$app->view->registerJs($js, View::POS_END);
    }
}
