<?php
namespace app\controllers;

use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;

class AppController extends ActiveController {

	    public function beforeAction($action)
    {
    	if (parent::beforeAction($action)) {
    		$action = $action->id;
	        $controller = \Yii::$app->controller->id;
	        $route = "$controller/$action";
	        if ($route == "user/login" || $route == "user/logout") {
	        	return true;
	        } elseif (\Yii::$app->user->isGuest) {
	        	throw new \yii\web\UnauthorizedHttpException('Please log in');
	        }
	        if (!$user = \Yii::$app->user->can($route)) {
	        	//replace $route below by string message
	        	throw new \yii\web\ForbiddenHttpException($route);
	        }
    		return true;
    	} else {
    		return false;
    	}
    }

	public $serializer = [ 'class' => 'yii\rest\Serializer', 'collectionEnvelope' => 'items']; 

	public $page = 1;
}

?>