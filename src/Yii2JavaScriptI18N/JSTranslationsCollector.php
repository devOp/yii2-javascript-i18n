<?php
namespace Yii2JavaScriptI18N;

use RecursiveDirectoryIterator;
use Yii;
use yii\console\Controller;
use yii\helpers\Json;

/**
 *
 * @author devOp
 */
class JSTranslationsCollector extends Controller
{

    public function actionCollect()
    {
        $neededTranslations = $this->collectTranslationsTexts();
        $languages = $this->getLanguages();
        $translations = $this->getTranslations($languages, $neededTranslations);
        $this->saveJsFile($translations);
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

    private function collectTranslationsTexts() {
        $results = [];
        $basePath = Yii::getAlias('@app') . DIRECTORY_SEPARATOR;
        foreach ($this->module->jsDir as $dir) {
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
            foreach ($texts as $cat => $texts) {
                $translations[$lang][$cat] = [];
                foreach ($texts as $text) {
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
                $this->module->jsFilenameOnServer,
                'var YII_I18N_JS = ' . Json::encode($result) . ';' . "\n"
            );
    }
}