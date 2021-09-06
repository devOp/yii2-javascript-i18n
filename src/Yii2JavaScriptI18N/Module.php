<?php

namespace Yii2JavaScriptI18N;

use Yii;
use RecursiveDirectoryIterator;
use yii\base\Component;
use yii\web\View;
use yii\helpers\Json;

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
        // hack for alias problem
        Yii::setAlias('@Yii2JavaScriptI18N', __DIR__);

        parent::init();
        $this->jsFilenameOnServer =
            Yii::getAlias('@app') . '/' . $this->jsFilename;
        $dirname = dirname($this->jsFilenameOnServer);
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }
        if (Yii::$app instanceof \yii\web\Application) {
            Yii::$app->view->registerJsFile(
                '/collector/translations/i18n'
            );
            $this->registerJsScript();
        }
    }

    private function findTranslationsInJsFile(&$results, $file) {
        preg_match_all('/yii\.t\(\s*([^)]+?)\s*\)/', file_get_contents($file), $matches, PREG_OFFSET_CAPTURE);
        if (!empty($matches) && isset($matches[1]) && !empty($matches[1])) {
            echo $file . "\r\n";
            foreach ($matches[1] as $match) {
                $array = explode(',', $match[0]);
                $category = trim($array[0], "\"\'");
                $text = trim(ltrim($array[1], " "), "\"\'");
                $results[$category][] = $text;
            }
        }
    }

    public function collect() {
        $neededTranslations = $this->collectTranslationsTexts();
        $languages = $this->getLanguages();
        $translations = $this->getTranslations($languages, $neededTranslations);
        $this->saveJsFile($translations);
    }

    private function collectTranslationsTexts() {
        $results = [];
        $basePath = Yii::getAlias('@app') . DIRECTORY_SEPARATOR;
        foreach ($this->jsDir as $dir) {
            $iterator = new RecursiveDirectoryIterator($basePath . $dir);
            foreach(new \RecursiveIteratorIterator($iterator) as $file) {
                $arr = explode('.', $file);
                if (!is_dir($file) && strtolower(array_pop($arr)) == 'js') {
                    $this->findTranslationsInJsFile($results, $file);
                }
            }
        }
        return $results;
    }

    private function getLanguages() {
        $languages = [];
        $iterator = new RecursiveDirectoryIterator(realpath(Yii::getAlias('@app/messages')));
        foreach ($iterator as $file) {
            $dir = $file->getFilename();
            if (is_dir($file) && $dir != '.' && $dir != '..') {
                $languages[] = $file->getFilename();
            }
        }
        return $languages;
    }

    private function getTranslations($langs, $texts) {
        $translations = [];
        foreach ($langs as $lang) {
            $translations[$lang] = [];
            foreach ($texts as $cat => $catTexts) {
                $translations[$lang][$cat] = [];
                foreach ($catTexts as $text) {
                    $translations[$lang][$cat][$text] = Yii::t($cat, $text, [], $lang);
                }
            }
        }
        return $translations;
    }

    private function saveJsFile($result)
    {
        return
            file_put_contents(
                $this->jsFilenameOnServer,
                'var YII_I18N_JS = ' . Json::encode($result) . ';' . "\n"
            );
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
