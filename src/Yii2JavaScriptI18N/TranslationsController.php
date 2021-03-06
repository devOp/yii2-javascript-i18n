<?php
namespace Yii2JavaScriptI18N;

use Yii;
use yii\web\Controller;

/**
 *
 * @author devOp
 */
class TranslationsController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => 'yii\filters\HttpCache',
                'only' => ['i18n'],
                'lastModified' => function () {
                    return filemtime($this->module->jsFilenameOnServer);
                },
            ],
        ];
    }

    public function actionI18n()
    {
        return Yii::$app->response->sendFile($this->module->jsFilenameOnServer);
    }

    public function actionCollect()
    {
        $this->module->collect();
        return "JS Translations collected and saved.";
    }
}