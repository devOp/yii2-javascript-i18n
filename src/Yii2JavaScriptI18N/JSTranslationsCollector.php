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
        echo "\r\n";
        echo 'Start to collect JS Translation-Strings...' . "\r\n";
        $this->module->collect();
        echo '...saved' . "\r\n";
    }


}